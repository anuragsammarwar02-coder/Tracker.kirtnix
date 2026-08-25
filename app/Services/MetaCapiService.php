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
     * Dispatch server-side Conversion Event to Meta Graph API for a generic request.
     */
    public function sendEvent(
        LandingPage $landingPage,
        string $eventName,
        string $eventId,
        Request $request,
        array $customData = []
    ): array {
        $pixelId = trim($landingPage->meta_pixel_id ?? Setting::get('meta_pixel_id', ''));
        $accessToken = trim($landingPage->meta_access_token ?? Setting::get('meta_access_token', ''));

        if (empty($pixelId) || empty($accessToken)) {
            return [
                'success' => false,
                'message' => 'Pixel ID or Access Token is missing',
            ];
        }

        $fbclid = $request->query('fbclid');
        $fbc = $request->cookie('_fbc') ?? ($fbclid ? "fb.1." . time() . ".{$fbclid}" : null);
        $fbp = $request->cookie('_fbp');

        $userData = [
            'client_ip_address' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
        ];

        if ($fbc) $userData['fbc'] = $fbc;
        if ($fbp) $userData['fbp'] = $fbp;

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
            $response = Http::timeout(5)
                ->asJson()
                ->post("https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$accessToken}", $postData);

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
     * Dispatch verified Telegram Conversion to Meta Conversions API.
     */
    public function sendConversionEvent(Conversion $conversion, string $eventName = 'CompleteRegistration'): array
    {
        $landingPage = $conversion->landingPage;
        $pixelId = trim($landingPage?->meta_pixel_id ?? Setting::get('meta_pixel_id', ''));
        $accessToken = trim($landingPage?->meta_access_token ?? Setting::get('meta_access_token', ''));

        // If not configured, record as skipped/pending without failing the verified Telegram join
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

        $userData = array_filter([
            'fbc' => $conversion->fbc,
            'fbp' => $conversion->fbp,
            'external_id' => $this->hashField($conversion->visitor_id ?: $conversion->telegram_user_id),
            'country' => $this->hashField($conversion->country ?? 'IN'),
        ]);

        $eventPayload = [
            'event_name' => $eventName,
            'event_time' => $conversion->event_time ? $conversion->event_time->timestamp : time(),
            'event_id' => $eventId,
            'action_source' => 'other',
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
                ->post("https://graph.facebook.com/v19.0/{$pixelId}/events?access_token={$accessToken}", $postData);

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
