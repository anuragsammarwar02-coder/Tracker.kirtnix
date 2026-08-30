<?php

namespace App\Http\Controllers;

use App\Models\LandingPage;
use App\Services\TrackingService;
use App\Services\MetaCapiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class PublicLandingPageController extends Controller
{
    protected TrackingService $trackingService;
    protected MetaCapiService $metaCapiService;

    public function __construct(TrackingService $trackingService, MetaCapiService $metaCapiService)
    {
        $this->trackingService = $trackingService;
        $this->metaCapiService = $metaCapiService;
    }

    public function show(string $slug, Request $request)
    {
        $landingPage = LandingPage::with(['ctas', 'client'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        // 1. Record Page View & Tracking Session
        $trackingData = $this->trackingService->recordLandingPageView($landingPage, $request);
        $visitorId = $trackingData['visitor_id'];

        // 2. Generate unique Meta Event ID for PageView deduplication with browser pixel
        $metaEventId = 'pv_' . Str::random(16) . '_' . time();

        // 3. Dispatch Server-Side Meta CAPI PageView Event if configured
        if (!empty($landingPage->meta_pixel_id) && !empty($landingPage->meta_access_token)) {
            $this->metaCapiService->sendEvent(
                landingPage: $landingPage,
                eventName: 'PageView',
                eventId: $metaEventId,
                request: $request,
                customData: [
                    'page_title' => $landingPage->title,
                    'client' => $landingPage->client?->company_name,
                ]
            );
        }

        // 4. Resolve CTAs for dynamic insertion into template
        $primaryCta = $landingPage->ctas->where('button_type', 'primary')->first() ?? $landingPage->ctas->first();
        $secondaryCta = $landingPage->ctas->where('button_type', 'secondary')->first() ?? $primaryCta;

        $template = match (true) {
            !empty($landingPage->blocks_json) || $landingPage->template_type === 'visual_builder' => 'templates.visual_builder',
            $landingPage->template_type === 'gujarati_trader' => 'templates.gujarati_trader',
            default => 'templates.forex_focus',
        };

        // Queue 1-year visitor tracking cookie
        Cookie::queue('kx_visitor_id', $visitorId, 60 * 24 * 365);

        return response()->view($template, compact(
            'landingPage',
            'primaryCta',
            'secondaryCta',
            'metaEventId',
            'visitorId'
        ));
    }
}
