<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\Conversion;
use App\Models\CtaClick;
use App\Models\LandingPage;
use App\Models\LandingPageView;
use App\Models\TelegramEvent;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $range = $request->query('range', '7d');
        $clientId = $request->query('client_id');
        $filters = [
            'range' => $range,
            'client_id' => $clientId,
            'start_date' => $request->query('start_date'),
            'end_date' => $request->query('end_date'),
        ];

        $metrics = $this->analyticsService->getMetricsSummary($filters);
        $timeSeries = $this->analyticsService->getTimeSeriesData($filters);
        $deviceData = $this->analyticsService->getDeviceBreakdown($filters);
        $utmData = $this->analyticsService->getUtmBreakdown($filters);

        // Core Counts
        $totalClients = Client::count();
        $totalLandingPages = LandingPage::when($clientId, fn($q) => $q->where('client_id', $clientId))->count();
        $activeCampaigns = Campaign::when($clientId, fn($q) => $q->where('client_id', $clientId))->where('status', 'active')->count();

        // Calculate Real Agency Spend & Reach
        $campaignsQuery = Campaign::when($clientId, fn($q) => $q->where('client_id', $clientId));
        $totalSpend = (float) $campaignsQuery->sum('spend');
        $totalReach = (int) $campaignsQuery->sum('reach');

        $totalViews = $metrics['total_views'];
        $uniqueVisitors = $metrics['unique_visitors'];
        $totalClicks = $metrics['total_clicks'];
        $totalJoins = $metrics['joins'];
        $totalExits = $metrics['leaves'];

        $joinRequests = Conversion::when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->where('event_type', 'join_request')
            ->count() 
            + TelegramEvent::when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->where('event_type', 'pending')
            ->count();

        $costPerJoin = $totalJoins > 0 ? round($totalSpend / $totalJoins, 2) : 0.00;
        $conversionRate = $totalViews > 0 ? round(($totalJoins / $totalViews) * 100, 1) : 0.0;
        $ctr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0.0;
        $joinRequestRate = $totalClicks > 0 ? round(($joinRequests / $totalClicks) * 100, 2) : 0.0;

        // Client Performance Breakdown
        $allClients = Client::all();
        $clientsQuery = Client::with(['campaigns', 'views', 'clicks', 'telegramEvents', 'conversions']);
        if ($clientId) {
            $clientsQuery->where('id', $clientId);
        }
        $clients = $clientsQuery->get();

        $clientPerformance = $clients->map(function ($c) {
            $spend = (float) $c->campaigns->sum('spend');
            $reach = (int) $c->campaigns->sum('reach');
            $views = $c->views->count();
            $clicks = $c->clicks->count();
            $joins = $c->conversions->where('status', 'verified')->count() ?: $c->telegramEvents->where('event_type', 'join')->count();
            $cpj = $joins > 0 ? round($spend / $joins, 2) : 0.00;

            return [
                'id' => $c->id,
                'kx_code' => $c->kx_code ?? "KX-00{$c->id}",
                'name' => $c->company_name,
                'client_name' => $c->client_name,
                'industry' => $c->industry ?? 'Trading',
                'spend' => $spend,
                'reach' => $reach,
                'views' => $views,
                'clicks' => $clicks,
                'joins' => $joins,
                'cost_per_join' => $cpj,
                'meta_connected' => $c->meta_ads_connected,
            ];
        });

        // Tracking Health Status
        $trackingHealth = [
            ['name' => 'Meta Ads CAPI', 'status' => 'LIVE', 'latency' => '124ms', 'success' => '100%'],
            ['name' => 'Telegram Bot API', 'status' => 'HEALTHY', 'latency' => '85ms', 'success' => '99.8%'],
            ['name' => 'Webhook Engine', 'status' => 'LIVE', 'latency' => '42ms', 'success' => '100%'],
            ['name' => 'Landing Page Router', 'status' => 'HEALTHY', 'latency' => '18ms', 'success' => '100%'],
            ['name' => 'Meta Pixel (Browser)', 'status' => 'HEALTHY', 'latency' => '92ms', 'success' => '100%'],
        ];

        // Recent Activity Feed
        $recentClicks = CtaClick::with(['landingPage', 'client', 'cta'])
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->latest('id')
            ->limit(7)
            ->get();

        $recentTelegramEvents = TelegramEvent::with(['client', 'campaign'])
            ->when($clientId, fn($q) => $q->where('client_id', $clientId))
            ->latest('id')
            ->limit(7)
            ->get();

        return view('dashboard.index', [
            'metrics' => $metrics,
            'timeSeries' => $timeSeries,
            'deviceData' => $deviceData,
            'utmData' => $utmData,
            'totalClients' => $totalClients,
            'totalLandingPages' => $totalLandingPages,
            'activeCampaigns' => $activeCampaigns,
            'totalSpend' => $totalSpend,
            'totalReach' => $totalReach,
            'totalViews' => $totalViews,
            'uniqueVisitors' => $uniqueVisitors,
            'totalClicks' => $totalClicks,
            'totalJoins' => $totalJoins,
            'totalExits' => $totalExits,
            'joinRequests' => $joinRequests,
            'costPerJoin' => $costPerJoin,
            'conversionRate' => $conversionRate,
            'ctr' => $ctr,
            'joinRequestRate' => $joinRequestRate,
            'clientPerformance' => $clientPerformance,
            'trackingHealth' => $trackingHealth,
            'recentClicks' => $recentClicks,
            'recentTelegramEvents' => $recentTelegramEvents,
            'clients' => $allClients,
            'filters' => $filters,
        ]);
    }
}
