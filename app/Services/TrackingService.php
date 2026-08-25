<?php

namespace App\Services;

use App\Models\LandingPage;
use App\Models\LandingPageView;
use App\Models\TrackingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TrackingService
{
    /**
     * Get or create persistent visitor identifier.
     */
    public function getVisitorId(Request $request): string
    {
        $visitorId = $request->input('visitor_id') ?? $request->cookie('kx_visitor_id') ?? $request->cookie('_kx_vid');
        if ($visitorId && (Str::isUuid($visitorId) || str_starts_with($visitorId, 'kx_'))) {
            return $visitorId;
        }
        return (string) Str::uuid();
    }

    /**
     * Extract & parse device type from User-Agent.
     */
    public function parseDeviceType(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'desktop';
        }
        $ua = strtolower($userAgent);
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobi))/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/(mobile|ipod|iphone|android|blackberry|iemobile|kindle|opera mini|opera mobi)/i', $ua)) {
            return 'mobile';
        }
        if (preg_match('/(bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegrambot)/i', $ua)) {
            return 'bot';
        }
        return 'desktop';
    }

    /**
     * Extract Browser name from User-Agent.
     */
    public function parseBrowser(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }
        $ua = $userAgent;
        if (preg_match('/Chrome\/([0-9\.]+)/i', $ua) && !preg_match('/Edg|OPR/i', $ua)) {
            return 'Chrome';
        } elseif (preg_match('/Safari\/([0-9\.]+)/i', $ua) && !preg_match('/Chrome/i', $ua)) {
            return 'Safari';
        } elseif (preg_match('/Firefox\/([0-9\.]+)/i', $ua)) {
            return 'Firefox';
        } elseif (preg_match('/Edg\/([0-9\.]+)/i', $ua)) {
            return 'Edge';
        } elseif (preg_match('/OPR\/([0-9\.]+)/i', $ua)) {
            return 'Opera';
        }
        return 'Other';
    }

    /**
     * Extract Operating System from User-Agent.
     */
    public function parseOs(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown';
        }
        $ua = $userAgent;
        if (preg_match('/Windows NT 10/i', $ua)) return 'Windows 10/11';
        if (preg_match('/Windows/i', $ua)) return 'Windows';
        if (preg_match('/iPhone|iPad|iPod/i', $ua)) return 'iOS';
        if (preg_match('/Android/i', $ua)) return 'Android';
        if (preg_match('/Macintosh|Mac OS X/i', $ua)) return 'macOS';
        if (preg_match('/Linux/i', $ua)) return 'Linux';
        return 'Other';
    }

    /**
     * Record a landing page visit and return tracking session + view record.
     */
    public function recordLandingPageView(LandingPage $landingPage, Request $request): array
    {
        $visitorId = $this->getVisitorId($request);
        $userAgent = $request->userAgent();
        $ip = $request->ip();
        $ipHash = hash('sha256', $ip . config('app.key'));
        $sessionId = $request->hasSession() ? $request->session()->getId() : (string) Str::uuid();

        // Check if visitor has visited this landing page recently (within last 24h) for uniqueness
        $existingView = LandingPageView::where('landing_page_id', $landingPage->id)
            ->where('visitor_id', $visitorId)
            ->where('created_at', '>=', now()->subHours(24))
            ->first();

        $isUnique = is_null($existingView);

        // Capture UTM & Meta parameters from query string or POST body
        $utmSource = $request->input('utm_source') ?? $request->query('utm_source');
        $utmMedium = $request->input('utm_medium') ?? $request->query('utm_medium');
        $utmCampaign = $request->input('utm_campaign') ?? $request->query('utm_campaign');
        $utmTerm = $request->input('utm_term') ?? $request->query('utm_term');
        $utmContent = $request->input('utm_content') ?? $request->query('utm_content');
        $fbclid = $request->input('fbclid') ?? $request->query('fbclid');
        $gclid = $request->input('gclid') ?? $request->query('gclid');
        $fbc = $request->input('fbc') ?? $request->cookie('_fbc') ?? ($fbclid ? "fb.1." . time() . ".{$fbclid}" : null);
        $fbp = $request->input('fbp') ?? $request->cookie('_fbp') ?? $request->query('_fbp');

        $session = TrackingSession::create([
            'session_id' => $sessionId,
            'visitor_id' => $visitorId,
            'client_id' => $landingPage->client_id,
            'landing_page_id' => $landingPage->id,
            'campaign_id' => $landingPage->campaign_id,
            'ip_hash' => $ipHash,
            'user_agent' => $userAgent,
            'device_type' => $this->parseDeviceType($userAgent),
            'browser' => $this->parseBrowser($userAgent),
            'os' => $this->parseOs($userAgent),
            'referrer' => $request->header('referer'),
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'utm_term' => $utmTerm,
            'utm_content' => $utmContent,
            'fbclid' => $fbclid,
            'gclid' => $gclid,
            'fbc' => $fbc,
            'fbp' => $fbp,
        ]);

        $view = LandingPageView::create([
            'tracking_session_id' => $session->id,
            'landing_page_id' => $landingPage->id,
            'client_id' => $landingPage->client_id,
            'campaign_id' => $landingPage->campaign_id,
            'visitor_id' => $visitorId,
            'is_unique' => $isUnique,
            'viewed_at' => now(),
        ]);

        return [
            'session' => $session,
            'view' => $view,
            'visitor_id' => $visitorId,
            'is_unique' => $isUnique,
        ];
    }
}
