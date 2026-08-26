<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\PublicLandingPageController;
use App\Http\Controllers\CtaRedirectController;
use App\Http\Controllers\TelegramBotController;
use App\Http\Controllers\TelegramChannelController;
use App\Http\Controllers\MetaIntegrationController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AiCopilotController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\AccessManagementController;
use App\Http\Controllers\ConversionLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\PublicMarketingController;

/*
|--------------------------------------------------------------------------
| Public Marketing & Product Landing Pages
|--------------------------------------------------------------------------
*/
// Production Health & Diagnostic Check
Route::get('/healthz', function () {
    $migrationOutput = null;
    $migrationError = null;

    try {
        if (config('database.default') === 'sqlite') {
            $dbPath = config('database.connections.sqlite.database');
            if ($dbPath && $dbPath !== ':memory:') {
                $dir = dirname($dbPath);
                if (!is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
                if (!file_exists($dbPath)) {
                    @touch($dbPath);
                }
            }
        }

        if (!\Illuminate\Support\Facades\Schema::hasTable('users')) {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            $migrationOutput = \Illuminate\Support\Facades\Artisan::output();
            \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
        }
    } catch (\Throwable $e) {
        $migrationError = $e->getMessage();
    }

    $usersCount = 0;
    $clientsCount = 0;
    try {
        $usersCount = \App\Models\User::count();
        $clientsCount = \App\Models\Client::count();
    } catch (\Throwable $e) {
        // Table still missing
    }

    return response()->json([
        'status' => $usersCount > 0 ? 'ok' : 'pending_init',
        'php_version' => PHP_VERSION,
        'sqlite_loaded' => extension_loaded('pdo_sqlite'),
        'database_file_exists' => file_exists(database_path('database.sqlite')),
        'database_file_size' => file_exists(database_path('database.sqlite')) ? filesize(database_path('database.sqlite')) : 0,
        'users_count' => $usersCount,
        'clients_count' => $clientsCount,
        'app_key_set' => !empty(config('app.key')),
        'storage_writable' => is_writable(storage_path('framework/views')),
        'migration_output' => $migrationOutput,
        'migration_error' => $migrationError,
    ]);
});

Route::get('/', [PublicMarketingController::class, 'home'])->name('home');
Route::get('/analytics', function () {
    if (auth()->check()) {
        return app(AnalyticsController::class)->index(request());
    }
    return app(PublicMarketingController::class)->analytics();
})->name('public.analytics');

// Dedicated Shareable Client Analytics Page
Route::get('/analytics/{slug}', [AnalyticsController::class, 'detail'])->name('public.analytics.detail');
Route::get('/share/analytics/{slug}', [AnalyticsController::class, 'detail'])->name('public.analytics.share');

/*
|--------------------------------------------------------------------------
| Public Tracking & Landing Page Engine
|--------------------------------------------------------------------------
*/
// High-Speed CTA Click Interception & Direct Telegram Redirect
Route::get('/go/{token}', [CtaRedirectController::class, 'redirect'])->name('public.cta_redirect');
Route::get('/cta/{token}', [CtaRedirectController::class, 'redirect']);

// Dynamic Public Landing Pages
Route::get('/lp/{slug}', [PublicLandingPageController::class, 'show'])->name('public.landing_page');

// Standalone kx.js Dynamic Script for external/embedded landing pages (Vercel / Netlify / Custom)
Route::get('/api/public/kx.js', function (\Illuminate\Http\Request $request) {
    $slug = $request->query('lp') ?? $request->query('slug') ?? $request->query('token');
    $explicitPixel = $request->query('pixel') ?? $request->query('pixel_id') ?? $request->query('meta_pixel');

    $pixelId = $explicitPixel;
    $landingPage = null;

    if ($slug) {
        $landingPage = \App\Models\LandingPage::where('tracking_token', $slug)
            ->orWhere('slug', $slug)
            ->first();
    }

    if (!$landingPage && !$pixelId) {
        // Default to first active landing page
        $landingPage = \App\Models\LandingPage::where('is_active', true)->first();
    }

    if (!$pixelId && $landingPage) {
        $pixelId = $landingPage->meta_pixel_id ?: ($landingPage->client?->meta_pixel_id);
    }

    if (!$pixelId) {
        $pixelId = \App\Models\Setting::get('default_meta_pixel_id') ?? '';
    }

    $js = file_get_contents(public_path('assets/js/kx.js'));
    $configHeader = "window.__KX_CONFIG__ = window.__KX_CONFIG__ || {};\n";
    if ($pixelId) {
        $configHeader .= "window.__KX_CONFIG__.meta_pixel_id = " . json_encode((string) $pixelId) . ";\n";
    }
    if ($landingPage) {
        $configHeader .= "window.__KX_CONFIG__.slug = " . json_encode($landingPage->slug) . ";\n";
        $configHeader .= "window.__KX_CONFIG__.token = " . json_encode($landingPage->tracking_token) . ";\n";
    }

    return response($configHeader . "\n" . $js, 200, [
        'Content-Type' => 'application/javascript',
        'Cache-Control' => 'no-cache, private, must-revalidate',
        'Access-Control-Allow-Origin' => '*',
    ]);
});

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/auth', [AuthController::class, 'showLogin'])->name('auth');
    Route::post('/auth', [AuthController::class, 'login'])->name('auth.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Protected SaaS Dashboard & Workspace Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // 1. Overview Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 2. In-App Analytics (Global & Detail View)
    Route::get('/analytics-dashboard', [AnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('/analytics/detail/{slug?}', [AnalyticsController::class, 'detail'])->name('analytics.detail');
    Route::get('/analytics/page/{slug}', [AnalyticsController::class, 'detail'])->name('analytics.page_detail');
    Route::get('/analytics/export', [AnalyticsController::class, 'exportCsv'])->name('analytics.export');

    // 3. Clients Management
    Route::post('/clients/{client}/assign-ad-account', [ClientController::class, 'assignAdAccount'])->name('clients.assign_ad_account');
    Route::resource('clients', ClientController::class);

    // 4. Campaigns Management
    Route::resource('campaigns', CampaignController::class);

    // 5. Landing Pages Management & External Vercel Import
    Route::get('/landing-pages/import', [LandingPageController::class, 'import'])->name('landing-pages.import');
    Route::post('/landing-pages/import', [LandingPageController::class, 'storeImport'])->name('landing-pages.store_import');
    Route::post('/landing-pages/vercel-token', [LandingPageController::class, 'saveVercelToken'])->name('landing-pages.vercel_token');
    Route::post('/landing-pages/{landingPage}/meta-config', [LandingPageController::class, 'updateMetaConfig'])->name('landing-pages.update_meta_config');
    Route::resource('landing-pages', LandingPageController::class);

    // 6. Access Management / Team Permissions
    Route::get('/access-management', [AccessManagementController::class, 'index'])->name('access.index');
    Route::post('/access-management/member', [AccessManagementController::class, 'storeMember'])->name('access.storeMember');
    Route::put('/access-management/member/{user}', [AccessManagementController::class, 'updateMember'])->name('access.updateMember');
    Route::post('/access-management/member/{user}/toggle', [AccessManagementController::class, 'toggleMemberStatus'])->name('access.toggleMemberStatus');
    Route::delete('/access-management/member/{user}', [AccessManagementController::class, 'deleteMember'])->name('access.deleteMember');
    Route::post('/access-management/permission', [AccessManagementController::class, 'updatePermission'])->name('access.updatePermission');

    // 7. Conversion Logs / Meta Delivery Log
    Route::get('/conversion-logs', [ConversionLogController::class, 'index'])->name('conversion_logs.index');
    Route::post('/conversion-logs/retry', [ConversionLogController::class, 'retryQueue'])->name('conversion_logs.retry');

    // 8. Reports Management & AI Generator
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/generate-ai', [ReportController::class, 'generateAi'])->name('reports.generate_ai');
    Route::get('/reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export_csv');

    // 9. Telegram Bots & Channels Management
    Route::get('/telegram', [TelegramBotController::class, 'index'])->name('telegram.index');
    Route::get('/telegram-bots', [TelegramBotController::class, 'index'])->name('telegram_bots.index');
    Route::get('/telegram/create', [TelegramBotController::class, 'create'])->name('telegram.create');
    Route::post('/telegram', [TelegramBotController::class, 'store'])->name('telegram.store');
    Route::post('/telegram/{bot}/sync', [TelegramBotController::class, 'syncWebhook'])->name('telegram.sync');
    Route::post('/telegram/{bot}/health', [TelegramBotController::class, 'health'])->name('telegram.health');
    Route::delete('/telegram/{bot}', [TelegramBotController::class, 'destroy'])->name('telegram.destroy');

    // Telegram Tracked Channels & Auto-Detection
    Route::post('/telegram/channels/verify', [TelegramChannelController::class, 'verify'])->name('telegram.channels.verify');
    Route::get('/telegram/channels/auto-detect', [TelegramChannelController::class, 'autoDetect'])->name('telegram.channels.auto_detect');
    Route::delete('/telegram/channels/{channel}', [TelegramChannelController::class, 'destroy'])->name('telegram.channels.destroy');

    // 10. Meta / Facebook OAuth & Integration Routes
    Route::get('/meta/oauth/redirect', [MetaIntegrationController::class, 'oauthRedirect'])->name('meta.oauth.redirect');
    Route::get('/meta/oauth/callback', [MetaIntegrationController::class, 'oauthCallback'])->name('meta.oauth.callback');
    Route::post('/meta/connect', [MetaIntegrationController::class, 'connect'])->name('meta.connect');
    Route::post('/meta/sync', [MetaIntegrationController::class, 'sync'])->name('meta.sync');
    Route::post('/meta/disconnect', [MetaIntegrationController::class, 'disconnect'])->name('meta.disconnect');
    Route::post('/meta/ad-accounts', [MetaIntegrationController::class, 'storeAdAccount'])->name('meta.ad_accounts.store');
    Route::delete('/meta/ad-accounts/{adAccount}', [MetaIntegrationController::class, 'destroyAdAccount'])->name('meta.ad_accounts.destroy');

    // 11. Notifications Center
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.mark_read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark_all_read');

    // 12. KirtniX AI Copilot
    Route::get('/ai-copilot', [AiCopilotController::class, 'index'])->name('ai.index');
    Route::get('/kirtnix-ai', [AiCopilotController::class, 'index'])->name('kirtnix_ai.index');
    Route::post('/ai/chat', [AiCopilotController::class, 'chat'])->name('ai.chat');

    // 13. Settings & Hostinger Deployment
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');

    // 14. Profile & Security
    Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile.show');
    Route::post('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

    // 15. Help, Support & FAQ
    Route::get('/support', [HelpController::class, 'support'])->name('support.index');
    Route::post('/support/ticket', [HelpController::class, 'submitTicket'])->name('support.ticket');
    Route::get('/faq', [HelpController::class, 'faq'])->name('faq.index');

    // 16. Security & Login Requests
    Route::get('/login-requests', [SecurityController::class, 'loginRequests'])->name('login_requests.index');
    Route::post('/login-requests/{loginRequest}/status', [SecurityController::class, 'updateStatus'])->name('login_requests.update_status');
    Route::post('/login-requests/{loginRequest}/revoke', [SecurityController::class, 'revokeAccess'])->name('login_requests.revoke');

    // 17. Global Command Bar Search API
    Route::get('/api/search', [SearchController::class, 'search'])->name('api.search');
});
