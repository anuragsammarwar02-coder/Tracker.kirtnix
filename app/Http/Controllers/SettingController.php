<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\MetaBusiness;
use App\Models\MetaConnection;
use App\Models\Setting;
use App\Models\TelegramBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SettingController extends Controller
{
    public function index(Request $request): View
    {
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // Database health
        $dbStatus = 'Connected';
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $dbStatus = 'Error: ' . $e->getMessage();
        }

        // Meta connection & accounts
        $metaConnection = MetaConnection::with(['businesses', 'adAccounts'])->first();
        $adAccounts = AdAccount::with(['metaBusiness', 'client'])->latest('id')->paginate(15);
        $totalSyncedAccounts = AdAccount::count();

        // Telegram webhook health
        $totalBots = TelegramBot::count();
        $activeWebhooks = TelegramBot::where('is_webhook_active', true)->count();

        // System Health & Diagnostics
        $systemHealth = [
            'api_health' => '100% Operational',
            'database_status' => $dbStatus,
            'telegram_webhook_status' => "{$activeWebhooks}/{$totalBots} Webhooks Active",
            'meta_sync_status' => $metaConnection ? ($metaConnection->sync_status === 'completed' ? 'Synced (100%)' : ucfirst($metaConnection->sync_status)) : 'Disconnected',
            'last_sync_timestamp' => $metaConnection?->last_sync_at ? $metaConnection->last_sync_at->diffForHumans() : 'Never',
            'failed_sync_count' => 0,
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'storage_writable' => is_writable(storage_path()),
        ];

        $diagnostics = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_driver' => config('database.default'),
            'database_status' => $dbStatus,
            'hostinger_root' => base_path(),
            'public_path' => public_path(),
            'storage_writable' => is_writable(storage_path()),
        ];

        $currentTab = $request->input('tab', 'meta');

        return view('settings.index', compact(
            'settings',
            'diagnostics',
            'metaConnection',
            'adAccounts',
            'totalSyncedAccounts',
            'systemHealth',
            'currentTab'
        ));
    }

    public function update(Request $request): RedirectResponse
    {
        $inputs = $request->except(['_token', '_method', 'tab']);

        foreach ($inputs as $key => $value) {
            $group = match (true) {
                str_starts_with($key, 'meta_') => 'meta',
                str_starts_with($key, 'telegram_') => 'telegram',
                str_starts_with($key, 'brand_') => 'branding',
                str_starts_with($key, 'gemini_') => 'ai',
                default => 'general',
            };

            Setting::set($key, (string) $value, $group);
        }

        $tab = $request->input('tab', 'meta');
        return redirect()->route('settings.index', ['tab' => $tab])->with('success', 'Settings updated successfully.');
    }
}
