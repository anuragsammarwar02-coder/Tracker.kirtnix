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

class MetaIntegrationController extends Controller
{
    public function __construct(protected MetaSyncService $metaSyncService) {}

    /**
     * Connect Meta account with real access token.
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
                ->with('error', 'No Meta account connected. Please connect your token first.');
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
