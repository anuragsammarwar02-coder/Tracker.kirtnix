<?php

namespace App\Services;

use App\Models\Cta;
use App\Models\CtaClick;
use App\Models\TrackingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RedirectService
{
    protected MetaCapiService $metaCapiService;

    public function __construct(MetaCapiService $metaCapiService)
    {
        $this->metaCapiService = $metaCapiService;
    }

    /**
     * Parse destination URL into direct Telegram deep-link and web URL.
     */
    public function resolveTelegramLinks(string $destinationUrl): array
    {
        $cleanUrl = trim($destinationUrl);

        // Normalize if started with @
        if (str_starts_with($cleanUrl, '@')) {
            $username = substr($cleanUrl, 1);
            return [
                'deep_link' => "tg://resolve?domain={$username}",
                'web_url' => "https://t.me/{$username}",
            ];
        }

        // Check if invite link with + (e.g. https://t.me/+abc12345 or t.me/+abc12345)
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?t\.me\/\+([a-zA-Z0-9_\-]+)/i', $cleanUrl, $matches)) {
            $inviteHash = $matches[1];
            return [
                'deep_link' => "tg://join?invite={$inviteHash}",
                'web_url' => "https://t.me/+{$inviteHash}",
            ];
        }

        // Check if joinchat link (e.g. https://t.me/joinchat/abc12345)
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?t\.me\/joinchat\/([a-zA-Z0-9_\-]+)/i', $cleanUrl, $matches)) {
            $inviteHash = $matches[1];
            return [
                'deep_link' => "tg://join?invite={$inviteHash}",
                'web_url' => "https://t.me/joinchat/{$inviteHash}",
            ];
        }

        // Check if standard public channel username (e.g. https://t.me/username)
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?t\.me\/([a-zA-Z0-9_]{4,})/i', $cleanUrl, $matches)) {
            $username = $matches[1];
            return [
                'deep_link' => "tg://resolve?domain={$username}",
                'web_url' => "https://t.me/{$username}",
            ];
        }

        // Fallback: Ensure https:// if missing
        if (!str_starts_with($cleanUrl, 'http://') && !str_starts_with($cleanUrl, 'https://') && !str_starts_with($cleanUrl, 'tg://')) {
            $cleanUrl = 'https://' . $cleanUrl;
        }

        return [
            'deep_link' => $cleanUrl,
            'web_url' => $cleanUrl,
        ];
    }

    /**
     * Process CTA click, record event, trigger Meta CAPI, and prepare redirect payload.
     */
    public function handleCtaClick(Cta $cta, Request $request): array
    {
        $visitorId = $request->cookie('kx_visitor_id') ?? (string) Str::uuid();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        // Find recent tracking session
        $trackingSession = TrackingSession::where('landing_page_id', $cta->landing_page_id)
            ->where('visitor_id', $visitorId)
            ->latest('id')
            ->first();

        // Check uniqueness in last 24h
        $existingClick = CtaClick::where('cta_id', $cta->id)
            ->where('visitor_id', $visitorId)
            ->where('created_at', '>=', now()->subHours(24))
            ->first();

        $isUnique = is_null($existingClick);

        // Generate unique Meta event ID for deduplication
        $metaEventId = 'lead_' . Str::random(16) . '_' . time();

        $destinationUrl = $cta->telegram_destination ?: ($cta->landingPage->telegram_destination ?? 'https://t.me');
        $resolvedLinks = $this->resolveTelegramLinks($destinationUrl);

        // Record Click in database
        $click = CtaClick::create([
            'tracking_session_id' => $trackingSession?->id,
            'cta_id' => $cta->id,
            'landing_page_id' => $cta->landing_page_id,
            'client_id' => $cta->client_id,
            'campaign_id' => $cta->campaign_id,
            'tracking_token' => $cta->tracking_token,
            'visitor_id' => $visitorId,
            'is_unique' => $isUnique,
            'destination_url' => $resolvedLinks['web_url'],
            'meta_event_id' => $metaEventId,
            'meta_capi_status' => 'pending',
            'clicked_at' => now(),
        ]);

        // Increment counter on CTA model
        $cta->increment('click_count');

        // Dispatch Meta CAPI Lead Event if configured
        $landingPage = $cta->landingPage;
        if ($landingPage && !empty($landingPage->meta_pixel_id) && !empty($landingPage->meta_access_token)) {
            $capiResult = $this->metaCapiService->sendEvent(
                landingPage: $landingPage,
                eventName: 'Lead',
                eventId: $metaEventId,
                request: $request,
                customData: [
                    'content_name' => $cta->name,
                    'content_category' => 'Telegram Join CTA',
                    'landing_page_title' => $landingPage->title,
                ]
            );

            $click->update([
                'meta_capi_status' => $capiResult['success'] ? 'sent' : 'failed',
                'meta_capi_response' => json_encode($capiResult),
            ]);
        } else {
            $click->update(['meta_capi_status' => 'skipped']);
        }

        return [
            'click' => $click,
            'deep_link' => $resolvedLinks['deep_link'],
            'web_url' => $resolvedLinks['web_url'],
            'visitor_id' => $visitorId,
        ];
    }
}
