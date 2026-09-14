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
     * Redirect user to official Facebook OAuth dialog with re-authentication / account switch support.
     */
    public function oauthRedirect(Request $request): RedirectResponse
    {
        $appId = Setting::get('meta_app_id') ?? env('META_APP_ID');
        $appSecret = Setting::get('meta_app_secret') ?? env('META_APP_SECRET');

        // Check if real custom Meta App credentials exist
        if (empty($appId) || empty($appSecret) || $appId === '4520673831531016') {
            return redirect()->route('settings.index', ['tab' => 'meta', 'open_manual' => '1'])
                ->with('info', 'To connect via Facebook Login Dialog, please save your verified Meta App ID & Secret below, or connect directly using your Meta System User Access Token.');
        }

        $redirectUri = url()->secure(route('meta.oauth.callback', [], false));
        if (!str_starts_with($redirectUri, 'https://') && (request()->secure() || request()->header('X-Forwarded-Proto') === 'https')) {
            $redirectUri = 'https://' . request()->getHttpHost() . '/meta/oauth/callback';
        }

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
        $nonce = bin2hex(random_bytes(16));
        session(['meta_oauth_state' => $state, 'meta_oauth_nonce' => $nonce]);

        // Official Meta OAuth parameter for re-authentication and re-requesting permissions
        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'response_type' => 'code',
            'scope' => implode(',', $scopes),
            'auth_type' => 'reauthenticate,rerequest',
            'auth_nonce' => $nonce,
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
            Log::warning("Facebook OAuth Callback Error: {$err}");
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Facebook connection failed: ' . $err);
        }

        $code = $request->input('code');
        if (empty($code)) {
            Log::warning('Facebook OAuth Error: No authorization code received.');
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Facebook connection failed. No authorization code received.');
        }

        $appId = Setting::get('meta_app_id') ?? env('META_APP_ID');
        $appSecret = Setting::get('meta_app_secret') ?? env('META_APP_SECRET');
        $redirectUri = url()->secure(route('meta.oauth.callback', [], false));
        if (!str_starts_with($redirectUri, 'https://') && (request()->secure() || request()->header('X-Forwarded-Proto') === 'https')) {
            $redirectUri = 'https://' . request()->getHttpHost() . '/meta/oauth/callback';
        }

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
                Log::error('Meta OAuth exchange error: ' . $err, ['response' => $res->json()]);
                return redirect()->route('settings.index', ['tab' => 'meta'])
                    ->with('error', 'Facebook connection failed: ' . $err);
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

            // Step 3: Check whether this Facebook User is already connected
            $profileRes = Http::withoutVerifying()->timeout(8)->get("{$this->graphApiBase}/{$this->graphApiVersion}/me", [
                'access_token' => $finalToken,
                'fields' => 'id,name,email',
            ]);
            $fbUserId = $profileRes->successful() ? $profileRes->json('id') : null;
            $isAlreadyConnected = $fbUserId && MetaConnection::where('facebook_user_id', $fbUserId)->exists();

            // Step 4: Save Connection & Sync Accessible Business Managers and Ad Accounts
            $connection = $this->metaSyncService->connectAccessToken($finalToken, auth()->id(), null, 'oauth');
            $syncResult = $this->metaSyncService->syncAll($connection);

            $accountsCount = $syncResult['accounts_count'] ?? AdAccount::where('meta_connection_id', $connection->id)->count();

            // Set this connection as the active connection
            Setting::set('active_meta_connection_id', (string) $connection->id, 'meta');

            if ($isAlreadyConnected) {
                return redirect()->route('settings.index', ['tab' => 'meta'])
                    ->with('info', "Facebook account '{$connection->facebook_name}' (ID: {$connection->facebook_user_id}) was already connected. Access token and {$accountsCount} ad account(s) have been refreshed without creating a duplicate profile.");
            }

            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('success', "New Facebook account '{$connection->facebook_name}' (ID: {$connection->facebook_user_id}) connected successfully! ({$accountsCount} accessible Meta Ad Accounts synced).");
        } catch (\Exception $e) {
            Log::error('Meta OAuth Callback Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'Facebook connection failed. Please try again.');
        }
    }

    /**
     * Live Test Connection for Meta System User Token.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $token = trim((string) $request->input('access_token'));
        if (empty($token)) {
            return response()->json([
                'valid' => false,
                'error' => 'Please enter a Meta Access Token to test.',
            ], 422);
        }

        $result = $this->metaSyncService->validateToken($token);

        return response()->json($result, $result['valid'] ? 200 : 400);
    }

    /**
     * Connect Meta account with direct token (System User / Permanent Graph API Token).
     */
    public function connect(Request $request): RedirectResponse
    {
        $token = trim((string) $request->input('access_token'));
        if (empty($token)) {
            $token = Setting::get('meta_system_user_token');
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

        $systemUserId = $request->input('system_user_id') ? trim($request->input('system_user_id')) : null;
        $customName = $request->input('custom_name') ? trim($request->input('custom_name')) : null;
        $adAccountId = $request->input('ad_account_id');

        $connection = $this->metaSyncService->connectAccessToken(
            $token, 
            auth()->id(), 
            $adAccountId, 
            'system_user', 
            $systemUserId, 
            $customName
        );

        $syncResult = $this->metaSyncService->syncAll($connection);
        $syncedCount = AdAccount::where('meta_connection_id', $connection->id)->count();

        // Set as active connection
        Setting::set('active_meta_connection_id', (string) $connection->id, 'meta');

        return redirect()->route('settings.index', ['tab' => 'meta'])
            ->with('success', "Meta account connected successfully as '{$connection->facebook_name}' (ID: {$connection->facebook_user_id})! ({$syncedCount} ad accounts synced)");
    }

    /**
     * Set a Meta Connection as the active/primary connection.
     */
    public function selectConnection(MetaConnection $metaConnection): RedirectResponse
    {
        Setting::set('active_meta_connection_id', (string) $metaConnection->id, 'meta');

        return redirect()->back()
            ->with('success', "Active Meta connection set to '{$metaConnection->facebook_name}' (ID: {$metaConnection->facebook_user_id}).");
    }

    /**
     * Sync ALL connected Meta connections and their ad accounts.
     */
    public function sync(): JsonResponse|RedirectResponse
    {
        $connections = MetaConnection::all();
        if ($connections->isEmpty()) {
            $token = Setting::get('meta_system_user_token');
            if ($token) {
                $connection = $this->metaSyncService->connectAccessToken($token, auth()->id(), null, 'system_user');
                $connections = collect([$connection]);
            }
        }

        if ($connections->isEmpty()) {
            return redirect()->route('settings.index', ['tab' => 'meta'])
                ->with('error', 'No Meta accounts connected. Please connect your Facebook account or System User Token first.');
        }

        $totalSynced = 0;
        foreach ($connections as $conn) {
            $res = $this->metaSyncService->syncAll($conn);
            $totalSynced += ($res['accounts_count'] ?? 0);
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Successfully synced {$totalSynced} ad accounts across " . $connections->count() . ' connected Meta account(s).',
                'accounts_count' => $totalSynced,
            ]);
        }

        return redirect()->back()->with('success', "Successfully synced {$totalSynced} ad accounts across " . $connections->count() . ' connected Meta account(s).');
    }

    /**
     * Sync single MetaConnection.
     */
    public function syncConnection(MetaConnection $metaConnection): RedirectResponse
    {
        $res = $this->metaSyncService->syncAll($metaConnection);
        return redirect()->back()->with('success', "Synced accounts for '{$metaConnection->facebook_name}': " . ($res['message'] ?? 'Done'));
    }

    /**
     * Disconnect ALL Meta connections.
     */
    public function disconnect(): RedirectResponse
    {
        MetaConnection::query()->delete();
        AdAccount::query()->delete();
        MetaBusiness::query()->delete();
        Setting::where('key', 'meta_system_user_token')->delete();
        Setting::where('key', 'active_meta_connection_id')->delete();

        return redirect()->route('settings.index', ['tab' => 'meta'])
            ->with('info', 'All Meta connections disconnected successfully.');
    }

    /**
     * Disconnect a single MetaConnection without affecting other connected Facebook accounts.
     */
    public function disconnectConnection(MetaConnection $metaConnection): RedirectResponse
    {
        $name = $metaConnection->facebook_name ?? 'Facebook User';
        $activeId = Setting::get('active_meta_connection_id');

        // Unlink or delete ad accounts belonging exclusively to this connection
        AdAccount::where('meta_connection_id', $metaConnection->id)->delete();
        MetaBusiness::where('meta_connection_id', $metaConnection->id)->delete();
        $metaConnection->delete();

        if ($activeId == $metaConnection->id) {
            $next = MetaConnection::first();
            if ($next) {
                Setting::set('active_meta_connection_id', (string) $next->id, 'meta');
            } else {
                Setting::where('key', 'active_meta_connection_id')->delete();
            }
        }

        $remainingCount = MetaConnection::count();

        return redirect()->route('settings.index', ['tab' => 'meta'])
            ->with('info', "Meta account '{$name}' disconnected successfully. ({$remainingCount} connected account(s) remaining)");
    }

    /**
     * Store custom or manually registered Meta Ad Account
     */
    public function storeAdAccount(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'account_id' => ['required', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', 'string', 'max:50'],
            'meta_connection_id' => ['nullable', 'exists:meta_connections,id'],
        ]);

        $rawId = trim($validated['account_id']);
        $accId = str_starts_with($rawId, 'act_') ? $rawId : ('act_' . $rawId);

        $connection = isset($validated['meta_connection_id']) 
            ? MetaConnection::find($validated['meta_connection_id'])
            : MetaConnection::first();

        if (!$connection) {
            $token = Setting::get('meta_system_user_token');
            if ($token) {
                $connection = $this->metaSyncService->connectAccessToken($token, auth()->id(), null, 'system_user');
            }
        }

        $adAccount = AdAccount::updateOrCreate(
            ['account_id' => $accId],
            [
                'meta_connection_id' => $connection?->id,
                'name' => $validated['name'],
                'currency' => strtoupper($validated['currency'] ?? 'INR'),
                'status' => ucfirst($validated['status'] ?? 'Active'),
                'is_active' => true,
                'last_synced_at' => now(),
            ]
        );

        // Try syncing live data immediately from Meta
        $this->metaSyncService->syncSingleAdAccount($adAccount);

        return redirect()->back()->with('success', "Ad Account '{$adAccount->name}' ({$accId}) saved & synced successfully!");
    }

    /**
     * Remove / Delete a Meta Ad Account
     */
    public function destroyAdAccount(AdAccount $adAccount): RedirectResponse
    {
        $name = $adAccount->name;
        \App\Models\Client::where('ad_account_id', $adAccount->id)->update(['ad_account_id' => null]);
        $adAccount->delete();

        return redirect()->back()->with('success', "Ad Account '{$name}' removed successfully.");
    }
}
