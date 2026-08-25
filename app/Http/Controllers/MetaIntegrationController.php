<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\MetaBusiness;
use App\Models\MetaConnection;
use App\Models\Setting;
use App\Services\MetaSyncService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaIntegrationController extends Controller
{
    protected string $graphApiVersion = 'v19.0';
    protected string $facebookAuthUrl = 'https://www.facebook.com';
    protected string $graphApiBase = 'https://graph.facebook.com';

    public function __construct(protected MetaSyncService $metaSyncService) {}

    /**
     * Redirect user to official Facebook OAuth dialog.
     */
    public function oauthRedirect(Request $request): RedirectResponse
    {
        $appId = Setting::get('meta_app_id') ?? env('META_APP_ID', '4520673831531016');
        $redirectUri = route('meta.oauth.callback');
        $scopes = [
            'ads_read',
            'ads_management',
            'read_insights',
            'business_management',
            'pages_show_list',
            'email',
            'public_profile',
        ];

        $state = csrf_token();
        session(['meta_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'scope' => implode(',', $scopes),
        ]);

        return redirect()->away("{$this->facebookAuthUrl}/{$this->graphApiVersion}/dialog/oauth?{$query}");
    }

    /**
     * Handle incoming OAuth callback from Facebook.
     */
    public function oauthCallback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            $err = $request->input('error_description', $request->input('error', 'Authentication cancelled.'));
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Facebook login failed: ' . $err);
        }

        $code = $request->input('code');
        if (empty($code)) {
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'No authorization code received from Facebook.');
        }

        $appId = Setting::get('meta_app_id') ?? env('META_APP_ID', '4520673831531016');
        $appSecret = Setting::get('meta_app_secret') ?? env('META_APP_SECRET', '4400729382f0cf94b61599e165019281');
        $redirectUri = route('meta.oauth.callback');

        try {
            // Step 1: Exchange code for short-lived access token
            $res = Http::withoutVerifying()->timeout(15)->get("{$this->graphApiBase}/{$this->graphApiVersion}/oauth/access_token", [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);

            if (!$res->successful() || empty($res->json('access_token'))) {
                $err = $res->json('error.message') ?? 'Failed to exchange authorization code.';
                return redirect()->route('settings.index', ['tab' => 'meta'])
                    ->with('error', 'Meta OAuth error: ' . $err);
            }

            $shortLivedToken = $res->json('access_token');

            // Step 2: Upgrade to Long-Lived Token (60 Days)
            $exchangeRes = Http::withoutVerifying()->timeout(15)->get("{$this->graphApiBase}/{$this->graphApiVersion}/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $shortLivedToken,
            ]);

            $finalToken = $exchangeRes->successful() && !empty($exchangeRes->json('access_token'))
                ? $exchangeRes->json('access_token')
                : $shortLivedToken;

            // Step 3: Save and Sync Profile & Ad Accounts
            Setting::set('meta_system_user_token', $finalToken, 'meta');
            $connection = $this->metaSyncService->connectAccessToken($finalToken, auth()->id());
            $syncResult = $this->metaSyncService->syncAll($connection);

            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('success', "Connected with Facebook as {$connection->facebook_name}! " . ($syncResult['message'] ?? 'Ad accounts synced.'));
        } catch (\Exception $e) {
            Log::error('Meta OAuth Callback Exception: ' . $e->getMessage());
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'OAuth Error: ' . $e->getMessage());
        }
    }

    /**
     * Connect Meta account with direct token (fallback).
     */
    public function connect(Request $request): RedirectResponse
    {
        $token = trim((string) $request->input('access_token'));
        if (empty($token)) {
            $token = Setting::get('meta_system_user_token') ?? env('META_SYSTEM_USER_TOKEN');
        }

        if (empty($token)) {
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Please enter your Meta System User Access Token.');
        }

        Setting::set('meta_system_user_token', $token, 'meta');
        if ($appId = $request->input('app_id')) {
            Setting::set('meta_app_id', trim($appId), 'meta');
        }
        if ($appSecret = $request->input('app_secret')) {
            Setting::set('meta_app_secret', trim($appSecret), 'meta');
        }

        $connection = $this->metaSyncService->connectAccessToken($token, auth()->id());

        return redirect()->route('settings.index', ['tab' => 'meta'])
            ->with('success', 'Meta account connected successfully! Profile: ' . ($connection->facebook_name ?? 'Connected'));
    }

    /**
     * Sync Meta ad accounts, campaigns, and insights.
     */
    public function sync(): JsonResponse|RedirectResponse
    {
        $connection = MetaConnection::first();
        if (!$connection) {
            $token = Setting::get('meta_system_user_token') ?? env('META_SYSTEM_USER_TOKEN');
            if ($token) {
                $connection = $this->metaSyncService->connectAccessToken($token, auth()->id());
            }
        }

        if (!$connection) {
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'No Meta account connected. Please connect with Facebook first.');
        }

        $res = $this->metaSyncService->syncAll($connection);

        if (request()->wantsJson()) {
            return response()->json($res);
        }

        return redirect()->back()->with('success', $res['message']);
    }

    /**
     * Disconnect Meta agency connection completely.
     */
    public function disconnect(): RedirectResponse
    {
        MetaConnection::query()->delete();
        AdAccount::query()->delete();
        MetaBusiness::query()->delete();
        Setting::set('meta_system_user_token', null);

        return redirect()->route('settings.index', ['tab' => 'meta'])
            ->with('info', 'Meta agency connection disconnected and ad accounts removed.');
    }
}
