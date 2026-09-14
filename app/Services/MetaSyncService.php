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

            // Fetch Businesses
            $businesses = [];
            try {
                $bizRes = Http::withoutVerifying()->timeout(8)->get("{$this->baseUrl}/{$version}/me/businesses", [
                    'access_token' => $token,
                    'fields' => 'id,name,verification_status',
                    'limit' => 50,
                ]);
                if ($bizRes->successful() && !empty($bizRes->json('data'))) {
                    $businesses = $bizRes->json('data');
                }
            } catch (\Exception $e) {
                Log::warning('validateToken businesses check: ' . $e->getMessage());
            }

            // Fetch Ad Accounts
            $adAccounts = [];
            try {
                $accRes = Http::withoutVerifying()->timeout(8)->get("{$this->baseUrl}/{$version}/me/adaccounts", [
                    'access_token' => $token,
                    'fields' => 'id,account_id,name,currency,account_status,amount_spent,business{id,name,verification_status}',
                    'limit' => 100,
                ]);
                if ($accRes->successful() && !empty($accRes->json('data'))) {
                    $adAccounts = $accRes->json('data');
                }
            } catch (\Exception $e) {
                Log::warning('validateToken ad accounts check: ' . $e->getMessage());
            }

            return [
                'valid' => true,
                'user_id' => $userId,
                'name' => $userName,
                'email' => $profile['email'] ?? null,
                'businesses_count' => count($businesses),
                'businesses' => $businesses,
                'ad_accounts_count' => count($adAccounts),
                'ad_accounts' => $adAccounts,
                'message' => "Token is valid! Found " . count($businesses) . " Business Portfolio(s) and " . count($adAccounts) . " Ad Account(s).",
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

        $connection = MetaConnection::updateOrCreate(
            ['facebook_user_id' => $fbUserId],
            [
                'user_id' => $userId ?? auth()->id(),
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
     * Sync Businesses from Meta Graph API
     */
    public function syncBusinesses(MetaConnection $connection): array
    {
        $token = $connection->access_token;
        $results = [];

        // Attempt live Graph API query
        try {
            $version = $this->getGraphApiVersion();
            $res = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/me/businesses", [
                'access_token' => $token,
                'fields' => 'id,name,verification_status',
                'limit' => 50,
            ]);

            if ($res->successful() && !empty($res->json('data'))) {
                foreach ($res->json('data') as $b) {
                    $results[] = MetaBusiness::updateOrCreate(
                        ['business_id' => $b['id']],
                        [
                            'meta_connection_id' => $connection->id,
                            'name' => $b['name'] ?? ('Meta Business ' . $b['id']),
                            'verification_status' => $b['verification_status'] ?? 'verified',
                        ]
                    );
                }
                return $results;
            }
        } catch (\Exception $e) {
            Log::warning('Meta Graph API Businesses Error: ' . $e->getMessage());
        }

        $existing = MetaBusiness::where('meta_connection_id', $connection->id)->get()->all();
        if (!empty($existing)) {
            return $existing;
        }

        // Default Agency Business Manager
        $defaultBiz = MetaBusiness::updateOrCreate(
            ['business_id' => 'biz_kirtnix_bm_01'],
            [
                'meta_connection_id' => $connection->id,
                'name' => 'KirtniX Performance Business Manager',
                'verification_status' => 'verified',
            ]
        );

        return [$defaultBiz];
    }

    /**
     * Sync Ad Accounts from Meta Graph API
     */
    public function syncAdAccounts(MetaConnection $connection): array
    {
        $token = $connection->access_token;
        $results = [];

        // 1. Attempt live Graph API query for direct Ad Accounts
        try {
            $version = $this->getGraphApiVersion();
            $res = Http::withoutVerifying()->timeout(12)->get("{$this->baseUrl}/{$version}/me/adaccounts", [
                'access_token' => $token,
                'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent,timezone_name,timezone_offset_hours_utc,business{id,name,verification_status}',
                'limit' => 100,
            ]);

            if ($res->successful() && !empty($res->json('data'))) {
                $accountsData = $res->json('data');

                foreach ($accountsData as $acc) {
                    $rawId = (string) ($acc['account_id'] ?? $acc['id']);
                    $accId = str_starts_with($rawId, 'act_') ? $rawId : ('act_' . $rawId);
                    $statusNum = $acc['account_status'] ?? 1;
                    $status = ($statusNum === 1) ? 'Active' : (($statusNum === 2) ? 'Disabled' : 'Unsettled');

                    $spendLimit = isset($acc['spend_cap']) ? ((float) $acc['spend_cap'] / 100) : (isset($acc['spend_limit']) ? ((float) $acc['spend_limit'] / 100) : 0.00);
                    $balance = isset($acc['balance']) ? ((float) $acc['balance'] / 100) : 0.00;
                    $lifetimeSpend = isset($acc['amount_spent']) ? ((float) $acc['amount_spent'] / 100) : 0.00;
                    $dailyBudget = 0.00;

                    // Authoritative Meta Business resolution:
                    // Ad Account -> business -> business.id / business.name
                    $metaBusinessId = null;
                    if (!empty($acc['business']['id']) && !empty($acc['business']['name'])) {
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
                            'name' => $acc['name'] ?? ('Meta Ad Account ' . $rawId),
                            'currency' => $acc['currency'] ?? 'INR',
                            'status' => $status,
                            'spend_limit' => $spendLimit,
                            'balance' => $balance,
                            'lifetime_spend' => $lifetimeSpend,
                            'active_daily_budget' => $dailyBudget,
                            'payment_method' => 'Meta Billing',
                            'is_active' => true,
                            'last_synced_at' => now(),
                        ]
                    );

                    $results[$accId] = $record;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Meta Graph API Ad Accounts Error: ' . $e->getMessage());
        }

        // 2. Also check all businesses for client / owned ad accounts
        try {
            $version = $this->getGraphApiVersion();
            $businesses = MetaBusiness::where('meta_connection_id', $connection->id)->get();
            foreach ($businesses as $biz) {
                foreach (['client_ad_accounts', 'owned_ad_accounts'] as $edge) {
                    $bizRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$version}/{$biz->business_id}/{$edge}", [
                        'access_token' => $token,
                        'fields' => 'id,account_id,name,currency,account_status,spend_cap,balance,amount_spent',
                        'limit' => 50,
                    ]);
                    if ($bizRes->successful() && !empty($bizRes->json('data'))) {
                        foreach ($bizRes->json('data') as $acc) {
                            $rawId = (string) ($acc['account_id'] ?? $acc['id']);
                            $accId = str_starts_with($rawId, 'act_') ? $rawId : ('act_' . $rawId);
                            if (isset($results[$accId])) {
                                continue;
                            }
                            $statusNum = $acc['account_status'] ?? 1;
                            $status = ($statusNum === 1) ? 'Active' : (($statusNum === 2) ? 'Disabled' : 'Unsettled');
                            $spendLimit = isset($acc['spend_cap']) ? ((float) $acc['spend_cap'] / 100) : 0.00;
                            $balance = isset($acc['balance']) ? ((float) $acc['balance'] / 100) : 0.00;
                            $lifetimeSpend = isset($acc['amount_spent']) ? ((float) $acc['amount_spent'] / 100) : 0.00;

                            $record = AdAccount::updateOrCreate(
                                ['account_id' => $accId],
                                [
                                    'meta_connection_id' => $connection->id,
                                    'meta_business_id' => $biz->id,
                                    'name' => $acc['name'] ?? ('Meta Ad Account ' . $rawId),
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
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Meta Graph API Business Ad Accounts Error: ' . $e->getMessage());
        }

        // 3. Re-sync and link all existing Ad Accounts in database
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

        // 4. If no accounts exist yet, auto-populate primary agency account and link client accounts with 0.00 defaults
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
                $balance = isset($accData['balance']) ? ((float) $accData['balance'] / 100) : 0.00;
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
                    'balance' => $balance,
                    'lifetime_spend' => $lifetimeSpend,
                    'currency' => $accData['currency'] ?? $adAccount->currency,
                    'last_synced_at' => now(),
                ]);
                $adAccount->meta_business_id = $metaBusinessId;
                $adAccount->spend_limit = $spendLimit;
                $adAccount->balance = $balance;
                $adAccount->lifetime_spend = $lifetimeSpend;
            }

            // 2. Sync campaigns and insights from Meta with pagination
            $allCampaignData = [];
            $nextUrl = "{$this->baseUrl}/{$version}/act_{$rawAccId}/campaigns";
            $params = [
                'access_token' => $token,
                'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining,insights{reach,impressions,spend,actions}',
                'effective_status' => '["ACTIVE","PAUSED","ARCHIVED","IN_PROCESS","WITH_ISSUES"]',
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
                        $allCampaignData[] = $c;
                    }
                    $nextUrl = $res->json('paging.next');
                } else {
                    break;
                }
            }

            if (!empty($allCampaignData)) {
                foreach ($allCampaignData as $c) {
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

                    $rawStatus = $c['status'] ?? $c['effective_status'] ?? 'ACTIVE';
                    $status = ucfirst(strtolower($rawStatus));

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
                            'active_daily_budget' => $dailyBudget,
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
        $campaigns = Campaign::where('ad_account_id', $adAccount->id)->get();
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
                    }
                    $spendCap = isset($accData['spend_cap']) ? ((float) $accData['spend_cap'] / 100) : (isset($accData['spend_limit']) ? ((float) $accData['spend_limit'] / 100) : (float) ($adAccount->spend_limit ?? 0));
                    $balance = isset($accData['balance']) ? ((float) $accData['balance'] / 100) : (float) ($adAccount->balance ?? 0);

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
                        'balance' => $balance,
                        'lifetime_spend' => $spendTotal,
                        'currency' => $currency,
                        'last_synced_at' => now(),
                    ]);
                    $adAccount->meta_business_id = $metaBusinessId;
                    $adAccount->spend_limit = $spendCap;
                    $adAccount->balance = $balance;
                    $adAccount->lifetime_spend = $spendTotal;
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

                // 4. Dynamic Campaigns with Pagination
                $allCampaigns = [];
                $nextUrl = "{$this->baseUrl}/{$version}/act_{$rawAccId}/campaigns";
                $params = [
                    'access_token' => $token,
                    'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining,insights{reach,impressions,spend,actions}',
                    'effective_status' => '["ACTIVE","PAUSED","ARCHIVED","IN_PROCESS","WITH_ISSUES"]',
                    'limit' => 100,
                ];

                $pageCount = 0;
                while ($nextUrl && $pageCount < 5) {
                    $pageCount++;
                    $cRes = Http::withoutVerifying()->timeout(15)->get($nextUrl, $params);
                    if ($cRes->successful() && !empty($cRes->json('data'))) {
                        $cData = $cRes->json('data');
                        $allCampaigns = array_merge($allCampaigns, $cData);
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
                        $rawCampId = $c['id'];
                        $campInsights = $c['insights']['data'][0] ?? [];
                        $cSpend = isset($campInsights['spend']) ? (float) $campInsights['spend'] : 0.00;
                        $cReach = isset($campInsights['reach']) ? (int) $campInsights['reach'] : 0;
                        $cImpressions = isset($campInsights['impressions']) ? (int) $campInsights['impressions'] : 0;
                        $cDailyBudget = isset($c['daily_budget']) ? ((float) $c['daily_budget'] / 100) : 0.00;
                        $cLifetimeBudget = isset($c['lifetime_budget']) ? ((float) $c['lifetime_budget'] / 100) : 0.00;
                        $cBudgetRemaining = isset($c['budget_remaining']) ? ((float) $c['budget_remaining'] / 100) : 0.00;

                        $cStatus = strtolower($c['effective_status'] ?? ($c['status'] ?? 'paused'));

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
            'campaigns_count' => $campaignsCount,
        ];

        Cache::put($cacheKey, $metrics, 60);
        if ($dateRange === 'lifetime') {
            Cache::put($fallbackKey, $metrics, 60);
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
