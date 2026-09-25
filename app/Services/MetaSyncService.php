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
            $balance = isset($acc['balance']) ? ((float) $acc['balance'] / 100) : 0.00;
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
            'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
            'limit' => 100,
        ], 25);

        foreach ($directAccounts as $acc) {
            $processAccount($acc);
        }

        // 2. Assigned Ad Accounts (/me/assigned_ad_accounts with multi-page traversal)
        $assignedAccounts = $this->fetchPagedGraphApi("{$this->baseUrl}/{$version}/me/assigned_ad_accounts", [
            'access_token' => $token,
            'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
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
                    'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
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

        $token = $conn?->access_token ?: Setting::get('meta_system_user_token');
        $version = $this->getGraphApiVersion();

        if ($token) {
            try {
                $res = Http::withoutVerifying()->timeout(12)->get("{$this->baseUrl}/{$version}/{$actId}", [
                    'access_token' => $token,
                    'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
                ]);

                if ($res->successful() && !empty($res->json())) {
                    $acc = $res->json();
                    $statusNum = $acc['account_status'] ?? 1;
                    $status = ($statusNum === 1) ? 'Active' : (($statusNum === 2) ? 'Disabled' : 'Unsettled');
                    $spendLimit = isset($acc['spend_cap']) ? ((float) $acc['spend_cap'] / 100) : (isset($acc['spend_limit']) ? ((float) $acc['spend_limit'] / 100) : 0.00);
                    $balance = isset($acc['balance']) ? ((float) $acc['balance'] / 100) : 0.00;
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
     * Fetches ALL campaigns (ACTIVE, PAUSED, ARCHIVED, etc.) with pagination support
     */
    public function syncSingleAdAccount(AdAccount $adAccount): array
    {
        $connection = $adAccount->metaConnection ?? MetaConnection::first();
        $token = $connection?->access_token ?? \App\Models\Setting::get('meta_system_user_token');

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
                'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
            ]);

            if ($accRes->successful() && !empty($accRes->json())) {
                $accData = $accRes->json();
                $spendLimit = isset($accData['spend_cap']) ? ((float) $accData['spend_cap'] / 100) : (isset($accData['spend_limit']) ? ((float) $accData['spend_limit'] / 100) : 0.00);
                $rawBalance = isset($accData['balance']) ? ((float) $accData['balance'] / 100) : 0.00;
                $lifetimeSpend = isset($accData['amount_spent']) ? ((float) $accData['amount_spent'] / 100) : (float) ($adAccount->lifetime_spend ?? 0.00);
                $availableBalance = ($rawBalance > 0) ? $rawBalance : (($spendLimit > 0 && $spendLimit >= $lifetimeSpend) ? ($spendLimit - $lifetimeSpend) : 0.00);

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

            // 2. Sync campaigns and insights from Meta with pagination (Strictly active and paused campaigns, exclude archived)
            Campaign::where('ad_account_id', $adAccount->id)
                ->whereIn('status', ['archived', 'ARCHIVED', 'Archived'])
                ->delete();

            $allCampaignData = [];
            $nextUrl = "{$this->baseUrl}/{$version}/act_{$rawAccId}/campaigns";
            $params = [
                'access_token' => $token,
                'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining,insights{reach,impressions,spend,actions}',
                'effective_status' => '["ACTIVE","PAUSED","IN_PROCESS","WITH_ISSUES"]',
                'limit' => 100,
            ];

            $pages = 0;
            while ($nextUrl && $pages < 5) {
                $pages++;
                $res = $pages === 1
                    ? Http::withoutVerifying()->timeout(15)->get($nextUrl, $params)
                    : Http::withoutVerifying()->timeout(15)->get($nextUrl);

                if ($res->successful() && !empty($res->json('data'))) {
                    foreach ($res->json('data') as $c) {
                        $rawStatus = strtolower($c['effective_status'] ?? ($c['status'] ?? ''));
                        if (!in_array($rawStatus, ['archived', 'deleted'])) {
                            $allCampaignData[] = $c;
                        }
                    }
                    $nextUrl = $res->json('paging.next');
                } else {
                    break;
                }
            }

            if (!empty($allCampaignData)) {
                foreach ($allCampaignData as $c) {
                    $rawStatus = strtolower($c['effective_status'] ?? ($c['status'] ?? 'active'));
                    if (in_array($rawStatus, ['archived', 'deleted'])) {
                        continue;
                    }

                    $insights = $c['insights']['data'][0] ?? [];
                    $spend = (float) ($insights['spend'] ?? 0);
                    $reach = (int) ($insights['reach'] ?? 0);
                    $impressions = (int) ($insights['impressions'] ?? 0);
                    $dailyBudget = isset($c['daily_budget']) ? ((float) $c['daily_budget'] / 100) : 0.00;
                    $lifetimeBudget = isset($c['lifetime_budget']) ? ((float) $c['lifetime_budget'] / 100) : 0.00;

                    // Actual Telegram join is the single source of truth for subscribers (never Meta actions)
                    $existingCamp = Campaign::where('campaign_id', 'cmp_' . $c['id'])->orWhere('campaign_id', $c['id'])->first();
                    $actualSubscribers = $existingCamp 
                        ? (\App\Models\Conversion::where('campaign_id', $existingCamp->id)->where('status', 'verified')->count() ?: $existingCamp->telegramEvents()->where('event_type', 'join')->count())
                        : 0;
                    $costPerSub = $actualSubscribers > 0 ? round($spend / $actualSubscribers, 2) : 0.00;

                    $status = ucfirst($rawStatus);

                    $campaign = Campaign::updateOrCreate(
                        ['campaign_id' => 'cmp_' . $c['id']],
                        [
                            'client_id' => $adAccount->client_id,
                            'ad_account_id' => $adAccount->id,
                            'name' => $c['name'],
                            'slug' => \Illuminate\Support\Str::slug($c['name']),
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

        $connection = $adAccount->metaConnection ?? MetaConnection::first();
        $token = $connection?->access_token ?? \App\Models\Setting::get('meta_system_user_token');

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
        $scopedSpend = $campaignSpend;
        $scopedImpressions = (int) $campaigns->sum('impressions');
        $scopedReach = (int) $campaigns->sum('reach');
        $scopedClicks = (int) CampaignInsight::whereIn('campaign_id', $campaignIds)->sum('clicks');
        $scopedLeads = (int) $campaigns->sum('subscribers');
        $campaignsCount = $campaigns->count();

        // Attempt Live Meta Graph API query
        if (!empty($token)) {
            try {
                $version = $this->getGraphApiVersion();
                $rawAccId = str_replace('act_', '', $adAccount->account_id);

                // 1. Account Metadata & Lifetime Spend
                $accRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/act_{$rawAccId}", [
                    'access_token' => $token,
                    'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
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
                    $rawBalance = isset($accData['balance']) ? ((float) $accData['balance'] / 100) : 0.00;
                    $availableBalance = ($rawBalance > 0) ? $rawBalance : (($spendCap > 0 && $spendCap >= $spendTotal) ? ($spendCap - $spendTotal) : 0.00);

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
                        $spendToday = 0.00;
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

                // 5. Dynamic Campaigns with Pagination (Active & Paused campaigns only, exclude archived)
                $allCampaigns = [];
                $nextUrl = "{$this->baseUrl}/{$version}/act_{$rawAccId}/campaigns";
                $params = [
                    'access_token' => $token,
                    'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining,insights{reach,impressions,spend,actions}',
                    'effective_status' => '["ACTIVE","PAUSED","IN_PROCESS","WITH_ISSUES"]',
                    'limit' => 100,
                ];

                $pageCount = 0;
                while ($nextUrl && $pageCount < 5) {
                    $pageCount++;
                    $cRes = Http::withoutVerifying()->timeout(15)->get($nextUrl, $params);
                    if ($cRes->successful() && !empty($cRes->json('data'))) {
                        $cData = $cRes->json('data');
                        foreach ($cData as $c) {
                            $rawStatus = strtolower($c['effective_status'] ?? ($c['status'] ?? ''));
                            if (!in_array($rawStatus, ['archived', 'deleted'])) {
                                $allCampaigns[] = $c;
                            }
                        }
                        $paging = $cRes->json('paging');
                        $nextUrl = $paging['next'] ?? null;
                        $params = [];
                    } else {
                        break;
                    }
                }

                if (!empty($allCampaigns)) {
                    $campaignsCount = count($allCampaigns);
                    foreach ($allCampaigns as $c) {
                        $cStatus = strtolower($c['effective_status'] ?? ($c['status'] ?? 'paused'));
                        if (in_array($cStatus, ['archived', 'deleted'])) {
                            continue;
                        }

                        $rawCampId = $c['id'];
                        $campInsights = $c['insights']['data'][0] ?? [];
                        $cSpend = isset($campInsights['spend']) ? (float) $campInsights['spend'] : 0.00;
                        $cReach = isset($campInsights['reach']) ? (int) $campInsights['reach'] : 0;
                        $cImpressions = isset($campInsights['impressions']) ? (int) $campInsights['impressions'] : 0;
                        $cDailyBudget = isset($c['daily_budget']) ? ((float) $c['daily_budget'] / 100) : 0.00;
                        $cLifetimeBudget = isset($c['lifetime_budget']) ? ((float) $c['lifetime_budget'] / 100) : 0.00;
                        $cBudgetRemaining = isset($c['budget_remaining']) ? ((float) $c['budget_remaining'] / 100) : 0.00;

                        $campModel = Campaign::updateOrCreate(
                            ['campaign_id' => $rawCampId],
                            [
                                'client_id' => $adAccount->client_id,
                                'ad_account_id' => $adAccount->id,
                                'name' => $c['name'] ?? "Campaign {$rawCampId}",
                                'slug' => Str::slug($c['name'] ?? "campaign-{$rawCampId}"),
                                'objective' => $c['objective'] ?? 'OUTCOME_LEADS',
                                'status' => $cStatus,
                                'spend' => $cSpend,
                                'reach' => $cReach,
                                'impressions' => $cImpressions,
                                'budget' => $cLifetimeBudget,
                                'active_daily_budget' => in_array($cStatus, ['active', '1']) ? $cDailyBudget : 0.00,
                            ]
                        );

                        if ($campModel && ($cSpend > 0 || $cReach > 0)) {
                            CampaignInsight::updateOrCreate(
                                [
                                    'campaign_id' => $campModel->id,
                                    'date' => now()->toDateString(),
                                ],
                                [
                                    'spend' => $cSpend,
                                    'reach' => $cReach,
                                    'impressions' => $cImpressions,
                                    'clicks' => (int) ($campInsights['clicks'] ?? 0),
                                    'actions' => json_encode($campInsights['actions'] ?? []),
                                ]
                            );
                        }
                    }
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
            $this->syncSingleAdAccount($adAccount);
        }
    }
}
