<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversion;
use App\Models\Cta;
use App\Models\CtaClick;
use App\Models\LandingPage;
use App\Models\LandingPageView;
use App\Models\TrackingSession;
use App\Services\MetaCapiService;
use App\Services\TelegramService;
use App\Services\TrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TrackingApiController extends Controller
{
    protected TrackingService $trackingService;
    protected TelegramService $telegramService;
    protected MetaCapiService $metaCapiService;

    public function __construct(
        TrackingService $trackingService,
        TelegramService $telegramService,
        MetaCapiService $metaCapiService
    ) {
        $this->trackingService = $trackingService;
        $this->telegramService = $telegramService;
        $this->metaCapiService = $metaCapiService;
    }

    /**
     * Record a Landing Page View from internal or external landing page.
     * POST /api/track/view
     */
    public function recordView(Request $request)
    {
        $landingPageId = $request->input('landing_page_id');
        $slug = $request->input('slug') ?? $request->input('lp') ?? $request->input('tracking_token');

        $landingPage = null;
        if ($landingPageId) {
            $landingPage = LandingPage::find($landingPageId);
        } elseif ($slug) {
            $landingPage = LandingPage::where('tracking_token', $slug)
                ->orWhere('slug', $slug)
                ->first();
        }

        if (!$landingPage) {
            // Default to first active landing page
            $landingPage = LandingPage::where('is_active', true)->first();
        }

        if (!$landingPage) {
            return response()->json(['error' => 'No active landing page found'], 404);
        }

        $trackingData = $this->trackingService->recordLandingPageView($landingPage, $request);

        $pixelId = $landingPage->meta_pixel_id ?: ($landingPage->client?->meta_pixel_id ?: \App\Models\Setting::get('default_meta_pixel_id'));

        return response()->json([
            'ok' => true,
            'visitor_id' => $trackingData['visitor_id'],
            'session_id' => $trackingData['session']->id,
            'landing_page_id' => $landingPage->id,
            'client_id' => $landingPage->client_id,
            'meta_pixel_id' => $pixelId,
            'is_unique' => $trackingData['view']->is_unique,
        ])->cookie('kx_visitor_id', $trackingData['visitor_id'], 60 * 24 * 365);
    }

    /**
     * Generate or fetch unique Telegram invite link for this visitor session.
     * POST /api/track/invite
     */
    public function getInvite(Request $request)
    {
        $landingPageId = $request->input('landing_page_id');
        $slug = $request->input('slug') ?? $request->input('lp') ?? $request->input('tracking_token');
        $sessionId = $request->input('session_id');
        $visitorId = $request->input('visitor_id') ?? $request->cookie('kx_visitor_id') ?? (string) Str::uuid();

        $landingPage = null;
        if ($landingPageId) {
            $landingPage = LandingPage::find($landingPageId);
        } elseif ($slug) {
            $landingPage = LandingPage::where('tracking_token', $slug)
                ->orWhere('slug', $slug)
                ->first();
        }

        if (!$landingPage) {
            $landingPage = LandingPage::where('is_active', true)->first();
        }
        if (!$landingPage) {
            return response()->json(['error' => 'Landing page not found'], 404);
        }

        $session = null;
        if ($sessionId) {
            $session = TrackingSession::find($sessionId);
        }
        if (!$session) {
            $session = TrackingSession::where('landing_page_id', $landingPage->id)
                ->where('visitor_id', $visitorId)
                ->latest('id')
                ->first();
        }
        if (!$session) {
            $trackingData = $this->trackingService->recordLandingPageView($landingPage, $request);
            $session = $trackingData['session'];
            $visitorId = $trackingData['visitor_id'];
        }

        $inviteData = $this->telegramService->generateInviteLink($landingPage, $session, $visitorId);

        return response()->json([
            'ok' => true,
            'invite_id' => $inviteData['invite']->id,
            'invite_link' => $inviteData['invite_link'],
            'deep_link' => $inviteData['deep_link'],
            'web_url' => $inviteData['web_url'],
            'visitor_id' => $visitorId,
            'session_id' => $session->id,
        ]);
    }

    /**
     * Record a CTA Click event.
     * POST /api/track/click
     */
    public function recordClick(Request $request)
    {
        $landingPageId = $request->input('landing_page_id');
        $sessionId = $request->input('session_id');
        $visitorId = $request->input('visitor_id') ?? $request->cookie('kx_visitor_id') ?? (string) Str::uuid();
        $ctaId = $request->input('cta_id');
        $slug = $request->input('slug') ?? $request->input('lp') ?? $request->input('tracking_token');

        $landingPage = null;
        if ($landingPageId) {
            $landingPage = LandingPage::find($landingPageId);
        } elseif ($slug) {
            $landingPage = LandingPage::where('tracking_token', $slug)
                ->orWhere('slug', $slug)
                ->first();
        }

        if (!$landingPage) {
            $landingPage = LandingPage::where('is_active', true)->first();
        }
        $session = $sessionId ? TrackingSession::find($sessionId) : TrackingSession::where('visitor_id', $visitorId)->latest('id')->first();
        $cta = $ctaId ? Cta::find($ctaId) : $landingPage?->ctas?->first();

        if (!$session && $landingPage) {
            $session = TrackingSession::create([
                'session_id' => (string) Str::uuid(),
                'visitor_id' => $visitorId,
                'client_id' => $landingPage->client_id,
                'landing_page_id' => $landingPage->id,
                'campaign_id' => $landingPage->campaign_id,
                'user_agent' => $request->userAgent(),
                'utm_source' => $request->input('utm_source'),
                'utm_campaign' => $request->input('utm_campaign'),
            ]);
        }

        $existingClick = CtaClick::where('visitor_id', $visitorId)
            ->where('landing_page_id', $landingPage?->id)
            ->where('created_at', '>=', now()->subHours(24))
            ->first();

        $isUnique = is_null($existingClick);
        $destinationUrl = $request->input('destination_url', $landingPage?->telegram_destination ?? 'https://t.me');
        $resolvedLinks = $this->telegramService->resolveDeepLinks($destinationUrl);
        $metaEventId = $request->input('event_id') ?? ('cta_' . Str::random(16) . '_' . time());

        $click = CtaClick::create([
            'tracking_session_id' => $session?->id,
            'cta_id' => $cta?->id ?? 1,
            'landing_page_id' => $landingPage?->id ?? 1,
            'client_id' => $landingPage?->client_id ?? 1,
            'campaign_id' => $landingPage?->campaign_id,
            'tracking_token' => $cta?->tracking_token ?? ('kx_' . Str::random(8)),
            'visitor_id' => $visitorId,
            'is_unique' => $isUnique,
            'destination_url' => $resolvedLinks['web_url'],
            'meta_event_id' => $metaEventId,
            'meta_capi_status' => 'pending',
            'clicked_at' => now(),
        ]);

        if ($cta) {
            $cta->increment('click_count');
        }

        // Dispatch Meta CAPI Website Subscribe event for real-time Meta Ads Manager results
        try {
            $this->metaCapiService->sendCtaClickEvent($click, 'Subscribe');
        } catch (\Throwable $e) {
            Log::info("CAPI Click dispatch notice: " . $e->getMessage());
        }

        return response()->json([
            'ok' => true,
            'click_id' => $click->id,
            'deep_link' => $resolvedLinks['deep_link'],
            'web_url' => $resolvedLinks['web_url'],
        ]);
    }

    /**
     * Retry failed Meta CAPI conversion.
     * POST /api/conversions/{conversion}/retry-meta
     */
    public function retryMeta(Conversion $conversion)
    {
        $result = $this->metaCapiService->sendConversionEvent($conversion);

        return response()->json([
            'ok' => $result['success'] ?? false,
            'status' => $conversion->meta_capi_status,
            'response' => $conversion->meta_capi_response,
        ]);
    }
}
