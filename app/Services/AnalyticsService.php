<?php

namespace App\Services;

use App\Models\LandingPageView;
use App\Models\CtaClick;
use App\Models\TelegramEvent;
use App\Models\TrackingSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Resolve start and end dates from date range preset or custom inputs.
     */
    public function resolveDateRange(?string $range, ?string $startDate = null, ?string $endDate = null): array
    {
        $now = Carbon::now();

        return match ($range) {
            'today' => [
                'start' => $now->copy()->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Today',
            ],
            'yesterday' => [
                'start' => $now->copy()->subDay()->startOfDay(),
                'end' => $now->copy()->subDay()->endOfDay(),
                'label' => 'Yesterday',
            ],
            '7d' => [
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Last 7 Days',
            ],
            '30d' => [
                'start' => $now->copy()->subDays(29)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Last 30 Days',
            ],
            'this_month' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'This Month',
            ],
            'custom' => [
                'start' => $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->subDays(7)->startOfDay(),
                'end' => $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfDay(),
                'label' => 'Custom Range',
            ],
            default => [
                'start' => $now->copy()->subDays(6)->startOfDay(),
                'end' => $now->copy()->endOfDay(),
                'label' => 'Last 7 Days',
            ],
        };
    }

    /**
     * Alias for getMetricsSummary for controller compatibility.
     */
    public function getOverviewMetrics(?int $clientId = null, ?int $campaignId = null, ?string $range = '7d'): array
    {
        return $this->getMetricsSummary([
            'client_id' => $clientId,
            'campaign_id' => $campaignId,
            'range' => $range,
        ]);
    }

    /**
     * Alias for getUtmBreakdown.
     */
    public function getUtmPerformance(?int $clientId = null): array
    {
        return $this->getUtmBreakdown(['client_id' => $clientId]);
    }

    /**
     * Get High-Level Metrics Summary with real database calculation.
     */
    public function getMetricsSummary(array $filters = []): array
    {
        $range = $this->resolveDateRange($filters['range'] ?? '7d', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $clientId = $filters['client_id'] ?? null;
        $campaignId = $filters['campaign_id'] ?? null;
        $landingPageId = $filters['landing_page_id'] ?? null;

        // Base query scopes
        $viewsQuery = LandingPageView::whereBetween('viewed_at', [$range['start'], $range['end']]);
        $clicksQuery = CtaClick::whereBetween('clicked_at', [$range['start'], $range['end']]);
        $eventsQuery = TelegramEvent::whereBetween('event_time', [$range['start'], $range['end']]);

        if ($clientId) {
            $viewsQuery->where('client_id', $clientId);
            $clicksQuery->where('client_id', $clientId);
            $eventsQuery->where('client_id', $clientId);
        }
        if ($campaignId) {
            $viewsQuery->where('campaign_id', $campaignId);
            $clicksQuery->where('campaign_id', $campaignId);
            $eventsQuery->where('campaign_id', $campaignId);
        }
        if ($landingPageId) {
            $viewsQuery->where('landing_page_id', $landingPageId);
            $clicksQuery->where('landing_page_id', $landingPageId);
        }

        $totalViews = (clone $viewsQuery)->count();
        $uniqueVisitors = (clone $viewsQuery)->where('is_unique', true)->count();
        $totalClicks = (clone $clicksQuery)->count();
        $uniqueClicks = (clone $clicksQuery)->where('is_unique', true)->count();

        $joins = (clone $eventsQuery)->where('event_type', 'join')->count();
        $leaves = (clone $eventsQuery)->where('event_type', 'leave')->count();
        $netJoins = max(0, $joins - $leaves);

        // Formulas
        $ctr = $totalViews > 0 ? round(($totalClicks / $totalViews) * 100, 2) : 0.0;
        $joinRate = $totalClicks > 0 ? round(($joins / $totalClicks) * 100, 2) : 0.0;
        $backoutRate = $joins > 0 ? round(($leaves / $joins) * 100, 2) : 0.0;
        $conversionRate = $uniqueVisitors > 0 ? round(($joins / $uniqueVisitors) * 100, 2) : ($totalViews > 0 ? round(($joins / $totalViews) * 100, 2) : 0.0);

        return [
            'date_range' => $range,
            'total_views' => $totalViews,
            'unique_visitors' => $uniqueVisitors,
            'total_clicks' => $totalClicks,
            'unique_clicks' => $uniqueClicks,
            'ctr' => $ctr,
            'joins' => $joins,
            'leaves' => $leaves,
            'net_joins' => $netJoins,
            'join_rate' => $joinRate,
            'backout_rate' => $backoutRate,
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * Get Daily Time-Series Chart Data.
     */
    public function getTimeSeriesData(array|int|null $filters = [], ?int $campaignId = null, ?int $days = 14): array
    {
        if (!is_array($filters)) {
            $filters = [
                'client_id' => $filters,
                'campaign_id' => $campaignId,
                'range' => '7d',
            ];
        }

        $range = $this->resolveDateRange($filters['range'] ?? '7d', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $clientId = $filters['client_id'] ?? null;
        $campaignId = $filters['campaign_id'] ?? null;

        $labels = [];
        $viewsData = [];
        $clicksData = [];
        $joinsData = [];

        $current = $range['start']->copy();
        while ($current <= $range['end']) {
            $dayStart = $current->copy()->startOfDay();
            $dayEnd = $current->copy()->endOfDay();
            $labels[] = $current->format('M d');

            $vQuery = LandingPageView::whereBetween('viewed_at', [$dayStart, $dayEnd]);
            $cQuery = CtaClick::whereBetween('clicked_at', [$dayStart, $dayEnd]);
            $jQuery = TelegramEvent::where('event_type', 'join')->whereBetween('event_time', [$dayStart, $dayEnd]);

            if ($clientId) {
                $vQuery->where('client_id', $clientId);
                $cQuery->where('client_id', $clientId);
                $jQuery->where('client_id', $clientId);
            }
            if ($campaignId) {
                $vQuery->where('campaign_id', $campaignId);
                $cQuery->where('campaign_id', $campaignId);
                $jQuery->where('campaign_id', $campaignId);
            }

            $viewsData[] = $vQuery->count();
            $clicksData[] = $cQuery->count();
            $joinsData[] = $jQuery->count();

            $current->addDay();
        }

        return [
            'labels' => $labels,
            'views' => $viewsData,
            'clicks' => $clicksData,
            'joins' => $joinsData,
        ];
    }

    /**
     * Get Device Distribution Breakdown.
     */
    public function getDeviceBreakdown(array|int|null $filters = []): array
    {
        if (!is_array($filters)) {
            $filters = ['client_id' => $filters];
        }

        $range = $this->resolveDateRange($filters['range'] ?? '7d', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $clientId = $filters['client_id'] ?? null;

        $query = TrackingSession::whereBetween('created_at', [$range['start'], $range['end']]);
        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        $devices = $query->select('device_type', DB::raw('count(*) as total'))
            ->groupBy('device_type')
            ->pluck('total', 'device_type')
            ->toArray();

        return [
            'mobile' => $devices['mobile'] ?? 0,
            'desktop' => $devices['desktop'] ?? 0,
            'tablet' => $devices['tablet'] ?? 0,
            'other' => $devices['bot'] ?? 0,
        ];
    }

    /**
     * Get UTM Sources Breakdown.
     */
    public function getUtmBreakdown(array|int|null $filters = []): array
    {
        if (!is_array($filters)) {
            $filters = ['client_id' => $filters];
        }

        $range = $this->resolveDateRange($filters['range'] ?? '7d', $filters['start_date'] ?? null, $filters['end_date'] ?? null);
        $clientId = $filters['client_id'] ?? null;

        $query = TrackingSession::whereBetween('created_at', [$range['start'], $range['end']])
            ->whereNotNull('utm_source');

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        return $query->select(
                'utm_source',
                'utm_medium',
                'utm_campaign',
                DB::raw('count(*) as sessions_count')
            )
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderByDesc('sessions_count')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
