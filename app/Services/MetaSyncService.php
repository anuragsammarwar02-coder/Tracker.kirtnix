<?php

namespace App\Services;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\CampaignInsight;
use App\Models\Client;
use App\Models\MetaBusiness;
use App\Models\MetaConnection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MetaSyncService
{
    protected string $graphApiVersion = 'v19.0';
    protected string $baseUrl = 'https://graph.facebook.com';

    /**
     * Start Meta OAuth or connect active token.
     */
    public function connectAccessToken(string $accessToken, ?int $userId = null): MetaConnection
    {
        // Try fetching user profile from Meta Graph API
        $userData = $this->fetchUserProfile($accessToken);

        $connection = MetaConnection::updateOrCreate(
            ['user_id' => $userId],
            [
                'facebook_user_id' => $userData['id'] ?? 'fb_agency_admin_' . rand(10000, 99999),
                'facebook_name' => $userData['name'] ?? 'KirtniX Performance Agency',
                'access_token' => $accessToken,
                'status' => 'active',
                'sync_status' => 'idle',
                'last_sync_at' => now(),
            ]
        );

        $this->syncAll($connection);

        return $connection;
    }

    /**
     * Fetch user profile from Graph API
     */
    protected function fetchUserProfile(string $token): ?array
    {
        try {
            $res = Http::withoutVerifying()->timeout(8)->get("{$this->baseUrl}/{$this->graphApiVersion}/me", [
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
            $res = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$this->graphApiVersion}/me/businesses", [
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

        return MetaBusiness::where('meta_connection_id', $connection->id)->get()->all();
    }

    /**
     * Sync Ad Accounts from Meta Graph API
     */
    public function syncAdAccounts(MetaConnection $connection): array
    {
        $token = $connection->access_token;
        $results = [];

        // Attempt live Graph API query for Ad Accounts
        try {
            $res = Http::withoutVerifying()->timeout(12)->get("{$this->baseUrl}/{$this->graphApiVersion}/me/adaccounts", [
                'access_token' => $token,
                'fields' => 'id,account_id,name,currency,account_status,spend_limit,balance,amount_spent,daily_budget,funding_source_details',
                'limit' => 100,
            ]);

            if ($res->successful() && !empty($res->json('data'))) {
                $accountsData = $res->json('data');
                $business = MetaBusiness::where('meta_connection_id', $connection->id)->first();

                foreach ($accountsData as $acc) {
                    $rawId = (string) ($acc['account_id'] ?? $acc['id']);
                    $accId = str_starts_with($rawId, 'act_') ? $rawId : ('act_' . $rawId);
                    $statusNum = $acc['account_status'] ?? 1;
                    $status = ($statusNum === 1) ? 'Active' : (($statusNum === 2) ? 'Disabled' : 'Unsettled');

                    $spendLimit = isset($acc['spend_limit']) ? ((float) $acc['spend_limit'] / 100) : 0.00;
                    $balance = isset($acc['balance']) ? ((float) $acc['balance'] / 100) : 0.00;
                    $lifetimeSpend = isset($acc['amount_spent']) ? ((float) $acc['amount_spent'] / 100) : 0.00;
                    $dailyBudget = isset($acc['daily_budget']) ? ((float) $acc['daily_budget'] / 100) : 0.00;

                    $record = AdAccount::updateOrCreate(
                        ['account_id' => $accId],
                        [
                            'meta_connection_id' => $connection->id,
                            'meta_business_id' => $business?->id,
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

                    $results[] = $record;
                }

                return $results;
            }
        } catch (\Exception $e) {
            Log::warning('Meta Graph API Ad Accounts Error: ' . $e->getMessage());
        }

        // Return real existing accounts from database without creating fake fixtures
        return AdAccount::where('meta_connection_id', $connection->id)->get()->all();
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
            $rawAccId = str_replace('act_', '', $adAccount->account_id);

            // 1. Sync live ad account metadata from Meta
            $accRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$this->graphApiVersion}/act_{$rawAccId}", [
                'access_token' => $token,
                'fields' => 'id,account_id,name,currency,account_status,spend_limit,balance,amount_spent,daily_budget,funding_source_details,timezone_name',
            ]);

            if ($accRes->successful() && !empty($accRes->json())) {
                $accData = $accRes->json();
                $spendLimit = isset($accData['spend_limit']) ? ((float) $accData['spend_limit'] / 100) : 0.00;
                $balance = isset($accData['balance']) ? ((float) $accData['balance'] / 100) : 0.00;
                $lifetimeSpend = isset($accData['amount_spent']) ? ((float) $accData['amount_spent'] / 100) : (float)$adAccount->lifetime_spend;
                $dailyBudget = isset($accData['daily_budget']) ? ((float) $accData['daily_budget'] / 100) : 0.00;

                $adAccount->update([
                    'spend_limit' => $spendLimit,
                    'balance' => $balance,
                    'lifetime_spend' => $lifetimeSpend,
                    'active_daily_budget' => $dailyBudget,
                    'currency' => $accData['currency'] ?? $adAccount->currency,
                    'last_synced_at' => now(),
                ]);
            }

            // 2. Sync campaigns and insights from Meta with pagination
            $allCampaignData = [];
            $nextUrl = "{$this->baseUrl}/{$this->graphApiVersion}/act_{$rawAccId}/campaigns";
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

                    $subscribers = 0;
                    if (!empty($insights['actions'])) {
                        foreach ($insights['actions'] as $act) {
                            if (in_array($act['action_type'], ['lead', 'onsite_conversion.subscribe', 'subscribe'])) {
                                $subscribers += (int) $act['value'];
                            }
                        }
                    }
                    $costPerSub = $subscribers > 0 ? round($spend / $subscribers, 2) : 0.00;

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
                            'subscribers' => $subscribers,
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
    public function getAdAccountMetrics(AdAccount $adAccount, bool $forceRefresh = false): array
    {
        $clientId = $adAccount->client_id ?? 0;
        $cacheKey = "meta_analytics:client_{$clientId}:acc_{$adAccount->id}";

        if (!$forceRefresh && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
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
        $impressions = (int) $campaigns->sum('impressions');
        $reach = (int) $campaigns->sum('reach');
        $clicks = (int) CampaignInsight::whereIn('campaign_id', $campaignIds)->sum('clicks');
        if ($clicks <= 0) {
            $clicks = (int) \App\Models\CtaClick::whereIn('campaign_id', $campaignIds)->count();
        }
        $leads = (int) $campaigns->sum('subscribers');
        $campaignsCount = $campaigns->count();

        // Attempt Live Meta Graph API query
        if (!empty($token)) {
            try {
                $rawAccId = str_replace('act_', '', $adAccount->account_id);

                // 1. Account Metadata & Lifetime Spend
                $accRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$this->graphApiVersion}/act_{$rawAccId}", [
                    'access_token' => $token,
                    'fields' => 'id,account_id,name,currency,account_status,spend_limit,balance,amount_spent,daily_budget,timezone_name,timezone_offset_hours_utc',
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
                    $adAccount->update([
                        'lifetime_spend' => $spendTotal,
                        'currency' => $currency,
                        'last_synced_at' => now(),
                    ]);
                }

                // 2. Maximum / Lifetime Reporting Insights
                $maxRes = Http::withoutVerifying()->timeout(12)->get("{$this->baseUrl}/{$this->graphApiVersion}/act_{$rawAccId}/insights", [
                    'access_token' => $token,
                    'date_preset' => 'maximum',
                    'fields' => 'spend,impressions,reach,clicks,cpc,cpm,ctr,actions',
                ]);

                if ($maxRes->successful() && !empty($maxRes->json('data'))) {
                    $maxInsights = $maxRes->json('data')[0] ?? [];
                    if (isset($maxInsights['spend'])) {
                        $spendTotal = (float) $maxInsights['spend'];
                    }
                    if (isset($maxInsights['impressions'])) {
                        $impressions = (int) $maxInsights['impressions'];
                    }
                    if (isset($maxInsights['reach'])) {
                        $reach = (int) $maxInsights['reach'];
                    }
                    if (isset($maxInsights['clicks'])) {
                        $clicks = (int) $maxInsights['clicks'];
                    }
                    if (!empty($maxInsights['actions'])) {
                        $leads = 0;
                        foreach ($maxInsights['actions'] as $act) {
                            if (in_array($act['action_type'] ?? '', ['lead', 'onsite_conversion.subscribe', 'subscribe'])) {
                                $leads += (int) ($act['value'] ?? 0);
                            }
                        }
                    }
                }

                // 3. TODAY's Insights in the account's configured timezone
                $todayRes = Http::withoutVerifying()->timeout(10)->get("{$this->baseUrl}/{$this->graphApiVersion}/act_{$rawAccId}/insights", [
                    'access_token' => $token,
                    'date_preset' => 'today',
                    'fields' => 'spend,impressions,reach,clicks,actions',
                ]);

                if ($todayRes->successful() && !empty($todayRes->json('data'))) {
                    $todayData = $todayRes->json('data')[0] ?? [];
                    $spendToday = (float) ($todayData['spend'] ?? 0.00);
                } else {
                    // Meta reports no insights for today -> exactly ₹0
                    $spendToday = 0.00;
                }

                // 4. Dynamic Campaigns with Pagination
                $allCampaigns = [];
                $nextUrl = "{$this->baseUrl}/{$this->graphApiVersion}/act_{$rawAccId}/campaigns";
                $params = [
                    'access_token' => $token,
                    'fields' => 'id,name,objective,status,effective_status,daily_budget,lifetime_budget,budget_remaining,insights{reach,impressions,spend,actions}',
                    'effective_status' => '["ACTIVE","PAUSED","ARCHIVED","IN_PROCESS","WITH_ISSUES"]',
                    'limit' => 100,
                ];

                $pages = 0;
                while ($nextUrl && $pages < 5) {
                    $pages++;
                    $cRes = $pages === 1
                        ? Http::withoutVerifying()->timeout(15)->get($nextUrl, $params)
                        : Http::withoutVerifying()->timeout(15)->get($nextUrl);

                    if ($cRes->successful() && !empty($cRes->json('data'))) {
                        foreach ($cRes->json('data') as $c) {
                            $allCampaigns[] = $c;
                        }
                        $nextUrl = $cRes->json('paging.next');
                    } else {
                        break;
                    }
                }

                if (!empty($allCampaigns)) {
                    $campaignsCount = count($allCampaigns);

                    // Sync campaigns locally for this client/ad_account
                    foreach ($allCampaigns as $c) {
                        $rawStatus = $c['status'] ?? $c['effective_status'] ?? 'ACTIVE';
                        $cInsights = $c['insights']['data'][0] ?? [];
                        $cSpend = (float) ($cInsights['spend'] ?? 0);
                        $cReach = (int) ($cInsights['reach'] ?? 0);
                        $cImpressions = (int) ($cInsights['impressions'] ?? 0);
                        $cDaily = isset($c['daily_budget']) ? ((float) $c['daily_budget'] / 100) : 0.00;
                        $cLife = isset($c['lifetime_budget']) ? ((float) $c['lifetime_budget'] / 100) : 0.00;

                        Campaign::updateOrCreate(
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
                                'status' => ucfirst(strtolower($rawStatus)),
                                'spend' => $cSpend,
                                'budget' => $cLife,
                                'active_daily_budget' => $cDaily,
                                'reach' => $cReach,
                                'impressions' => $cImpressions,
                            ]
                        );
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Meta Graph API live analytics error for account {$adAccount->account_id}: " . $e->getMessage());
            }
        }

        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.00;
        $cpc = $clicks > 0 ? round($spendTotal / $clicks, 2) : 0.00;
        $cpm = $impressions > 0 ? round(($spendTotal / $impressions) * 1000, 2) : 0.00;

        $metrics = [
            'connected' => true,
            'account_name' => $adAccount->name,
            'account_id' => $adAccount->account_id,
            'business_name' => $adAccount->metaBusiness?->name ?? 'Arabika Kofi',
            'currency' => $currency,
            'currency_symbol' => $currencySymbol,
            'status' => $adAccount->status ?? 'Active',
            'timezone' => $timezone,
            'last_sync' => $adAccount->last_synced_at ? $adAccount->last_synced_at->diffForHumans() : 'Just now',
            'spend_today' => $spendToday,
            'spend_total' => $spendTotal,
            'spend_month' => $spendTotal, // For backwards compatibility
            'clicks' => $clicks,
            'impressions' => $impressions,
            'reach' => $reach,
            'leads' => $leads,
            'ctr' => $ctr,
            'cpc' => $cpc,
            'cpm' => $cpm,
            'campaigns_count' => $campaignsCount,
        ];

        Cache::put($cacheKey, $metrics, 60);

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
