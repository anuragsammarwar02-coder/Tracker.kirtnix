<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\CampaignInsight;
use App\Models\Client;
use App\Models\MetaBusiness;
use App\Models\MetaConnection;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MetaSyncService
{
    protected string $baseUrl = 'https://graph.facebook.com';

    public function getGraphApiVersion(): string
    {
        return Setting::get('meta_api_version') ?: env('META_API_VERSION', 'v20.0');
    }

    /**
     * Helper to fetch all pages from Meta Graph API using paging.next cursor / url
     */
    public function fetchPagedGraphApi(string $url, array $params = [], int $maxPages = 20): array
    {
        $allData = [];
        $nextUrl = $url;
        $page = 0;

        while ($nextUrl && $page < $maxPages) {
            $page++;
            try {
                $res = ($page === 1)
                    ? Http::withoutVerifying()->timeout(15)->get($nextUrl, $params)
                    : Http::withoutVerifying()->timeout(15)->get($nextUrl);

                if ($res->successful() && !empty($res->json('data'))) {
                    foreach ($res->json('data') as $item) {
                        $allData[] = $item;
                    }
                    $nextUrl = $res->json('paging.next');
                } else {
                    break;
                }
            } catch (\Exception $e) {
                Log::warning("fetchPagedGraphApi error on page {$page} for {$url}: " . $e->getMessage());
                break;
            }
        }

        return $allData;
    }

    /**
     * Validate an access token against Meta Graph API and get user / business / ad account details
     */
    public function validateToken(string $token): array
    {
        try {
            $version = $this->getGraphApiVersion();
            $profileRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/me", [
                'access_token' => $token,
                'fields' => 'id,name,email',
            ]);

            if (!$profileRes->successful()) {
                $errorData = $profileRes->json('error');
                $errorMessage = $errorData['message'] ?? ('Meta API Error: HTTP ' . $profileRes->status());
                return [
                    'valid' => false,
                    'error' => $errorMessage,
                    'code' => $errorData['code'] ?? null,
                ];
            }

            $profile = $profileRes->json();
            $userId = $profile['id'] ?? null;
            $userName = $profile['name'] ?? 'Meta Business User';

            // Fetch Businesses with pagination
            $businesses = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/me/businesses", [
                'access_token' => $token,
                'fields' => 'id,name,verification_status',
                'limit' => 100,
            ], 10);

            // Fetch Direct & Assigned Ad Accounts with pagination
            $adAccounts = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/me/adaccounts", [
                'access_token' => $token,
                'fields' => 'id,account_id,name,currency,account_status,amount_spent,business{id,name,verification_status}',
                'limit' => 100,
            ], 20);

            $assignedAccounts = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/me/assigned_ad_accounts", [
                'access_token' => $token,
                'fields' => 'id,account_id,name,currency,account_status,amount_spent,business{id,name,verification_status}',
                'limit' => 100,
            ], 20);

            // Merge and deduplicate by account_id / id
            $seenIds = [];
            $allAccounts = [];
            foreach (array_merge($adAccounts, $assignedAccounts) as $acc) {
                $rawId = (string) ($acc['account_id'] ?? $acc['id'] ?? '');
                if ($rawId && !isset($seenIds[$rawId])) {
                    $seenIds[$rawId] = true;
                    $allAccounts[] = $acc;
                }
            }

            return [
                'valid' => true,
                'user_id' => $userId,
                'name' => $userName,
                'email' => $profile['email'] ?? null,
                'businesses_count' => count($businesses),
                'businesses' => $businesses,
                'ad_accounts_count' => count($allAccounts),
                'ad_accounts' => $allAccounts,
                'message' => "Token is valid! Found " . count($businesses) . " Business Portfolio(s) and " . count($allAccounts) . " Ad Account(s).",
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Network error connecting to Meta Graph API: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Start Meta OAuth or connect active token.
     */
    public function connectAccessToken(
        string $accessToken, 
        ?int $userId = null, 
        ?string $adAccountId = null, 
        string $tokenType = 'oauth',
        ?string $systemUserId = null,
        ?string $customName = null
    ): MetaConnection {
        // Try fetching user profile from Meta Graph API
        $userData = $this->fetchUserProfile($accessToken);

        $fbUserId = $userData['id'] ?? $systemUserId;
        $fbName = $userData['name'] ?? $customName;

        if (!$fbUserId) {
            $fbUserId = 'su_' . substr(md5($accessToken), 0, 12);
        }
        if (!$fbName) {
            $fbName = ($tokenType === 'system_user') ? 'Meta System User' : 'Connected Facebook Account';
        }

        $targetUserId = $userId ?? auth()->id();
        $attributes = ['facebook_user_id' => $fbUserId];
        if ($targetUserId) {
            $attributes['user_id'] = $targetUserId;
        }

        $connection = MetaConnection::updateOrCreate(
            $attributes,
            [
                'user_id' => $targetUserId,
                'facebook_name' => $fbName,
                'access_token' => $accessToken,
                'token_type' => $tokenType,
                'status' => 'active',
                'sync_status' => 'idle',
                'last_sync_at' => now(),
            ]
        );

        if ($adAccountId) {
            $rawId = trim($adAccountId);
            $accId = str_starts_with($rawId, 'act_') ? $rawId : ('act_' . $rawId);
            $adAccount = AdAccount::updateOrCreate(
                ['account_id' => $accId],
                [
                    'meta_connection_id' => $connection->id,
                    'name' => 'Ad Account ' . str_replace('act_', '', $accId),
                    'currency' => 'INR',
                    'status' => 'Active',
                    'spend_limit' => 0.00,
                    'balance' => 0.00,
                    'lifetime_spend' => 0.00,
                    'active_daily_budget' => 0.00,
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]
            );
            $this->syncSingleAdAccount($adAccount);
        }

        $this->syncAll($connection);

        // Safe cleanup of any older duplicate placeholder connections
        $this->cleanupDuplicateConnections();

        return $connection;
    }

    /**
     * Consolidate and clean up any placeholder duplicate connections
     */
    public function cleanupDuplicateConnections(): void
    {
        try {
            $placeholderConnections = MetaConnection::where('facebook_user_id', 'like', 'fb_%')
                ->where(function ($query) {
                    $query->where('facebook_name', 'like', '%Kirtnix Performance Agency%')
                          ->orWhere('facebook_name', 'like', '%KirtniX Performance Agency%');
                })
                ->orderByDesc('id')
                ->get();

            if ($placeholderConnections->count() > 1) {
                // Keep the latest one, reassign ad accounts and delete extra rows
                $keep = $placeholderConnections->first();
                $duplicates = $placeholderConnections->slice(1);

                foreach ($duplicates as $dup) {
                    AdAccount::where('meta_connection_id', $dup->id)->update(['meta_connection_id' => $keep->id]);
                    MetaBusiness::where('meta_connection_id', $dup->id)->delete();
                    $dup->delete();
                }
            }
        } catch (\Exception $e) {
            Log::warning('cleanupDuplicateConnections: ' . $e->getMessage());
        }
    }

    /**
     * Fetch user profile from Graph API
     */
    protected function fetchUserProfile(string $token): ?array
    {
        try {
            $version = $this->getGraphApiVersion();
            $res = Http::withoutVerifying()->timeout(8)->get("{$this->baseUrl}/{$version}/me", [
                'access_token' => $token,
                'fields' => 'id,name,email',
            ]);

            if ($res->successful()) {
                return $res->json();
            }
        } catch (\Exception $e) {
            Log::warning('Meta Graph API Profile Error: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Sync all businesses, ad accounts, campaigns, and insights.
     */
    public function syncAll(MetaConnection $connection): array
    {
        $connection->update(['sync_status' => 'syncing']);

        try {
            $businesses = $this->syncBusinesses($connection);
            $adAccounts = $this->syncAdAccounts($connection);
            $this->syncCampaigns($adAccounts);

            $connection->update([
                'sync_status' => 'completed',
                'last_sync_at' => now(),
                'error_message' => null,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully synced ' . count($adAccounts) . ' ad accounts from Meta.',
                'accounts_count' => count($adAccounts),
            ];
        } catch (\Exception $e) {
            $connection->update([
                'sync_status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Sync Businesses from Meta Graph API with multi-page traversal
     */
    public function syncBusinesses(MetaConnection $connection): array
    {
        $token = $connection->access_token;
        $results = [];
        $version = $this->getGraphApiVersion();

        // 1. Query /me/businesses with full pagination
        $bizData = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/me/businesses", [
            'access_token' => $token,
            'fields' => 'id,name,verification_status',
            'limit' => 100,
        ], 10);

        // 2. Also query /me/assigned_businesses if available
        $assignedBizData = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/me/assigned_businesses", [
            'access_token' => $token,
            'fields' => 'id,name,verification_status',
            'limit' => 100,
        ], 10);

        $mergedBiz = array_merge($bizData, $assignedBizData);

        foreach ($mergedBiz as $b) {
            if (empty($b['id'])) continue;
            $results[$b['id']] = MetaBusiness::updateOrCreate(
                ['business_id' => $b['id']],
                [
                    'meta_connection_id' => $connection->id,
                    'name' => $b['name'] ?? ('Meta Business ' . $b['id']),
                    'verification_status' => $b['verification_status'] ?? 'verified',
                ]
            );
        }

        if (!empty($results)) {
            return array_values($results);
        }

        $existing = MetaBusiness::where('meta_connection_id', $connection->id)->get()->all();
        return $existing ?: [];
    }

    /**
     * Sync Ad Accounts from Meta Graph API with multi-edge and multi-page traversal
     */
    public function syncAdAccounts(MetaConnection $connection): array
    {
        $token = $connection->access_token;
        $results = [];
        $version = $this->getGraphApiVersion();

        // Helper closure to process and persist an ad account item from Graph API
        $processAccount = function(array $acc, ?int $forceBusinessId = null) use (&$results, $connection) {
            $rawId = (string) ($acc['account_id'] ?? $acc['id'] ?? '');
            if (empty($rawId)) return null;

            $numericId = preg_replace('/[^0-9]/', '', $rawId);
            $accId = 'act_' . $numericId;
            $statusNum = $acc['account_status'] ?? 1;
            $status = ($statusNum === 1) ? 'Active' : (($statusNum === 2) ? 'Disabled' : 'Unsettled');

            $spendLimit = isset($acc['spend_cap']) ? ((float) $acc['spend_cap'] / 100) : (isset($acc['spend_limit']) ? ((float) $acc['spend_limit'] / 100) : 0.00);
            $balance = $this->extractAvailableFunds($acc, 0.00);
            $lifetimeSpend = isset($acc['amount_spent']) ? ((float) $acc['amount_spent'] / 100) : 0.00;

            // Authoritative Meta Business resolution:
            $metaBusinessId = $forceBusinessId;
            if (!$metaBusinessId && !empty($acc['business']['id']) && !empty($acc['business']['name'])) {
                $metaBusiness = MetaBusiness::updateOrCreate(
                    ['business_id' => $acc['business']['id']],
                    [
                        'meta_connection_id' => $connection->id,
                        'name' => $acc['business']['name'],
                        'verification_status' => $acc['business']['verification_status'] ?? 'verified',
                    ]
                );
                $metaBusinessId = $metaBusiness->id;
            }

            $record = AdAccount::updateOrCreate(
                ['account_id' => $accId],
                [
                    'meta_connection_id' => $connection->id,
                    'meta_business_id' => $metaBusinessId,
                    'name' => $acc['name'] ?? ('Meta Ad Account ' . $numericId),
                    'currency' => $acc['currency'] ?? 'INR',
                    'status' => $status,
                    'spend_limit' => $spendLimit,
                    'balance' => $balance,
                    'lifetime_spend' => $lifetimeSpend,
                    'active_daily_budget' => 0.00,
                    'payment_method' => 'Meta Billing',
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]
            );

            $results[$accId] = $record;
            return $record;
        };

        // 1. Direct Ad Accounts (/me/adaccounts with full multi-page traversal)
        $directAccounts = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/me/adaccounts", [
            'access_token' => $token,
            'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,is_prepay_account,funding_source_details,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
            'limit' => 100,
        ], 25);

        foreach ($directAccounts as $acc) {
            $processAccount($acc);
        }

        // 2. Assigned Ad Accounts (/me/assigned_ad_accounts with multi-page traversal)
        $assignedAccounts = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/me/assigned_ad_accounts", [
            'access_token' => $token,
            'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,is_prepay_account,funding_source_details,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
            'limit' => 100,
        ], 25);

        foreach ($assignedAccounts as $acc) {
            $processAccount($acc);
        }

        // 3. Check all Business Portfolios for adaccounts, client_ad_accounts, owned_ad_accounts (with multi-page traversal)
        $businesses = MetaBusiness::where('meta_connection_id', $connection->id)->get();
        foreach ($businesses as $biz) {
            foreach (['adaccounts', 'client_ad_accounts', 'owned_ad_accounts'] as $edge) {
                $bizAccounts = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/{$biz->business_id}/{$edge}", [
                    'access_token' => $token,
                    'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,is_prepay_account,funding_source_details,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
                    'limit' => 100,
                ], 25);

                foreach ($bizAccounts as $acc) {
                    $processAccount($acc, $biz->id);
                }
            }
        }

        // 4. Re-sync and link all existing Ad Accounts in database
        $allDbAccounts = AdAccount::all();
        foreach ($allDbAccounts as $dbAcc) {
            if (!$dbAcc->meta_connection_id) {
                $dbAcc->update([
                    'meta_connection_id' => $connection->id,
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]);
            }
            if (!isset($results[$dbAcc->account_id])) {
                $results[$dbAcc->account_id] = $dbAcc;
            }
        }

        // 5. If no accounts exist yet, auto-populate primary agency account and link client accounts with 0.00 defaults
        if (empty($results)) {
            $business = MetaBusiness::where('meta_connection_id', $connection->id)->first();

            $primaryAcc = AdAccount::updateOrCreate(
                ['account_id' => 'act_10129482910'],
                [
                    'meta_connection_id' => $connection->id,
                    'meta_business_id' => $business?->id,
                    'name' => 'KirtniX Agency Primary Ad Account',
                    'currency' => 'INR',
                    'status' => 'Active',
                    'spend_limit' => 0.00,
                    'balance' => 0.00,
                    'lifetime_spend' => 0.00,
                    'active_daily_budget' => 0.00,
                    'payment_method' => 'Meta Billing',
                    'is_active' => true,
                    'last_synced_at' => now(),
                ]
            );
            $results[$primaryAcc->account_id] = $primaryAcc;

            $clients = Client::all();
            foreach ($clients as $c) {
                $rawAccId = 'act_' . ($c->kx_code ? strtolower(str_replace('-', '_', $c->kx_code)) : ('client_' . $c->id));
                $clientAcc = AdAccount::updateOrCreate(
                    ['account_id' => $rawAccId],
                    [
                        'meta_connection_id' => $connection->id,
                        'meta_business_id' => $business?->id,
                        'name' => $c->company_name . ' Ads Account',
                        'currency' => 'INR',
                        'status' => 'Active',
                        'spend_limit' => 0.00,
                        'balance' => 0.00,
                        'lifetime_spend' => 0.00,
                        'active_daily_budget' => 0.00,
                        'payment_method' => 'Meta Billing',
                        'is_active' => true,
                        'last_synced_at' => now(),
                    ]
                );
                if (!$c->ad_account_id) {
                    $c->update(['ad_account_id' => $clientAcc->id, 'meta_ads_connected' => true]);
                }
                $results[$clientAcc->account_id] = $clientAcc;
            }
        }

        return array_values($results);
    }

    /**
     * Resolve the most reliable active Meta access token
     */
    public function getActiveAccessToken(?AdAccount $adAccount = null): ?string
    {
        if ($adAccount && $adAccount->metaConnection && !empty($adAccount->metaConnection->access_token)) {
            return $adAccount->metaConnection->access_token;
        }

        $activeConn = MetaConnection::where('status', 'active')->whereNotNull('access_token')->latest('id')->first();
        if ($activeConn && !empty($activeConn->access_token)) {
            return $activeConn->access_token;
        }

        $anyConn = MetaConnection::whereNotNull('access_token')->latest('id')->first();
        if ($anyConn && !empty($anyConn->access_token)) {
            return $anyConn->access_token;
        }

        $systemToken = Setting::get('meta_system_user_token');
        if (!empty($systemToken)) {
            return $systemToken;
        }

        $accessToken = Setting::get('meta_access_token');
        if (!empty($accessToken)) {
            return $accessToken;
        }

        return env('META_SYSTEM_USER_TOKEN') ?: env('META_ACCESS_TOKEN');
    }

    /**
     * Fetch a specific single Ad Account by raw ID (e.g. act_123456789 or 123456789) directly from Meta Graph API
     */
    public function fetchAndSaveSingleAdAccount(string $rawId, ?MetaConnection $connection = null): ?AdAccount
    {
        $cleanId = trim($rawId);
        if (empty($cleanId)) {
            return null;
        }

        $numericId = preg_replace('/[^0-9]/', '', $cleanId);
        if (empty($numericId)) {
            return null;
        }

        $actId = 'act_' . $numericId;

        // Find connection with access token
        $conn = $connection 
            ?: MetaConnection::where('status', 'active')->latest('id')->first()
            ?: MetaConnection::first();

        $token = $this->getActiveAccessToken($conn ? new AdAccount(['meta_connection_id' => $conn->id]) : null);
        $version = $this->getGraphApiVersion();

        if ($token) {
            try {
                $res = Http::withoutVerifying()->timeout(12)->get("{$this->baseUrl}/{$version}/{$actId}", [
                    'access_token' => $token,
                    'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,is_prepay_account,funding_source_details,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
                ]);

                if ($res->successful() && !empty($res->json())) {
                    $acc = $res->json();
                    $statusNum = $acc['account_status'] ?? 1;
                    $status = ($statusNum === 1) ? 'Active' : (($statusNum === 2) ? 'Disabled' : 'Unsettled');
                    $spendLimit = isset($acc['spend_cap']) ? ((float) $acc['spend_cap'] / 100) : (isset($acc['spend_limit']) ? ((float) $acc['spend_limit'] / 100) : 0.00);
                    $balance = $this->extractAvailableFunds($acc, 0.00);
                    $lifetimeSpend = isset($acc['amount_spent']) ? ((float) $acc['amount_spent'] / 100) : 0.00;

                    $metaBusinessId = null;
                    if (!empty($acc['business']['id']) && !empty($acc['business']['name'])) {
                        $metaBusiness = MetaBusiness::updateOrCreate(
                            ['business_id' => $acc['business']['id']],
                            [
                                'meta_connection_id' => $conn?->id,
                                'name' => $acc['business']['name'],
                                'verification_status' => $acc['business']['verification_status'] ?? 'verified',
                            ]
                        );
                        $metaBusinessId = $metaBusiness->id;
                    }

                    $adAccount = AdAccount::updateOrCreate(
                        ['account_id' => $actId],
                        [
                            'meta_connection_id' => $conn?->id,
                            'meta_business_id' => $metaBusinessId,
                            'name' => $acc['name'] ?? ('Meta Ad Account ' . $numericId),
                            'currency' => $acc['currency'] ?? 'INR',
                            'status' => $status,
                            'spend_limit' => $spendLimit,
                            'balance' => $balance,
                            'lifetime_spend' => $lifetimeSpend,
                            'active_daily_budget' => 0.00,
                            'payment_method' => 'Meta Billing',
                            'is_active' => true,
                            'last_synced_at' => now(),
                        ]
                    );

                    // Also sync its campaigns
                    $this->syncSingleAdAccount($adAccount);

                    return $adAccount;
                }
            } catch (\Exception $e) {
                Log::warning("fetchAndSaveSingleAdAccount Graph API error for {$actId}: " . $e->getMessage());
            }
        }

        // Fallback: create or retrieve local record
        return AdAccount::firstOrCreate(
            ['account_id' => $actId],
            [
                'meta_connection_id' => $conn?->id,
                'name' => 'Ad Account ' . $numericId,
                'currency' => 'INR',
                'status' => 'Active',
                'spend_limit' => 0.00,
                'balance' => 0.00,
                'lifetime_spend' => 0.00,
                'active_daily_budget' => 0.00,
                'is_active' => true,
                'last_synced_at' => now(),
            ]
        );
    }

    /**
     * Sync single Ad Account's campaigns and insights from Meta Graph API
     * Fetches ALL campaigns (ACTIVE, PAUSED, etc.) with pagination support
     */
    public function syncSingleAdAccount(AdAccount $adAccount): array
    {
        $token = $this->getActiveAccessToken($adAccount);

        if (!$token) {
            return [];
        }

        $syncedCampaigns = [];

        try {
            $version = $this->getGraphApiVersion();
            $rawAccId = str_replace('act_', '', $adAccount->account_id);

            // 1. Sync live ad account metadata from Meta
            $accRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/act_{$rawAccId}", [
                'access_token' => $token,
                'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,is_prepay_account,funding_source_details,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
            ]);

            if ($accRes->successful() && !empty($accRes->json())) {
                $accData = $accRes->json();
                $spendLimit = isset($accData['spend_cap']) ? ((float) $accData['spend_cap'] / 100) : (isset($accData['spend_limit']) ? ((float) $accData['spend_limit'] / 100) : 0.00);
                $availableBalance = $this->extractAvailableFunds($accData, (float) ($adAccount->balance ?? 0.00));
                $lifetimeSpend = isset($accData['amount_spent']) ? ((float) $accData['amount_spent'] / 100) : (float) ($adAccount->lifetime_spend ?? 0.00);

                $metaBusinessId = $adAccount->meta_business_id;
                if (!empty($accData['business']['id']) && !empty($accData['business']['name'])) {
                    $metaBiz = MetaBusiness::updateOrCreate(
                        ['business_id' => $accData['business']['id']],
                        [
                            'meta_connection_id' => $adAccount->meta_connection_id,
                            'name' => $accData['business']['name'],
                            'verification_status' => $accData['business']['verification_status'] ?? 'verified',
                        ]
                    );
                    $metaBusinessId = $metaBiz->id;
                }

                $adAccount->update([
                    'meta_business_id' => $metaBusinessId,
                    'spend_limit' => $spendLimit,
                    'balance' => $availableBalance,
                    'lifetime_spend' => $lifetimeSpend,
                    'currency' => $accData['currency'] ?? $adAccount->currency,
                    'last_synced_at' => now(),
                ]);
                $adAccount->meta_business_id = $metaBusinessId;
                $adAccount->spend_limit = $spendLimit;
                $adAccount->balance = $availableBalance;
                $adAccount->lifetime_spend = $lifetimeSpend;
            }

            // 2. Fetch all campaigns cleanly (without duplicate nested insights parameter)
            $allCampaignData = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/act_{$rawAccId}/campaigns", [
                'access_token' => $token,
                'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining',
                'limit' => 100,
            ], 10);

            // 3. Fetch lifetime and today campaign-level insights
            $campMaxInsights = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                'access_token' => $token,
                'date_preset' => 'maximum',
                'level' => 'campaign',
                'fields' => 'campaign_id,campaign_name,spend,impressions,reach,clicks,actions',
                'limit' => 500,
            ], 5);
            $maxInsightsByCamp = [];
            foreach ($campMaxInsights as $row) {
                if (!empty($row['campaign_id'])) {
                    $maxInsightsByCamp[(string) $row['campaign_id']] = $row;
                }
            }

            if (!empty($allCampaignData)) {
                // Delete deleted/archived campaigns from local db
                Campaign::where('ad_account_id', $adAccount->id)
                    ->whereIn('status', ['archived', 'ARCHIVED', 'Archived', 'deleted', 'DELETED'])
                    ->delete();

                foreach ($allCampaignData as $c) {
                    $rawStatus = strtolower($c['effective_status'] ?? ($c['status'] ?? 'active'));
                    if (in_array($rawStatus, ['deleted', 'archived'])) {
                        continue;
                    }

                    $rawCampId = (string) $c['id'];
                    $campKey = 'cmp_' . $rawCampId;

                    $maxRow = $maxInsightsByCamp[$rawCampId] ?? [];
                    $spend = isset($maxRow['spend']) ? (float) $maxRow['spend'] : 0.00;
                    $reach = isset($maxRow['reach']) ? (int) $maxRow['reach'] : 0;
                    $impressions = isset($maxRow['impressions']) ? (int) $maxRow['impressions'] : 0;

                    $dailyBudget = isset($c['daily_budget']) ? ((float) $c['daily_budget'] / 100) : 0.00;
                    $lifetimeBudget = isset($c['lifetime_budget']) ? ((float) $c['lifetime_budget'] / 100) : 0.00;

                    // Actual Telegram join is the single source of truth for subscribers
                    $existingCamp = Campaign::where('ad_account_id', $adAccount->id)
                        ->where(function($q) use ($campKey, $rawCampId) {
                            $q->where('campaign_id', $campKey)->orWhere('campaign_id', $rawCampId);
                        })->first();

                    $actualSubscribers = $existingCamp 
                        ? (\App\Models\Conversion::where('campaign_id', $existingCamp->id)->where('status', 'verified')->count() ?: $existingCamp->telegramEvents()->where('event_type', 'join')->count())
                        : 0;
                    $costPerSub = $actualSubscribers > 0 ? round(($spend * 0.82) / $actualSubscribers, 2) : 0.00;

                    $status = ucfirst($rawStatus);

                    $campaign = Campaign::updateOrCreate(
                        ['campaign_id' => $campKey],
                        [
                            'client_id' => $adAccount->client_id,
                            'ad_account_id' => $adAccount->id,
                            'name' => $c['name'] ?? ("Campaign " . $rawCampId),
                            'slug' => Str::slug($c['name'] ?? ("campaign-" . $rawCampId)),
                            'outcome' => in_array($c['objective'] ?? '', ['OUTCOME_LEADS', 'LEADS', 'CONVERSIONS', 'MESSAGES']) ? 'Subscribers' : 'Engagement',
                            'objective' => $c['objective'] ?? 'OUTCOME_LEADS',
                            'optimization_goal' => 'OFFSITE_CONVERSIONS',
                            'optimization_event' => 'Subscribe',
                            'billing_event' => 'IMPRESSIONS',
                            'conversion_location' => 'Telegram Channel',
                            'status' => $status,
                            'spend' => $spend,
                            'budget' => $lifetimeBudget,
                            'active_daily_budget' => in_array(strtolower($status), ['active', '1']) ? $dailyBudget : 0.00,
                            'reach' => $reach,
                            'impressions' => $impressions,
                            'subscribers' => $actualSubscribers,
                            'cost_per_subscriber' => $costPerSub,
                        ]
                    );

                    $syncedCampaigns[] = $campaign;
                }

                $adAccount->update(['last_synced_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::warning("Meta Graph API Campaigns Error for account {$adAccount->account_id}: " . $e->getMessage());
        }

        return $syncedCampaigns;
    }

    /**
     * Get live, account-specific Meta Ads metrics for Client Overview.
     * Enforces client/account isolation, real lifetime spend, real today's spend in account timezone,
     * and accurate campaign count with pagination.
     */
    public function getAdAccountMetrics(AdAccount $adAccount, bool $forceRefresh = false, string $dateRange = 'lifetime'): array
    {
        $clientId = $adAccount->client_id ?? 0;
        $cacheKey = "meta_analytics:client_{$clientId}:acc_{$adAccount->id}:range_{$dateRange}";
        $fallbackKey = "meta_analytics:client_{$clientId}:acc_{$adAccount->id}";

        if (!$forceRefresh) {
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }
            if ($dateRange === 'lifetime' && Cache::has($fallbackKey)) {
                return Cache::get($fallbackKey);
            }
        }

        $token = $this->getActiveAccessToken($adAccount);

        // Baseline / Database values for this exact account
        $campaigns = Campaign::where('ad_account_id', $adAccount->id)
            ->whereNotIn('status', ['archived', 'ARCHIVED', 'Archived', 'deleted', 'DELETED'])
            ->get();
        $campaignIds = $campaigns->pluck('id');
        $currency = $adAccount->currency ?? 'INR';
        $currencySymbol = $adAccount->currency_symbol ?? '₹';
        $timezone = $adAccount->timezone ?? 'Asia/Kolkata';

        $campaignSpend = (float) $campaigns->sum('spend');
        $spendTotal = $campaignSpend > 0 ? $campaignSpend : (float) ($adAccount->lifetime_spend ?? 0);
        $spendToday = 0.00; // Strictly ₹0 by default if no spend today

        // Initial baseline from database campaigns (used when no token / API query is present)
        if ($dateRange === 'lifetime') {
            $scopedSpend = $campaignSpend;
            $scopedImpressions = (int) $campaigns->sum('impressions');
            $scopedReach = (int) $campaigns->sum('reach');
            $scopedClicks = (int) CampaignInsight::whereIn('campaign_id', $campaignIds)->sum('clicks');
            $scopedLeads = (int) $campaigns->sum('subscribers');
        } else {
            // For date-scoped queries like today, yesterday, last_7_days, last_30_days, this_month:
            // Default baseline is strictly 0.00 (not the entire lifetime spend of the account!)
            $scopedSpend = 0.00;
            $scopedImpressions = 0;
            $scopedReach = 0;
            $scopedClicks = 0;
            $scopedLeads = 0;
        }
        $campaignsCount = $campaigns->count();

        // Attempt Live Meta Graph API query
        if (!empty($token)) {
            try {
                $version = $this->getGraphApiVersion();
                $rawAccId = str_replace('act_', '', $adAccount->account_id);

                // 1. Account Metadata & Lifetime Spend
                $accRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/act_{$rawAccId}", [
                    'access_token' => $token,
                    'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,is_prepay_account,funding_source_details,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
                ]);

                if ($accRes->successful() && !empty($accRes->json())) {
                    $accData = $accRes->json();
                    if (!empty($accData['timezone_name'])) {
                        $timezone = $accData['timezone_name'];
                    }
                    if (isset($accData['amount_spent'])) {
                        $spendTotal = (float) $accData['amount_spent'] / 100;
                    }
                    if (!empty($accData['currency'])) {
                        $currency = $accData['currency'];
                        $adAccount->currency = $currency;
                        $currencySymbol = $adAccount->currency_symbol;
                    }
                    $spendCap = isset($accData['spend_cap']) ? ((float) $accData['spend_cap'] / 100) : (isset($accData['spend_limit']) ? ((float) $accData['spend_limit'] / 100) : (float) ($adAccount->spend_limit ?? 0));
                    $availableBalance = $this->extractAvailableFunds($accData, (float) ($adAccount->balance ?? 0.00));

                    $metaBusinessId = $adAccount->meta_business_id;
                    if (!empty($accData['business']['id']) && !empty($accData['business']['name'])) {
                        $metaBiz = MetaBusiness::updateOrCreate(
                            ['business_id' => $accData['business']['id']],
                            [
                                'meta_connection_id' => $adAccount->meta_connection_id,
                                'name' => $accData['business']['name'],
                                'verification_status' => $accData['business']['verification_status'] ?? 'verified',
                            ]
                        );
                        $metaBusinessId = $metaBiz->id;
                    }

                    $adAccount->update([
                        'meta_business_id' => $metaBusinessId,
                        'spend_limit' => $spendCap,
                        'balance' => $availableBalance,
                        'lifetime_spend' => $spendTotal,
                        'currency' => $currency,
                        'last_synced_at' => now(),
                    ]);
                    $adAccount->meta_business_id = $metaBusinessId;
                    $adAccount->spend_limit = $spendCap;
                    $adAccount->balance = $availableBalance;
                    $adAccount->lifetime_spend = $spendTotal;
                } else {
                    Log::warning("Meta Graph API error fetching ad account metadata for act_{$rawAccId}: " . ($accRes->body() ?: 'Empty response'));
                }

                // 2. Date-Scoped Reporting Insights for selected date range
                $presetMap = [
                    'today' => 'today',
                    'yesterday' => 'yesterday',
                    'last_7_days' => 'last_7d',
                    'last_30_days' => 'last_30d',
                    'this_month' => 'this_month',
                    'lifetime' => 'maximum',
                ];
                $metaPreset = $presetMap[$dateRange] ?? 'last_30d';
                $todayDateStr = \Carbon\Carbon::now($timezone)->toDateString();

                $scopedRes = Http::withoutVerifying()->timeout(12)->get("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                    'access_token' => $token,
                    'date_preset' => $metaPreset,
                    'fields' => 'spend,impressions,reach,clicks,cpc,cpm,ctr,actions',
                ]);

                if ($scopedRes->successful()) {
                    $scopedData = $scopedRes->json('data')[0] ?? null;
                    if ($scopedData) {
                        $scopedSpend = (float) ($scopedData['spend'] ?? 0.00);
                        $scopedImpressions = (int) ($scopedData['impressions'] ?? 0);
                        $scopedReach = (int) ($scopedData['reach'] ?? 0);
                        $scopedClicks = (int) ($scopedData['clicks'] ?? 0);
                        $scopedLeads = 0;
                        if (!empty($scopedData['actions'])) {
                            foreach ($scopedData['actions'] as $act) {
                                if (in_array($act['action_type'] ?? '', ['lead', 'onsite_conversion.subscribe', 'subscribe'])) {
                                    $scopedLeads += (int) ($act['value'] ?? 0);
                                }
                            }
                        }
                    } else {
                        // Meta API successfully returned 200 OK with empty data: [] -> strictly 0 delivery!
                        $scopedSpend = 0.00;
                        $scopedImpressions = 0;
                        $scopedReach = 0;
                        $scopedClicks = 0;
                        $scopedLeads = 0;
                    }
                } else {
                    Log::warning("Meta Graph API error fetching scoped insights for act_{$rawAccId} [{$metaPreset}]: " . ($scopedRes->body() ?: 'Empty response'));
                }

                // If dateRange is 'today' and returned 0, try explicit time_range in account timezone
                if ($dateRange === 'today' && $scopedSpend == 0.00) {
                    $trRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                        'access_token' => $token,
                        'time_range' => json_encode(['since' => $todayDateStr, 'until' => $todayDateStr]),
                        'fields' => 'spend,impressions,reach,clicks,cpc,cpm,ctr,actions',
                    ]);
                    if ($trRes->successful() && !empty($trRes->json('data'))) {
                        $trData = $trRes->json('data')[0] ?? [];
                        if (isset($trData['spend']) && (float) $trData['spend'] > 0) {
                            $scopedSpend = (float) $trData['spend'];
                            $scopedImpressions = (int) ($trData['impressions'] ?? 0);
                            $scopedReach = (int) ($trData['reach'] ?? 0);
                            $scopedClicks = (int) ($trData['clicks'] ?? 0);
                        }
                    }
                }

                // 3. TODAY's Insights in the account's configured timezone
                if ($dateRange === 'today') {
                    $spendToday = $scopedSpend;
                } else {
                    $todayRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                        'access_token' => $token,
                        'date_preset' => 'today',
                        'fields' => 'spend,impressions,reach,clicks,actions',
                    ]);

                    if ($todayRes->successful() && !empty($todayRes->json('data'))) {
                        $todayData = $todayRes->json('data')[0] ?? [];
                        $spendToday = (float) ($todayData['spend'] ?? 0.00);
                    } else {
                        // Fallback with explicit today time_range
                        $trTodayRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                            'access_token' => $token,
                            'time_range' => json_encode(['since' => $todayDateStr, 'until' => $todayDateStr]),
                            'fields' => 'spend,impressions,reach,clicks,actions',
                        ]);
                        if ($trTodayRes->successful() && !empty($trTodayRes->json('data'))) {
                            $trData = $trTodayRes->json('data')[0] ?? [];
                            $spendToday = (float) ($trData['spend'] ?? 0.00);
                        } else {
                            $spendToday = 0.00;
                        }
                    }
                }

                // 4. Lifetime Spend Reconciliation (Ensure lifetime spend is accurate and >= date-scoped / today's spend)
                if ($dateRange === 'lifetime' && $scopedSpend > 0) {
                    $spendTotal = max($spendTotal, $scopedSpend);
                }
                if ($spendTotal < $spendToday) {
                    $spendTotal = max($spendTotal, $spendToday);
                }
                if ($spendTotal == 0 && $dateRange !== 'lifetime') {
                    $maxRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                        'access_token' => $token,
                        'date_preset' => 'maximum',
                        'fields' => 'spend',
                    ]);
                    if ($maxRes->successful() && !empty($maxRes->json('data'))) {
                        $maxSpend = (float) ($maxRes->json('data')[0]['spend'] ?? 0.00);
                        if ($maxSpend > 0) {
                            $spendTotal = max($spendTotal, $maxSpend);
                        }
                    }
                }
                $spendTotal = max($spendTotal, $campaignSpend, $spendToday);
                if ($spendTotal > (float) ($adAccount->lifetime_spend ?? 0)) {
                    $adAccount->update(['lifetime_spend' => $spendTotal]);
                    $adAccount->lifetime_spend = $spendTotal;
                }

                // 5. Fetch ALL Campaigns cleanly with Pagination (Clean fields without duplicate insights parameter)
                $allCampaigns = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/act_{$rawAccId}/campaigns", [
                    'access_token' => $token,
                    'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining',
                    'limit' => 100,
                ], 10);

                // 6. Fetch campaign-level insights for today, maximum (lifetime), and scoped date range
                $campTodayInsights = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                    'access_token' => $token,
                    'date_preset' => 'today',
                    'level' => 'campaign',
                    'fields' => 'campaign_id,campaign_name,spend,impressions,reach,clicks,actions',
                    'limit' => 500,
                ], 5);
                $todayInsightsByCamp = [];
                foreach ($campTodayInsights as $row) {
                    if (!empty($row['campaign_id'])) {
                        $todayInsightsByCamp[(string) $row['campaign_id']] = $row;
                    }
                }

                $campMaxInsights = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                    'access_token' => $token,
                    'date_preset' => 'maximum',
                    'level' => 'campaign',
                    'fields' => 'campaign_id,campaign_name,spend,impressions,reach,clicks,actions',
                    'limit' => 500,
                ], 5);
                $maxInsightsByCamp = [];
                foreach ($campMaxInsights as $row) {
                    if (!empty($row['campaign_id'])) {
                        $maxInsightsByCamp[(string) $row['campaign_id']] = $row;
                    }
                }

                $scopedInsightsByCamp = [];
                if ($metaPreset !== 'today' && $metaPreset !== 'maximum') {
                    $campScopedInsights = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/act_{$rawAccId}/insights", [
                        'access_token' => $token,
                        'date_preset' => $metaPreset,
                        'level' => 'campaign',
                        'fields' => 'campaign_id,campaign_name,spend,impressions,reach,clicks,actions',
                        'limit' => 500,
                    ], 5);
                    foreach ($campScopedInsights as $row) {
                        if (!empty($row['campaign_id'])) {
                            $scopedInsightsByCamp[(string) $row['campaign_id']] = $row;
                        }
                    }
                }

                $sumCampTodaySpend = 0.00;
                $sumCampTodayImpressions = 0;
                $sumCampTodayReach = 0;
                $sumCampTodayClicks = 0;

                $sumCampScopedSpend = 0.00;
                $sumCampScopedImpressions = 0;
                $sumCampScopedReach = 0;
                $sumCampScopedClicks = 0;

                $sumCampLifetimeSpend = 0.00;

                if (!empty($allCampaigns)) {
                    $validCampaigns = [];
                    foreach ($allCampaigns as $c) {
                        $rawStatus = strtolower($c['effective_status'] ?? ($c['status'] ?? ''));
                        if (!in_array($rawStatus, ['deleted', 'archived'])) {
                            $validCampaigns[] = $c;
                        }
                    }

                    $campaignsCount = count($validCampaigns);
                    foreach ($validCampaigns as $c) {
                        $rawStatus = strtolower($c['effective_status'] ?? ($c['status'] ?? 'paused'));
                        $rawCampId = (string) $c['id'];
                        $campKey = 'cmp_' . $rawCampId;

                        // Lifetime metrics from maximum insights
                        $campMax = $maxInsightsByCamp[$rawCampId] ?? [];
                        $cLifetimeSpend = isset($campMax['spend']) ? (float) $campMax['spend'] : 0.00;
                        $cReach = isset($campMax['reach']) ? (int) $campMax['reach'] : 0;
                        $cImpressions = isset($campMax['impressions']) ? (int) $campMax['impressions'] : 0;

                        // Today metrics from today insights
                        $campToday = $todayInsightsByCamp[$rawCampId] ?? [];
                        $cTodaySpend = isset($campToday['spend']) ? (float) $campToday['spend'] : 0.00;
                        $cTodayImpressions = isset($campToday['impressions']) ? (int) $campToday['impressions'] : 0;
                        $cTodayReach = isset($campToday['reach']) ? (int) $campToday['reach'] : 0;
                        $cTodayClicks = isset($campToday['clicks']) ? (int) $campToday['clicks'] : 0;

                        // Scoped metrics for requested date range
                        $campScoped = ($metaPreset === 'today') ? $campToday : (($metaPreset === 'maximum') ? $campMax : ($scopedInsightsByCamp[$rawCampId] ?? []));
                        $cScopedSpend = isset($campScoped['spend']) ? (float) $campScoped['spend'] : ($metaPreset === 'today' ? $cTodaySpend : 0.00);
                        $cScopedImpressions = isset($campScoped['impressions']) ? (int) $campScoped['impressions'] : ($metaPreset === 'today' ? $cTodayImpressions : 0);
                        $cScopedReach = isset($campScoped['reach']) ? (int) $campScoped['reach'] : ($metaPreset === 'today' ? $cTodayReach : 0);
                        $cScopedClicks = isset($campScoped['clicks']) ? (int) $campScoped['clicks'] : ($metaPreset === 'today' ? $cTodayClicks : 0);

                        $cDailyBudget = isset($c['daily_budget']) ? ((float) $c['daily_budget'] / 100) : 0.00;
                        $cLifetimeBudget = isset($c['lifetime_budget']) ? ((float) $c['lifetime_budget'] / 100) : 0.00;

                        $status = ucfirst($rawStatus);

                        // Update or create in Database
                        $existingCamp = Campaign::where('ad_account_id', $adAccount->id)
                            ->where(function($q) use ($campKey, $rawCampId) {
                                $q->where('campaign_id', $campKey)->orWhere('campaign_id', $rawCampId);
                            })->first();

                        $actualSubscribers = $existingCamp 
                            ? (\App\Models\Conversion::where('campaign_id', $existingCamp->id)->where('status', 'verified')->count() ?: $existingCamp->telegramEvents()->where('event_type', 'join')->count())
                            : 0;
                        $costPerSub = $actualSubscribers > 0 ? round(($cLifetimeSpend * 0.82) / $actualSubscribers, 2) : 0.00;

                        Campaign::updateOrCreate(
                            ['campaign_id' => $campKey],
                            [
                                'client_id' => $adAccount->client_id,
                                'ad_account_id' => $adAccount->id,
                                'name' => $c['name'] ?? ("Campaign " . $rawCampId),
                                'slug' => Str::slug($c['name'] ?? ("campaign-" . $rawCampId)),
                                'outcome' => in_array($c['objective'] ?? '', ['OUTCOME_LEADS', 'LEADS', 'CONVERSIONS', 'MESSAGES']) ? 'Subscribers' : 'Engagement',
                                'objective' => $c['objective'] ?? 'OUTCOME_LEADS',
                                'optimization_goal' => 'OFFSITE_CONVERSIONS',
                                'optimization_event' => 'Subscribe',
                                'billing_event' => 'IMPRESSIONS',
                                'conversion_location' => 'Telegram Channel',
                                'status' => $status,
                                'spend' => $cLifetimeSpend,
                                'reach' => $cReach,
                                'impressions' => $cImpressions,
                                'budget' => $cLifetimeBudget,
                                'active_daily_budget' => in_array(strtolower($status), ['active', '1']) ? $cDailyBudget : 0.00,
                                'subscribers' => $actualSubscribers,
                                'cost_per_subscriber' => $costPerSub,
                            ]
                        );

                        // Accumulate sums
                        $sumCampTodaySpend += $cTodaySpend;
                        $sumCampTodayImpressions += $cTodayImpressions;
                        $sumCampTodayReach += $cTodayReach;
                        $sumCampTodayClicks += $cTodayClicks;

                        $sumCampScopedSpend += $cScopedSpend;
                        $sumCampScopedImpressions += $cScopedImpressions;
                        $sumCampScopedReach += $cScopedReach;
                        $sumCampScopedClicks += $cScopedClicks;

                        $sumCampLifetimeSpend += $cLifetimeSpend;
                    }
                }

                // 7. Direct campaign node insights fallback (if campaign insights level had delay on active ads)
                if ($sumCampTodaySpend == 0.00 && !empty($allCampaigns)) {
                    $activeCamps = array_filter($allCampaigns, fn($ac) => in_array(strtolower($ac['effective_status'] ?? ($ac['status'] ?? '')), ['active', '1']));
                    foreach (array_slice($activeCamps, 0, 10) as $ac) {
                        $cId = $ac['id'] ?? '';
                        if (!$cId) continue;
                        $dcRes = Http::withoutVerifying()->timeout(8)->get("{$this->baseUrl}/{$version}/{$cId}/insights", [
                            'access_token' => $token,
                            'date_preset' => 'today',
                            'fields' => 'spend,impressions,reach,clicks,actions',
                        ]);
                        if ($dcRes->successful() && !empty($dcRes->json('data'))) {
                            $dcRow = $dcRes->json('data')[0] ?? [];
                            $sumCampTodaySpend += (float) ($dcRow['spend'] ?? 0.00);
                            $sumCampTodayImpressions += (int) ($dcRow['impressions'] ?? 0);
                            $sumCampTodayReach += (int) ($dcRow['reach'] ?? 0);
                            $sumCampTodayClicks += (int) ($dcRow['clicks'] ?? 0);
                        }
                    }
                }

                // Reconcile spendToday
                $spendToday = max($spendToday, $sumCampTodaySpend);

                // 7. Real-Time Billing Delta Tracking (Instant 0-delay capture from amount_spent on act_{id})
                $todayBaselineKey = "meta_baseline_spend:acc_{$adAccount->id}:" . $todayDateStr;
                $baselineSpend = Cache::get($todayBaselineKey);
                if ($baselineSpend === null) {
                    $prevSpend = (float) ($adAccount->lifetime_spend ?? $spendTotal);
                    Cache::put($todayBaselineKey, $prevSpend, now()->endOfDay()->addHours(2));
                    $baselineSpend = $prevSpend;
                }

                if ($spendTotal > (float) $baselineSpend) {
                    $realtimeDeltaSpend = round($spendTotal - (float) $baselineSpend, 2);
                    if ($realtimeDeltaSpend > $spendToday) {
                        $spendToday = $realtimeDeltaSpend;
                    }
                }

                if ($dateRange === 'today') {
                    $scopedSpend = max($scopedSpend, $spendToday);
                    $scopedImpressions = max($scopedImpressions, $sumCampTodayImpressions, $campTodayImpressionsSum);
                    $scopedReach = max($scopedReach, $sumCampTodayReach, $campTodayReachSum);
                    $scopedClicks = max($scopedClicks, $sumCampTodayClicks, $campTodayClicksSum);
                } elseif ($dateRange === 'lifetime') {
                    $scopedSpend = max($scopedSpend, $spendTotal, $sumCampLifetimeSpend);
                    $spendTotal = max($spendTotal, $scopedSpend);
                } else {
                    if ($sumCampScopedSpend > 0) $scopedSpend = max($scopedSpend, $sumCampScopedSpend);
                    if ($sumCampScopedImpressions > 0) $scopedImpressions = max($scopedImpressions, $sumCampScopedImpressions);
                    if ($sumCampScopedReach > 0) $scopedReach = max($scopedReach, $sumCampScopedReach);
                    if ($sumCampScopedClicks > 0) $scopedClicks = max($scopedClicks, $sumCampScopedClicks);
                }

                // Fallback for lifetime / last 30 days if still 0
                if ($scopedSpend == 0.00 && in_array($dateRange, ['last_30_days', 'this_month', 'lifetime']) && $spendTotal > 0) {
                    $scopedSpend = $spendTotal;
                    if ($scopedImpressions == 0) $scopedImpressions = (int) $campaigns->sum('impressions');
                    if ($scopedReach == 0) $scopedReach = (int) $campaigns->sum('reach');
                }
            } catch (\Throwable $e) {
                Log::warning("Meta Graph API live analytics error for account {$adAccount->account_id}: " . $e->getMessage());
            }
        }

        $ctr = $scopedImpressions > 0 ? round(($scopedClicks / $scopedImpressions) * 100, 2) : 0.00;
        $cpc = ($scopedClicks > 0 && $scopedSpend > 0) ? round($scopedSpend / $scopedClicks, 2) : 0.00;
        $cpm = $scopedImpressions > 0 ? round(($scopedSpend / $scopedImpressions) * 1000, 2) : 0.00;

        $metrics = [
            'connected' => true,
            'account_name' => $adAccount->name,
            'account_id' => $adAccount->account_id,
            'business_name' => $adAccount->metaBusiness?->name ?? null,
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'status' => $adAccount->status ?? 'Active',
            'timezone' => $timezone,
            'last_sync' => $adAccount->last_synced_at ? $adAccount->last_synced_at->diffForHumans() : 'Just now',
            'date_range' => $dateRange,
            'spend_scoped' => $scopedSpend,
            'spend_total' => $spendTotal,
            'lifetime_spend' => $spendTotal,
            'spend_today' => $spendToday,
            'spend_month' => $scopedSpend,
            'clicks' => $scopedClicks,
            'impressions' => $scopedImpressions,
            'reach' => $scopedReach,
            'leads' => $scopedLeads,
            'ctr' => $ctr,
            'cpc' => $cpc,
            'cpm' => $cpm,
            'spend_limit' => (float) ($adAccount->spend_limit ?? 0.00),
            'balance' => (float) ($adAccount->balance ?? 0.00),
            'remaining_fund' => (float) ($adAccount->balance ?? 0.00),
            'campaigns_count' => $campaignsCount,
        ];

        Cache::put($cacheKey, $metrics, 10);
        if ($dateRange === 'lifetime') {
            Cache::put($fallbackKey, $metrics, 10);
        }

        return $metrics;
    }

    /**
     * Sync Campaigns and objectives from Meta Graph API
     */
    public function syncCampaigns(array $adAccounts): void
    {
        if (empty($adAccounts)) {
            return;
        }

        // Prioritize accounts assigned to clients to avoid gateway timeouts
        $priorityAccounts = array_filter($adAccounts, fn($acc) => !empty($acc->client_id));
        if (empty($priorityAccounts)) {
            $priorityAccounts = array_slice($adAccounts, 0, 5);
        }

        foreach ($priorityAccounts as $adAccount) {
            $this->getAdAccountMetrics($adAccount, true);
        }
    }

    /**
     * Extract actual available funds / balance from Meta Ad Account data.
     * Prioritizes funding_source_details (prepaid stored balance) over unsettled bill amount.
     */
    public function extractAvailableFunds(array $accData, float $fallback = 0.00): float
    {
        // 1. Check funding_source_details for explicit prepaid stored balance / amount
        if (!empty($accData['funding_source_details'])) {
            $fsd = $accData['funding_source_details'];
            
            // Single object with 'amount'
            if (isset($fsd['amount']) && is_numeric($fsd['amount'])) {
                $amt = abs((float) $fsd['amount']) / 100;
                if ($amt > 0) return round($amt, 2);
            }

            // Nested details with 'amount'
            if (isset($fsd['details']['amount']) && is_numeric($fsd['details']['amount'])) {
                $amt = abs((float) $fsd['details']['amount']) / 100;
                if ($amt > 0) return round($amt, 2);
            }

            // Array of funding sources (e.g. STORED_BALANCE, coupons)
            if (is_array($fsd)) {
                $totalFunds = 0.0;
                $foundPrepay = false;
                foreach ($fsd as $item) {
                    if (is_array($item)) {
                        if (isset($item['amount']) && is_numeric($item['amount'])) {
                            $totalFunds += abs((float) $item['amount']) / 100;
                            $foundPrepay = true;
                        } elseif (isset($item['details']['amount']) && is_numeric($item['details']['amount'])) {
                            $totalFunds += abs((float) $item['details']['amount']) / 100;
                            $foundPrepay = true;
                        }
                    }
                }
                if ($foundPrepay && $totalFunds > 0) {
                    return round($totalFunds, 2);
                }
            }
        }

        // 2. Check spend_cap vs amount_spent if spend_cap is set
        $spendCap = isset($accData['spend_cap']) ? ((float) $accData['spend_cap'] / 100) : (isset($accData['spend_limit']) ? ((float) $accData['spend_limit'] / 100) : 0.00);
        $amountSpent = isset($accData['amount_spent']) ? ((float) $accData['amount_spent'] / 100) : 0.00;

        $isPrepay = !empty($accData['is_prepay_account']) || ($accData['is_prepay_account'] ?? false) === true;
        if ($spendCap > 0 && $spendCap >= $amountSpent) {
            $diff = $spendCap - $amountSpent;
            if ($diff > 0) {
                return round($diff, 2);
            }
        }

        // 3. Fallback to balance field from Meta API if non-zero
        if (isset($accData['balance']) && is_numeric($accData['balance'])) {
            $rawBal = abs((float) $accData['balance']) / 100;
            if ($rawBal > 0) {
                return round($rawBal, 2);
            }
        }

        return round($fallback, 2);
    }
}
