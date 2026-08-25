<?php

namespace App\Http\Controllers;

use App\Models\Cta;
use App\Services\RedirectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CtaRedirectController extends Controller
{
    protected RedirectService $redirectService;

    public function __construct(RedirectService $redirectService)
    {
        $this->redirectService = $redirectService;
    }

    public function redirect(string $token, Request $request)
    {
        $cta = Cta::with(['landingPage', 'client'])
            ->where('tracking_token', $token)
            ->where('is_active', true)
            ->firstOrFail();

        $result = $this->redirectService->handleCtaClick($cta, $request);

        $deepLink = $result['deep_link'];
        $webUrl = $result['web_url'];
        $visitorId = $result['visitor_id'];

        Cookie::queue('kx_visitor_id', $visitorId, 60 * 24 * 365);

        // If client requests raw HTTP 302
        if ($request->query('raw') === '1') {
            return redirect()->away($webUrl);
        }

        // Return high-performance fast launcher page that triggers Telegram app deep link + web fallback
        return response()->view('tracking.redirect', [
            'deepLink' => $deepLink,
            'webUrl' => $webUrl,
            'pageTitle' => $cta->landingPage?->title ?? 'Opening Telegram...',
        ]);
    }
}
