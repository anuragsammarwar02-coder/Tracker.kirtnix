<?php

namespace App\Services;

use App\Models\Cta;
use App\Models\CtaClick;
use App\Models\LandingPage;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\TelegramInvite;
use App\Models\TrackingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RedirectService
{
    protected MetaCapiService $metaCapiService;

    public function __construct(MetaCapiService $metaCapiService)
    {
        $this->metaCapiService = $metaCapiService;
    }

    /**
     * Resolve the optimal Telegram destination URL for a CTA click.
     * Ensures that if a client has an assigned TelegramChannel and TelegramBot,
     * the CTA will dynamically route directly to their active channel.
     */
    public function resolveDestinationForCta(Cta $cta, string $visitorId, ?TrackingSession $trackingSession): string
    {
        $clientId = $cta->client_id ?? $cta->landingPage?->client_id;
        $landingPage = $cta->landingPage;

        // 1. Check if an active TelegramChannel is assigned to this landing page or client
        $channel = null;
        if ($cta->landing_page_id) {
            $channel = TelegramChannel::where('landing_page_id', $cta->landing_page_id)
                ->where('is_active', true)
                ->first();
        }
        if (!$channel && $clientId) {
            $channel = TelegramChannel::where('client_id', $clientId)
                ->where('is_active', true)
                ->latest('id')
                ->first();
        }

        if ($channel) {
            // If channel has a public @username, prioritize direct Telegram URL
            if (!empty($channel->username)) {
                $channelUrl = 'https://t.me/' . ltrim($channel->username, '@');

                if ($cta->telegram_destination !== $channelUrl) {
                    $cta->update(['telegram_destination' => $channelUrl]);
                }
                if ($landingPage && $landingPage->telegram_destination !== $channelUrl) {
                    $landingPage->update(['telegram_destination' => $channelUrl]);
                }

                return $channelUrl;
            }

            // Find associated bot
            $bot = $channel->bot 
                ?? TelegramBot::find($channel->telegram_bot_id)
                ?? TelegramBot::where('client_id', $clientId)->where('is_active', true)->first()
                ?? TelegramBot::where('is_global', true)->where('is_active', true)->latest('id')->first();

            // Try to generate / fetch invite link via Bot API
            if ($bot && !empty($bot->bot_token) && !empty($channel->telegram_chat_id)) {
                $inviteUrl = null;
                try {
                    $apiUrl = "https://api.telegram.org/bot{$bot->bot_token}/createChatInviteLink";
                    $res = Http::timeout(5)->post($apiUrl, [
                        'chat_id' => $channel->telegram_chat_id,
                        'name' => 'kx_' . substr($visitorId, 0, 8),
                        'creates_join_request' => true,
                    ]);
                    $json = $res->json();
                    if ($res->successful() && ($json['ok'] ?? false)) {
                        $inviteUrl = $json['result']['invite_link'] ?? null;
                    }
                } catch (\Throwable $e) {
                    Log::info("RedirectService createChatInviteLink note: " . $e->getMessage());
                }

                if (!$inviteUrl) {
                    try {
                        $exportUrl = "https://api.telegram.org/bot{$bot->bot_token}/exportChatInviteLink";
                        $res = Http::timeout(5)->post($exportUrl, [
                            'chat_id' => $channel->telegram_chat_id,
                        ]);
                        $json = $res->json();
                        if ($res->successful() && ($json['ok'] ?? false)) {
                            $inviteUrl = $json['result'] ?? null;
                        }
                    } catch (\Throwable $e) {
                        Log::info("RedirectService exportChatInviteLink note: " . $e->getMessage());
                    }
                }

                if ($inviteUrl) {
                    // Record invite in TelegramInvite table if trackingSession exists
                    if ($trackingSession) {
                        TelegramInvite::firstOrCreate(
                            [
                                'tracking_session_id' => $trackingSession->id,
                                'visitor_id' => $visitorId,
                            ],
                            [
                                'invite_link' => $inviteUrl,
                                'invite_name' => 'kx_' . substr($visitorId, 0, 8),
                                'landing_page_id' => $cta->landing_page_id,
                                'client_id' => $clientId,
                                'telegram_bot_id' => $bot->id,
                                'telegram_channel_id' => $channel->id,
                                'is_single_use' => true,
                                'creates_join_request' => true,
                                'status' => 'active',
                                'expires_at' => now()->addDays(7),
                            ]
                        );
                    }

                    // Sync to CTA and Landing page
                    if ($cta->telegram_destination !== $inviteUrl) {
                        $cta->update(['telegram_destination' => $inviteUrl]);
                    }
                    if ($landingPage && $landingPage->telegram_destination !== $inviteUrl) {
                        $landingPage->update(['telegram_destination' => $inviteUrl]);
                    }

                    return $inviteUrl;
                }
            }

            // If an invite was previously generated for this channel, reuse it
            $existingInvite = TelegramInvite::where('telegram_channel_id', $channel->id)
                ->where('status', 'active')
                ->latest('id')
                ->first();
            if ($existingInvite && !empty($existingInvite->invite_link)) {
                return $existingInvite->invite_link;
            }
        }

        // 2. Fallback to CTA / LandingPage configured destination
        return $cta->telegram_destination ?: ($landingPage?->telegram_destination ?? 'https://t.me');
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

        // Already tg:// protocol
        if (str_starts_with($cleanUrl, 'tg://join?invite=')) {
            $inviteHash = substr($cleanUrl, strlen('tg://join?invite='));
            return [
                'deep_link' => $cleanUrl,
                'web_url' => "https://t.me/+{$inviteHash}",
            ];
        }
        if (str_starts_with($cleanUrl, 'tg://resolve?domain=')) {
            $params = substr($cleanUrl, strlen('tg://resolve?domain='));
            return [
                'deep_link' => $cleanUrl,
                'web_url' => "https://t.me/{$params}",
            ];
        }

        // Check if invite link with + (e.g. https://t.me/+abc12345, telegram.me/+abc12345, or t.me/+abc12345)
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:t\.me|telegram\.me|telegram\.dog)\/\+([a-zA-Z0-9_\-]+)/i', $cleanUrl, $matches)) {
            $inviteHash = $matches[1];
            return [
                'deep_link' => "tg://join?invite={$inviteHash}",
                'web_url' => "https://t.me/+{$inviteHash}",
            ];
        }

        // Check if joinchat link (e.g. https://t.me/joinchat/abc12345 or telegram.me/joinchat/abc12345)
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:t\.me|telegram\.me|telegram\.dog)\/joinchat\/([a-zA-Z0-9_\-]+)/i', $cleanUrl, $matches)) {
            $inviteHash = $matches[1];
            return [
                'deep_link' => "tg://join?invite={$inviteHash}",
                'web_url' => "https://t.me/joinchat/{$inviteHash}",
            ];
        }

        // Check if standard public channel username or bot with query (e.g. https://t.me/username or https://t.me/username?start=xxx)
        if (preg_match('/(?:https?:\/\/)?(?:www\.)?(?:t\.me|telegram\.me|telegram\.dog)\/([a-zA-Z0-9_]{4,})(?:\?(.*))?/i', $cleanUrl, $matches)) {
            $username = $matches[1];
            $query = isset($matches[2]) && !empty($matches[2]) ? '&' . $matches[2] : '';
            return [
                'deep_link' => "tg://resolve?domain={$username}{$query}",
                'web_url' => "https://t.me/{$username}" . (isset($matches[2]) && !empty($matches[2]) ? '?' . $matches[2] : ''),
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

        $destinationUrl = $this->resolveDestinationForCta($cta, $visitorId, $trackingSession);
        $resolvedLinks = $this->resolveTelegramLinks($destinationUrl);

        // Record Click in database
        $click = CtaClick::create([
            'tracking_session_id' => $trackingSession?->id,
            'cta_id' => $cta->id,
            'landing_page_id' => $cta->landing_page_id,
            'client_id' => $cta->client_id,
            'campaign_id' => $trackingSession?->campaign_id ?? $cta->campaign_id,
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

        // Optional funnel event: dispatch Lead if configured, but NEVER Subscribe on CTA click (Subscribe is strictly for confirmed Telegram joins)
        try {
            $this->metaCapiService->sendCtaClickEvent($click, 'Lead');
        } catch (\Throwable $e) {
            Log::info("RedirectService CAPI notice: " . $e->getMessage());
        }

        return [
            'click' => $click,
            'deep_link' => $resolvedLinks['deep_link'],
            'web_url' => $resolvedLinks['web_url'],
            'visitor_id' => $visitorId,
        ];
    }
}
