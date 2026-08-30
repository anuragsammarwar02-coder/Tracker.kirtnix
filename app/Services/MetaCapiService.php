<?php

namespace App\Services;

use App\Models\Conversion;
use App\Models\LandingPage;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCapiService
{
    /**
     * Hash string for Meta CAPI (SHA-256 after trim and lowercase).
     */
    public function hashField(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        return hash('sha256', strtolower(trim($value)));
    }

    /**
     * Resolve valid Meta Pixel ID and Access Token with intelligent fallbacks.
     */
    public function resolveCredentials(?LandingPage $landingPage): array
    {
        $pixelId = trim($landingPage?->meta_pixel_id ?? '');

        // If landing page has pixel code in custom_head_code, auto-extract pixel ID
        if (empty($pixelId) && !empty($landingPage?->custom_head_code)) {
            if (preg_match('/fbq\([\'"]init[\'"],\s*[\'"](\d+)[\'"]\)/i', $landingPage->custom_head_code, $matches)) {
                $pixelId = $matches[1];
                try {
                    $landingPage->update(['meta_pixel_id' => $pixelId]);
                } catch (\Throwable $e) {
                    // Non-fatal if update fails
                }
            }
        }

        // Fallback to system settings or default
        if (empty($pixelId)) {
            $pixelId = trim(Setting::get('meta_pixel_id', Setting::get('default_meta_pixel_id', '1018611380802707')));
        }

        // Resolve Access Token: LandingPage -> Setting meta_access_token -> Setting meta_system_user_token -> MetaConnection
        $accessToken = trim(
            $landingPage?->meta_access_token 
            ?: (Setting::get('meta_access_token') 
            ?: (Setting::get('meta_system_user_token') 
            ?: (\App\Models\MetaConnection::first()?->access_token ?? '')))
        );

        return [$pixelId, $accessToken];
    }

    /**
     * Dispatch server-side Conversion Event to Meta Graph API for a generic request.
     */
    public function sendEvent(
        LandingPage $landingPage,
        string $eventName,
        string $eventId,
        Request $request,
        array $customData = []
    ): array {
        [$pixelId, $accessToken] = $this->resolveCredentials($landingPage);

        if (empty($pixelId) || empty($accessToken)) {
            return [
                'success' => false,
                'message' => 'Pixel ID or Access Token is missing',
            ];
        }

        $fbclid = $request->query('fbclid');
        $fbc = $request->cookie('_fbc') ?? ($fbclid ? "fb.1." . time() . ".{$fbclid}" : null);
        $fbp = $request->cookie('_fbp');

        $userData = array_filter([
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'fbc' => $fbc,
            'fbp' => $fbp,
        ]);

        $apiVersion = Setting::get('meta_api_version', 'v21.0');
        $eventPayload = [
            'event_name' => $eventName,
            'event_time' => time(),
            'event_id' => $eventId,
            'event_source_url' => $request->fullUrl(),
            'action_source' => 'website',
            'user_data' => $userData,
            'custom_data' => $customData,
        ];

        $postData = [
            'data' => [$eventPayload],
        ];

        if (!empty($landingPage->meta_test_event_code)) {
            $postData['test_event_code'] = $landingPage->meta_test_event_code;
        }

        try {
            $response = Http::timeout(6)
                ->asJson()
                ->post("https://graph.facebook.com/{$apiVersion}/{$pixelId}/events?access_token={$accessToken}", $postData);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status_code' => $response->status(),
                    'data' => $response->json(),
                ];
            }

            Log::warning("Meta CAPI Error for LP {$landingPage->id}: " . $response->body());
            return [
                'success' => false,
                'status_code' => $response->status(),
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            Log::error("Meta CAPI Exception for LP {$landingPage->id}: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Dispatch CTA Click to Meta Conversions API (Website Subscribe / Lead).
     */
    public function sendCtaClickEvent(CtaClick $click, string $eventName = 'Lead'): array
    {
        $landingPage = $click->landingPage;
        [$pixelId, $accessToken] = $this->resolveCredentials($landingPage);

        if (empty($pixelId) || empty($accessToken)) {
            $click->update([
                'meta_capi_status' => 'skipped',
            ]);
            return [
                'success' => false,
                'status' => 'skipped',
                'message' => 'Pixel credentials not configured',
            ];
        }

        $session = $click->session;
        $eventId = $click->meta_event_id ?: 'cta_' . $click->id . '_' . time();
        if (empty($click->meta_event_id)) {
            $click->update(['meta_event_id' => $eventId]);
        }

        $userData = array_filter([
            'client_ip_address' => $session?->ip_address,
            'client_user_agent' => $session?->user_agent,
            'fbc' => $session?->fbc,
            'fbp' => $session?->fbp,
            'external_id' => $this->hashField($click->visitor_id),
        ]);

        $eventSourceUrl = $landingPage?->public_url ?? url('/lp/' . ($landingPage?->slug ?? 'kirtnix-digital'));
        $apiVersion = Setting::get('meta_api_version', 'v21.0');

        $eventPayload = [
            'event_name' => $eventName,
            'event_time' => $click->clicked_at ? $click->clicked_at->timestamp : time(),
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $eventSourceUrl,
            'user_data' => $userData,
            'custom_data' => [
                'button_text' => 'Join Telegram',
                'destination_url' => $click->destination_url,
                'currency' => 'INR',
                'value' => 0.00,
            ],
        ];

        $postData = [
            'data' => [$eventPayload],
        ];

        if (!empty($landingPage?->meta_test_event_code)) {
            $postData['test_event_code'] = $landingPage->meta_test_event_code;
        }

        try {
            $response = Http::timeout(6)
                ->asJson()
                ->post("https://graph.facebook.com/{$apiVersion}/{$pixelId}/events?access_token={$accessToken}", $postData);

            if ($response->successful()) {
                $click->update([
                    'meta_capi_status' => 'sent',
                    'meta_sent_at' => now(),
                ]);
                return [
                    'success' => true,
                    'status' => 'sent',
                    'data' => $response->json(),
                ];
            }

            $click->update([
                'meta_capi_status' => 'failed',
            ]);
            return [
                'success' => false,
                'status' => 'failed',
                'error' => $response->json(),
            ];
        } catch (\Exception $e) {
            $click->update([
                'meta_capi_status' => 'failed',
            ]);
            return [
                'success' => false,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Dispatch verified Telegram Conversion to Meta Conversions API.
     * Default event is 'Subscribe' matching Meta Ads Manager optimization goal.
     */
    public function sendConversionEvent(Conversion $conversion, string $eventName = 'Subscribe'): array
    {
        $landingPage = $conversion->landingPage;
        [$pixelId, $accessToken] = $this->resolveCredentials($landingPage);

        // If not configured, record as skipped without failing the verified Telegram join
        if (empty($pixelId) || empty($accessToken)) {
            $conversion->update([
                'meta_capi_status' => 'skipped',
                'meta_capi_response' => 'Meta Pixel ID or Access Token not configured for client/system.',
            ]);
            return [
                'success' => false,
                'status' => 'skipped',
                'message' => 'Pixel credentials not configured',
            ];
        }

        $eventId = $conversion->meta_event_id ?: 'conv_' . $conversion->id . '_' . time();
        $conversion->update(['meta_event_id' => $eventId]);

        $session = $conversion->session;
        $userData = array_filter([
            'client_ip_address' => $session?->ip_address,
            'client_user_agent' => $session?->user_agent,
            'fbc' => $conversion->fbc ?: $session?->fbc,
            'fbp' => $conversion->fbp ?: $session?->fbp,
            'external_id' => $this->hashField($conversion->visitor_id ?: $conversion->telegram_user_id),
            'country' => $this->hashField($conversion->country ?? 'IN'),
        ]);

        $eventSourceUrl = $landingPage?->public_url ?? url('/lp/' . ($landingPage?->slug ?? 'kirtnix-digital'));
        $apiVersion = Setting::get('meta_api_version', 'v21.0');

        $eventPayload = [
            'event_name' => $eventName,
            'event_time' => $conversion->event_time ? $conversion->event_time->timestamp : time(),
            'event_id' => $eventId,
            'action_source' => 'website',
            'event_source_url' => $eventSourceUrl,
            'user_data' => $userData,
            'custom_data' => [
                'channel_title' => $conversion->channel?->title ?? 'Telegram Channel',
                'client_name' => $conversion->client?->company_name ?? 'Client',
                'campaign_name' => $conversion->campaign?->name ?? $conversion->utm_campaign ?? 'Direct',
                'source' => $conversion->source,
                'currency' => 'INR',
                'value' => 0.00,
            ],
        ];

        $postData = [
            'data' => [$eventPayload],
        ];

        if (!empty($landingPage?->meta_test_event_code)) {
            $postData['test_event_code'] = $landingPage->meta_test_event_code;
        }

        try {
            $response = Http::timeout(6)
                ->asJson()
                ->post("https://graph.facebook.com/{$apiVersion}/{$pixelId}/events?access_token={$accessToken}", $postData);

            if ($response->successful()) {
                $conversion->update([
                    'meta_capi_status' => 'sent',
                    'meta_sent_at' => now(),
                    'meta_capi_response' => json_encode($response->json()),
                ]);

                return [
                    'success' => true,
                    'status' => 'sent',
                    'data' => $response->json(),
                ];
            }

            $errorJson = $response->json();
            $conversion->update([
                'meta_capi_status' => 'failed',
                'meta_capi_response' => json_encode($errorJson),
                'meta_retries' => $conversion->meta_retries + 1,
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'error' => $errorJson,
            ];
        } catch (\Exception $e) {
            $conversion->update([
                'meta_capi_status' => 'failed',
                'meta_capi_response' => json_encode(['error' => $e->getMessage()]),
                'meta_retries' => $conversion->meta_retries + 1,
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }
}
