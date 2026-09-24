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

        // 4. Dynamically resolve client assigned Telegram Channel destination
        $channel = null;
        if ($landingPage->id) {
            $channel = \App\Models\TelegramChannel::where('landing_page_id', $landingPage->id)->where('is_active', true)->first();
        }
        if (!$channel && $landingPage->client_id) {
            $channel = \App\Models\TelegramChannel::where('client_id', $landingPage->client_id)->where('is_active', true)->latest('id')->first();
        }

        if ($channel) {
            $channelDestination = null;
            if (!empty($channel->username)) {
                $channelDestination = 'https://t.me/' . ltrim($channel->username, '@');
            } else {
                $existingInvite = \App\Models\TelegramInvite::where('telegram_channel_id', $channel->id)->where('status', 'active')->latest('id')->first();
                if ($existingInvite && !empty($existingInvite->invite_link)) {
                    $channelDestination = $existingInvite->invite_link;
                }
            }

            if ($channelDestination) {
                $landingPage->telegram_destination = $channelDestination;
            }
        }

        // 5. Resolve CTAs for dynamic insertion into template
        $primaryCta = $landingPage->ctas->where('button_type', 'primary')->first() ?? $landingPage->ctas->first();
        $secondaryCta = $landingPage->ctas->where('button_type', 'secondary')->first() ?? $primaryCta;

        if ($channel && isset($channelDestination) && $channelDestination) {
            if ($primaryCta) $primaryCta->telegram_destination = $channelDestination;
            if ($secondaryCta) $secondaryCta->telegram_destination = $channelDestination;
        }

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
