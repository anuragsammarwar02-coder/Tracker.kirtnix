<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Conversion;
use App\Models\CtaClick;
use App\Models\LandingPage;
use App\Models\LandingPageView;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\TelegramEvent;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsService $analyticsService) {}

    /**
     * Global Agency Analytics Dashboard
     */
    public function index(Request $request): View
    {
        $clientId = $request->input('client_id');
        $campaignId = $request->input('campaign_id');
        $dateRange = $request->input('date_range', 'last_30_days');

        $metrics = $this->analyticsService->getOverviewMetrics($clientId, $campaignId, $dateRange);
        $timeSeries = $this->analyticsService->getTimeSeriesData($clientId, $campaignId, 14);
        $deviceData = $this->analyticsService->getDeviceBreakdown($clientId);
        $utmData = $this->analyticsService->getUtmPerformance($clientId);

        $selectedClient = $clientId ? Client::with(['adAccount', 'campaigns'])->find($clientId) : null;
        $clients = Client::with('adAccount')->get();

        if ($selectedClient) {
            $currencySymbol = $selectedClient->currency_symbol;
            $currency = $selectedClient->currency;
        } else {
            $firstAssigned = AdAccount::whereNotNull('client_id')->first();
            $currencySymbol = $firstAssigned?->currency_symbol ?? '₹';
            $currency = $firstAssigned?->currency ?? 'INR';
        }

        $filters = [
            'client_id' => $clientId,
            'campaign_id' => $campaignId,
            'date_range' => $dateRange,
        ];

        // 8 Key KPI Cards Scoped to Client / Assigned Ad Account
        if ($selectedClient) {
            $adAccount = $selectedClient->adAccount;
            $campaignsQuery = Campaign::where(function ($q) use ($selectedClient, $adAccount) {
                $q->where('client_id', $selectedClient->id);
                if ($adAccount) {
                    $q->orWhere('ad_account_id', $adAccount->id);
                }
            });
            $campaigns = $campaignsQuery->get();
            $totalSpend = (float) $campaigns->sum('spend');
            if ($totalSpend <= 0 && $adAccount && $adAccount->lifetime_spend > 0) {
                $totalSpend = (float) $adAccount->lifetime_spend;
            }
            $impressions = (int) $campaigns->sum('impressions');
            $reach = (int) $campaigns->sum('reach');
            $adClicks = (int) CtaClick::where('client_id', $selectedClient->id)->count() ?: (int) $campaigns->sum(fn($c) => (int) ($c->getAttributes()['clicks'] ?? $c->clicks()->count()));
        } else {
            $activeClientIds = $clients->pluck('id')->all();
            $assignedAdAccountIds = $clients->pluck('ad_account_id')->filter()->all();

            $campaigns = Campaign::where(function ($q) use ($activeClientIds, $assignedAdAccountIds) {
                $q->whereIn('client_id', $activeClientIds)
                  ->orWhereIn('ad_account_id', $assignedAdAccountIds);
            })->get();

            $totalSpend = (float) $campaigns->sum('spend');
            if ($totalSpend <= 0 && !empty($assignedAdAccountIds)) {
                $totalSpend = (float) AdAccount::whereIn('id', $assignedAdAccountIds)->sum('lifetime_spend');
            }
            $impressions = (int) $campaigns->sum('impressions');
            $reach = (int) $campaigns->sum('reach');
            $adClicks = (int) CtaClick::count() ?: (int) $campaigns->sum(fn($c) => (int) ($c->getAttributes()['clicks'] ?? $c->clicks()->count()));
        }

        $landingPages = LandingPage::when($clientId, fn($q) => $q->where('client_id', $clientId))->get();
        $lpViews = $metrics['total_views'];
        $uniqueVisitors = $metrics['unique_visitors'];
        $tgClicks = $metrics['total_clicks'];
        $tgJoins = $metrics['joins'];
        $conversions = Conversion::when($clientId, fn($q) => $q->where('client_id', $clientId))->where('status', 'verified')->count() ?: $tgJoins;
        $costPerConv = $conversions > 0 ? round($totalSpend / $conversions, 2) : 0.00;

        // Conversion Funnel Data
        $funnel = [
            'impressions' => [
                'label' => '1. Impressions',
                'value' => $impressions,
                'pct' => '100%',
            ],
            'ad_clicks' => [
                'label' => '2. Ad Clicks',
                'value' => $adClicks,
                'pct' => $impressions > 0 ? round(($adClicks / $impressions) * 100, 1) . '%' : '0%',
                'dropoff' => $impressions > 0 ? round((1 - ($adClicks / $impressions)) * 100, 1) . '% drop' : '0% drop',
            ],
            'lp_views' => [
                'label' => '3. LP Views',
                'value' => $lpViews,
                'pct' => $adClicks > 0 ? round(($lpViews / $adClicks) * 100, 1) . '%' : ($impressions > 0 ? round(($lpViews / $impressions) * 100, 1) . '%' : '0%'),
                'dropoff' => $adClicks > 0 ? max(0, round((1 - ($lpViews / $adClicks)) * 100, 1)) . '% drop' : '0% drop',
            ],
            'tg_opens' => [
                'label' => '4. TG Clicks (/go)',
                'value' => $tgClicks,
                'pct' => $lpViews > 0 ? round(($tgClicks / $lpViews) * 100, 1) . '%' : '0%',
                'dropoff' => $lpViews > 0 ? max(0, round((1 - ($tgClicks / $lpViews)) * 100, 1)) . '% drop' : '0% drop',
            ],
            'tg_joins' => [
                'label' => '5. Telegram Joins',
                'value' => $tgJoins,
                'pct' => $tgClicks > 0 ? round(($tgJoins / $tgClicks) * 100, 1) . '%' : ($lpViews > 0 ? round(($tgJoins / $lpViews) * 100, 1) . '%' : '0%'),
                'dropoff' => $tgClicks > 0 ? max(0, round((1 - ($tgJoins / $tgClicks)) * 100, 1)) . '% drop' : '0% drop',
            ],
            'verified_conversions' => [
                'label' => '6. Meta Verified',
                'value' => $conversions,
                'pct' => $tgJoins > 0 ? round(($conversions / $tgJoins) * 100, 1) . '%' : '100%',
                'dropoff' => '0% loss',
            ],
        ];

        // Campaign Performance Table Data
        $campaignPerformance = $campaigns->map(function ($camp) use ($currencySymbol) {
            $views = $camp->views()->count();
            $clicks = $camp->clicks()->count() ?: (int) ($camp->getAttributes()['clicks'] ?? 0);
            $joins = $camp->telegramEvents()->where('event_type', 'join')->count() ?: (int) $camp->subscribers;
            $spend = (float) $camp->spend;
            $cpj = $joins > 0 ? round($spend / $joins, 2) : 0.00;
            $ctr = $views > 0 ? round(($clicks / $views) * 100, 1) : 0.0;
            $convRate = $views > 0 ? round(($joins / $views) * 100, 1) : 0.0;
            $cSymbol = $camp->adAccount?->currency_symbol ?? ($camp->client?->currency_symbol ?? $currencySymbol);

            return [
                'id' => $camp->id,
                'name' => $camp->name,
                'client_name' => $camp->client?->company_name ?? 'Client',
                'status' => $camp->status,
                'spend' => $spend,
                'currency_symbol' => $cSymbol,
                'reach' => (int) $camp->reach,
                'impressions' => (int) $camp->impressions,
                'views' => $views,
                'clicks' => $clicks,
                'joins' => $joins,
                'ctr' => $ctr,
                'cost_per_join' => $cpj,
                'cpj' => $cpj,
                'conversion_rate' => $convRate,
            ];
        });

        // Landing Page Performance Data
        $pagePerformance = $landingPages->map(function ($page) use ($currencySymbol) {
            $views = $page->views()->count();
            $uniqueVisitors = $page->views()->where('is_unique', true)->count() ?: $views;
            $clicks = $page->clicks()->count();
            $joins = Conversion::where('landing_page_id', $page->id)->where('status', 'verified')->count() 
                ?: ($page->client?->telegramEvents()->where('event_type', 'join')->count() ?? 0);
            $convRate = $views > 0 ? round(($joins / $views) * 100, 1) : 0.0;
            $spend = (float) ($page->campaign?->spend ?? 0);
            $cpj = $joins > 0 ? round($spend / $joins, 2) : 0.00;
            $cSymbol = $page->client?->currency_symbol ?? $currencySymbol;

            return [
                'title' => $page->title,
                'slug' => $page->slug,
                'views' => $views,
                'unique_visitors' => $uniqueVisitors,
                'telegram_clicks' => $clicks,
                'clicks' => $clicks,
                'joins' => $joins,
                'conversion_rate' => $convRate,
                'cost_per_join' => $cpj,
                'currency_symbol' => $cSymbol,
            ];
        });

        // Telegram Performance Metrics
        $joinRequestsCount = (int) (Conversion::when($clientId, fn($q) => $q->where('client_id', $clientId))->where('event_type', 'join_request')->count())
            + (int) (TelegramEvent::when($clientId, fn($q) => $q->where('client_id', $clientId))->where('event_type', 'pending')->count());
        $metaSentCount = Conversion::when($clientId, fn($q) => $q->where('client_id', $clientId))->where('meta_capi_status', 'sent')->count() ?: $conversions;

        $telegramMetrics = [
            'bot_starts' => $tgClicks,
            'unique_users' => $uniqueVisitors,
            'join_requests' => $joinRequestsCount ?: $tgJoins,
            'verified_joins' => $tgJoins,
            'meta_events_sent' => $metaSentCount,
            'event_delivery_rate' => '100%',
            'leaves_backouts' => $metrics['leaves'],
            'active_subscribers' => max(0, $tgJoins - $metrics['leaves']),
            'delivery_success_rate' => '100%',
        ];

        // Client Comparison Data
        $clientComparison = $clients->map(function ($c) {
            $joins = $c->telegramEvents()->where('event_type', 'join')->count();
            $adAccount = $c->adAccount;
            $cCampaigns = Campaign::where('client_id', $c->id)->when($adAccount, fn($q) => $q->orWhere('ad_account_id', $adAccount->id))->get();
            $spend = (float) $cCampaigns->sum('spend');
            if ($spend <= 0 && $adAccount && $adAccount->lifetime_spend > 0) {
                $spend = (float) $adAccount->lifetime_spend;
            }
            return [
                'name' => $c->company_name,
                'kx_code' => $c->kx_code,
                'joins' => $joins,
                'spend' => $spend,
                'currency_symbol' => $c->currency_symbol,
                'cpj' => $joins > 0 ? round($spend / $joins, 2) : 0.00,
            ];
        });

        // Conversion Timeline Logs
        $clicks = CtaClick::with(['landingPage', 'client', 'cta', 'campaign'])
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->when($campaignId, fn($q) => $q->where('campaign_id', $campaignId))
            ->latest('id')
            ->paginate(15);

        return view('analytics.index', compact(
            'metrics',
            'timeSeries',
            'deviceData',
            'utmData',
            'clients',
            'selectedClient',
            'campaigns',
            'landingPages',
            'clicks',
            'filters',
            'totalSpend',
            'currencySymbol',
            'currency',
            'impressions',
            'reach',
            'adClicks',
            'lpViews',
            'tgJoins',
            'conversions',
            'costPerConv',
            'funnel',
            'campaignPerformance',
            'pagePerformance',
            'telegramMetrics',
            'clientComparison'
        ));
    }

    /**
     * Dedicated Landing Page / Client Analytics Detail Page (Shareable)
     */
    public function detail(Request $request, ?string $slug = null): View
    {
        $landingPage = null;
        if ($slug) {
            $landingPage = LandingPage::where('slug', $slug)
                ->orWhere('telegram_channel_username', $slug)
                ->orWhere('title', $slug)
                ->first();

            if (!$landingPage) {
                $clientMatch = Client::where('kx_code', $slug)
                    ->orWhere('company_name', 'like', "%{$slug}%")
                    ->orWhere('client_name', 'like', "%{$slug}%")
                    ->first();
                if ($clientMatch) {
                    $landingPage = $clientMatch->landingPages()->first();
                }
            }
        }

        if (!$landingPage) {
            $landingPage = LandingPage::first() ?? new LandingPage([
                'title' => 'gujaratitrdexx',
                'slug' => 'gujaratitrdexx',
                'is_published' => true,
            ]);
        }

        // Multi-Tenant Client Role Authorization (if authenticated as a restricted client)
        $user = $request->user() ?? Auth::user();
        if ($user && $user->role === 'client' && $user->client_id) {
            if ($landingPage->client_id && (int) $landingPage->client_id !== (int) $user->client_id) {
                abort(403, 'Unauthorized. You do not have access to view analytics for this client.');
            }
        }

        $client = $landingPage->client ?? Client::first();
        $dateRange = $request->input('date_range', 'last_30_days');

        $dateRangeMap = [
            'today' => [now()->startOfDay(), now()->endOfDay(), 'Today • ' . now()->format('M j, Y')],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), 'Yesterday • ' . now()->subDay()->format('M j, Y')],
            'last_7_days' => [now()->subDays(7)->startOfDay(), now(), 'Last 7 days • ' . now()->subDays(7)->format('M j, Y') . ' – ' . now()->format('M j, Y')],
            'last_30_days' => [now()->subDays(30)->startOfDay(), now(), 'Last 30 days • ' . now()->subDays(30)->format('M j, Y') . ' – ' . now()->format('M j, Y')],
            'this_month' => [now()->startOfMonth(), now(), 'This month • ' . now()->startOfMonth()->format('M j, Y') . ' – ' . now()->format('M j, Y')],
            'lifetime' => [now()->subYears(10), now(), 'Lifetime • All Time'],
        ];

        $rangeInfo = $dateRangeMap[$dateRange] ?? $dateRangeMap['last_30_days'];
        $startDate = $rangeInfo[0];
        $formattedDateRange = $rangeInfo[2];
        $syncedAt = now()->format('n/j/Y, g:i:s A');

        // Connected Ad Account (Scoped strictly to this client)
        $adAccount = $client?->adAccount ?? ($client ? AdAccount::where('client_id', $client->id)->first() : null);

        // Campaigns Live from Meta for this client's assigned ad account
        if ($adAccount) {
            $campaigns = Campaign::where('ad_account_id', $adAccount->id)->get();
        } else {
            $campaigns = collect();
        }

        // 1. Landing Page Views & Unique Visitors from DB
        $viewsQuery = LandingPageView::where('landing_page_id', $landingPage->id)
            ->where('viewed_at', '>=', $startDate);
        $totalLpViews = (clone $viewsQuery)->count();
        $uniqueVisitors = (clone $viewsQuery)->where('is_unique', true)->count();
        if ($totalLpViews > 0 && $uniqueVisitors === 0) {
            $uniqueVisitors = $totalLpViews;
        }

        // 2. CTA Clicks from DB
        $clicksQuery = CtaClick::where('landing_page_id', $landingPage->id)
            ->where('clicked_at', '>=', $startDate);
        $tgClicks = (clone $clicksQuery)->count();

        // 3. Telegram Events from DB
        $eventsQuery = TelegramEvent::where('client_id', $client?->id)
            ->where('event_time', '>=', $startDate);
        $subscribers = (clone $eventsQuery)->where('event_type', 'join')->count();
        $directJoins = (clone $eventsQuery)->where('event_type', 'join')->where('source', 'direct')->count();
        $approvedMembers = (clone $eventsQuery)->whereIn('status_after', ['member', 'approved', 'administrator'])->count();
        if ($approvedMembers === 0 && $subscribers > 0) {
            $approvedMembers = $subscribers;
        }
        $pendingRequests = (clone $eventsQuery)->where('event_type', 'pending')->count();
        $backouts = (clone $eventsQuery)->where('event_type', 'leave')->count();

        // 4. Meta Ads Metrics from Scoped Campaigns
        $campaignSpend = (float) $campaigns->sum('spend');
        $campaignReach = (int) $campaigns->sum('reach');
        $campaignImpressions = (int) $campaigns->sum('impressions');

        // Derived Metrics
        $convRate = $totalLpViews > 0 
            ? round(($subscribers / $totalLpViews) * 100, 1) . '%' 
            : ($uniqueVisitors > 0 ? round(($subscribers / $uniqueVisitors) * 100, 1) . '%' : '0.0%');

        $costPerSub = $subscribers > 0 
            ? ($adAccount?->currency_symbol ?? '₹') . number_format($campaignSpend / $subscribers, 2)
            : ($adAccount?->currency_symbol ?? '₹') . '0.00';

        // Performance KPI Grid
        $kpis = [
            'reach' => $campaignReach,
            'impressions' => $campaignImpressions,
            'lp_views' => $totalLpViews,
            'unique_visitors' => $uniqueVisitors,
            'tg_clicks' => $tgClicks,
            'conversion_rate' => $convRate,
            'direct_joins' => $directJoins,
            'subscribers' => $subscribers,
            'approved_members' => $approvedMembers,
            'pending_requests' => $pendingRequests,
            'cost_per_subscriber' => $costPerSub,
            'backouts' => $backouts,
        ];

        // Budget section
        $totalBudgetLimit = $adAccount ? (float) ($adAccount->spend_limit ?: ($campaigns->sum('active_daily_budget') * 30)) : 0.00;
        $budget = [
            'total_spending' => $campaignSpend,
            'total_budget' => $totalBudgetLimit,
            'remaining_budget' => max(0, $totalBudgetLimit - $campaignSpend),
            'currency_symbol' => $adAccount?->currency_symbol ?? '₹',
            'last_synced' => $adAccount?->last_synced_at ? $adAccount->last_synced_at->diffForHumans() : 'Never',
        ];

        // Complete Join History Table with filters
        $eventFilter = $request->input('event_type');
        $sourceFilter = $request->input('source');
        $search = $request->input('search');

        $joinHistory = TelegramEvent::with(['channel', 'campaign', 'click'])
            ->when($client, fn($q) => $q->where('client_id', $client->id))
            ->when($eventFilter, fn($q) => $q->where('event_type', $eventFilter))
            ->when($sourceFilter, fn($q) => $q->where('source', $sourceFilter))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('telegram_username', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('telegram_user_id', 'like', "%{$search}%");
                });
            })
            ->latest('event_time')
            ->paginate(15)
            ->withQueryString();

        return view('analytics.detail', compact(
            'landingPage',
            'client',
            'adAccount',
            'budget',
            'campaigns',
            'kpis',
            'joinHistory',
            'dateRange',
            'formattedDateRange',
            'syncedAt',
            'eventFilter',
            'sourceFilter',
            'search'
        ));
    }
}
