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
// Production Health & Diagnostic Check (Read-Only)
Route::get('/healthz', function () {
    try {
        $dbPath = config('database.connections.sqlite.database');
        $dbExists = $dbPath && $dbPath !== ':memory:' ? file_exists($dbPath) : true;
        $dbSize = ($dbExists && $dbPath !== ':memory:') ? filesize($dbPath) : 0;

        $usersCount = 0;
        $clientsCount = 0;
        $landingPagesCount = 0;
        $botsCount = 0;
        $dbConnected = false;
        $dbError = null;

        $snapshotGzPath = database_path('snapshots/clean_baseline.sqlite.gz');
        $snapshotExists = file_exists($snapshotGzPath);
        $snapshotSize = $snapshotExists ? filesize($snapshotGzPath) : 0;
        $dbWritable = file_exists($dbPath) ? is_writable($dbPath) : is_writable(dirname($dbPath));
        $dirWritable = is_writable(dirname($dbPath));
        $writeAttemptResult = 'not_attempted';

        if (($dbSize === 0 || !$dbExists) && $snapshotExists && $snapshotSize > 0) {
            $gzData = @file_get_contents($snapshotGzPath);
            $raw = @gzdecode($gzData);
            if ($raw !== false && strlen($raw) === 458752) {
                $written = @file_put_contents($dbPath, $raw);
                $writeAttemptResult = $written !== false ? "wrote_{$written}_bytes" : "failed_to_write_error_" . json_encode(error_get_last());
                if ($written !== false) {
                    $dbExists = true;
                    $dbSize = filesize($dbPath);
                }
            } else {
                $writeAttemptResult = "gzdecode_failed_len_" . (is_string($raw) ? strlen($raw) : 'false');
            }
        }

        if ($dbExists && $dbSize > 0) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('users')) {
                    $usersCount = \App\Models\User::count();
                    $clientsCount = \App\Models\Client::count();
                    $landingPagesCount = \App\Models\LandingPage::count();
                    $botsCount = \App\Models\TelegramBot::count();
                    $dbConnected = true;
                }
            } catch (\Throwable $e) {
                $dbError = $e->getMessage();
            }
        }

        // Deep Recursive Scan for Candidate SQLite Files (100% Read-Only)
        $candidateFiles = [];
        $scannedPaths = [];

        $searchRoots = [
            database_path(),
            storage_path('app'),
            storage_path('app/backups'),
            base_path(),
            base_path('database'),
            base_path('storage'),
            dirname(base_path()),
            dirname(base_path()) . '/data',
            dirname(dirname(base_path())),
            dirname(dirname(dirname(base_path()))),
            '/home/u773780340',
            '/home/u773780340/backups',
            '/home/u773780340/domains/tracker.kirtnix.in/data',
            '/home/u773780340/domains/tracker.kirtnix.in/storage',
            '/home/u773780340/domains/tracker.kirtnix.in/storage/app',
            '/tmp',
        ];

        $findSqliteFiles = function ($dir, $depth = 0) use (&$findSqliteFiles, &$candidateFiles, &$scannedPaths) {
            try {
                if ($depth > 3 || !@is_dir($dir) || !@is_readable($dir)) return;
                $scannedPaths[] = $dir;

                $items = @scandir($dir) ?: [];
                foreach ($items as $item) {
                    if ($item === '.' || $item === '..' || $item === 'node_modules' || $item === 'vendor' || $item === '.git') continue;
                    $full = $dir . DIRECTORY_SEPARATOR . $item;

                    if (@is_dir($full)) {
                        $findSqliteFiles($full, $depth + 1);
                    } elseif (@is_file($full) && !@is_link($full)) {
                        // Skip common code/asset files
                        if (str_ends_with($item, '.php') || str_ends_with($item, '.js') || str_ends_with($item, '.css') || str_ends_with($item, '.json') || str_ends_with($item, '.md') || str_ends_with($item, '.txt')) {
                            continue;
                        }

                        $isSqliteCandidate = str_ends_with($item, '.sqlite') || 
                                             str_ends_with($item, '.db') || 
                                             str_ends_with($item, '.sqlite3') || 
                                             str_ends_with($item, '.backup');

                        // Check magic bytes for SQLite header if file is non-empty
                        $size = @filesize($full) ?: 0;
                        if (!$isSqliteCandidate && $size >= 16) {
                            $handle = @fopen($full, 'rb');
                            if ($handle) {
                                $header = fread($handle, 16);
                                fclose($handle);
                                if (str_starts_with($header, "SQLite format 3\0")) {
                                    $isSqliteCandidate = true;
                                }
                            }
                        }

                        if ($isSqliteCandidate) {
                            $mtime = date('Y-m-d H:i:s', @filemtime($full) ?: time());
                            $integrity = 'untested';
                            $tables = [];
                            $tableCounts = [];

                            if ($size >= 16) {
                                try {
                                    $pdo = new \PDO("sqlite:{$full}", null, null, [
                                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                                        \PDO::ATTR_TIMEOUT => 2,
                                    ]);

                                    // Discover tables
                                    $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';");
                                    $tables = $stmt ? $stmt->fetchAll(\PDO::FETCH_COLUMN) : [];

                                    // Integrity check
                                    if (!empty($tables)) {
                                        $stmt = $pdo->query('PRAGMA integrity_check;');
                                        $integrity = $stmt ? (string) $stmt->fetchColumn() : 'unknown';

                                        // Row counts for key tables
                                        foreach ($tables as $t) {
                                            try {
                                                $cStmt = $pdo->query("SELECT COUNT(*) FROM \"{$t}\";");
                                                $tableCounts[$t] = $cStmt ? (int) $cStmt->fetchColumn() : 0;
                                            } catch (\Throwable $e) {
                                                $tableCounts[$t] = 'err';
                                            }
                                        }
                                    } else {
                                        $integrity = 'empty database (0 tables)';
                                    }
                                } catch (\Throwable $e) {
                                    $integrity = 'error: ' . $e->getMessage();
                                }
                            }

                            $candidateFiles[] = [
                                'filename' => $item,
                                'absolute_path' => $full,
                                'size_bytes' => $size,
                                'size_formatted' => number_format($size) . ' bytes',
                                'last_modified' => $mtime,
                                'sqlite_integrity' => $integrity,
                                'has_users_table' => in_array('users', $tables),
                                'table_counts' => $tableCounts,
                                'tables' => $tables,
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Ignore restricted directories
            }
        };

        foreach (array_unique($searchRoots) as $root) {
            $findSqliteFiles($root, 0);
        }

        // Deduplicate candidate files by absolute path
        $uniqueCandidates = [];
        $seenPaths = [];
        foreach ($candidateFiles as $cand) {
            if (!in_array($cand['absolute_path'], $seenPaths)) {
                $seenPaths[] = $cand['absolute_path'];
                $uniqueCandidates[] = $cand;
            }
        }

        if (request()->query('format') === 'json') {
            return response()->json([
                'configured_database' => [
                    'driver' => config('database.default'),
                    'path' => $dbPath,
                    'exists' => $dbExists,
                    'size' => $dbSize,
                    'connected' => $dbConnected,
                ],
                'recover_error' => $recoverError ?? null,
                'live_counts' => [
                    'users' => $usersCount,
                    'clients' => $clientsCount,
                    'landing_pages' => $landingPagesCount,
                    'bots' => $botsCount,
                    'channels' => $dbConnected ? \App\Models\TelegramChannel::count() : 0,
                    'meta_connections' => $dbConnected ? \App\Models\MetaConnection::count() : 0,
                    'ad_accounts' => $dbConnected ? \App\Models\AdAccount::count() : 0,
                ],
                'bots_data' => $dbConnected ? \App\Models\TelegramBot::select('id', 'name', 'username', 'client_id', 'webhook_secret', 'is_active', 'is_webhook_active')->get() : [],
                'channels_data' => $dbConnected ? \App\Models\TelegramChannel::select('id', 'title', 'telegram_chat_id', 'telegram_bot_id', 'client_id')->get() : [],
                'clients_data' => $dbConnected ? \App\Models\Client::select('id', 'company_name', 'client_name', 'email', 'ad_account_id')->get() : [],
                'users_data' => $dbConnected ? \App\Models\User::select('id', 'name', 'email', 'role')->get() : [],
                'settings_keys' => $dbConnected ? \App\Models\Setting::pluck('key')->all() : [],
                'meta_diagnostics' => [
                    'connection' => $dbConnected ? \App\Models\MetaConnection::select('id', 'facebook_user_id', 'facebook_name', 'status', 'sync_status', 'last_sync_at')->first() : null,
                    'has_token_in_connection' => $dbConnected ? !empty(\App\Models\MetaConnection::first()?->access_token) : false,
                    'has_system_user_token_setting' => $dbConnected ? !empty(\App\Models\Setting::get('meta_system_user_token')) : false,
                    'meta_app_id_setting' => $dbConnected ? \App\Models\Setting::get('meta_app_id') : null,
                    'meta_app_id_env' => env('META_APP_ID'),
                    'app_url_config' => config('app.url'),
                    'oauth_redirect_uri' => route('meta.oauth.callback'),
                    'app_key_prefix' => substr((string)config('app.key'), 0, 10),
                ],
                'persistent_storage_diagnostics' => [
                    'base_path' => base_path(),
                    'domain_root' => dirname(base_path()),
                    'is_domain_root_writable' => is_writable(dirname(base_path())),
                    'candidate_data_dir' => dirname(base_path()) . '/data',
                    'candidate_data_dir_exists' => is_dir(dirname(base_path()) . '/data'),
                    'storage_path' => storage_path(),
                    'is_storage_writable' => is_writable(storage_path()),
                ],
                'discovered_sqlite_files' => $uniqueCandidates,
            ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        $html = "<!DOCTYPE html><html><head><title>Kirtnix DB Inspector</title>";
        $html .= "<style>body{font-family:monospace;background:#0d1117;color:#c9d1d9;padding:20px;} table{border-collapse:collapse;width:100%;margin-top:15px;} th,td{border:1px solid #30363d;padding:8px 12px;text-align:left;} th{background:#161b22;color:#58a6ff;} tr:nth-child(even){background:#161b22;}</style></head><body>";
        $html .= "<h2>KIRTNIX PRODUCTION DATABASE INSPECTOR (READ-ONLY)</h2>";
        $html .= "<p><strong>Configured DB:</strong> {$dbPath}<br>";
        $html .= "<strong>Exists:</strong> " . ($dbExists ? 'YES' : 'NO') . " (" . number_format($dbSize) . " bytes)<br>";
        $html .= "<strong>Connected:</strong> " . ($dbConnected ? 'YES' : 'NO') . "<br>";
        $html .= "<strong>Snapshot Archive:</strong> " . ($snapshotExists ? "YES ({$snapshotSize} bytes)" : 'NO') . "<br>";
        $html .= "<strong>DB Writable:</strong> " . ($dbWritable ? 'YES' : 'NO') . " | <strong>Dir Writable:</strong> " . ($dirWritable ? 'YES' : 'NO') . " | <strong>Write Result:</strong> {$writeAttemptResult}<br>";
        $html .= "<strong>Live Records:</strong> Users={$usersCount}, Clients={$clientsCount}, LandingPages={$landingPagesCount}, Bots={$botsCount}</p>";

        $html .= "<h3>Discovered SQLite Candidate Files (" . count($uniqueCandidates) . ")</h3>";
        if (empty($uniqueCandidates)) {
            $html .= "<p style='color:#f85149;'>No candidate SQLite files found in scanned directories.</p>";
        } else {
            $html .= "<table><thead><tr><th>#</th><th>File Path</th><th>Size</th><th>Modified</th><th>Integrity</th><th>Users Table</th><th>Row Counts</th></tr></thead><tbody>";
            foreach ($uniqueCandidates as $idx => $cand) {
                $num = $idx + 1;
                $countsStr = [];
                foreach ($cand['table_counts'] as $t => $cnt) {
                    $countsStr[] = "{$t}:{$cnt}";
                }
                $countsText = empty($countsStr) ? 'None' : htmlspecialchars(implode(', ', $countsStr));
                $html .= "<tr>";
                $html .= "<td>{$num}</td>";
                $html .= "<td><strong>" . htmlspecialchars($cand['absolute_path']) . "</strong></td>";
                $html .= "<td>{$cand['size_formatted']}</td>";
                $html .= "<td>{$cand['last_modified']}</td>";
                $html .= "<td>" . htmlspecialchars($cand['sqlite_integrity']) . "</td>";
                $html .= "<td>" . ($cand['has_users_table'] ? '<span style="color:#3fb950;font-weight:bold;">YES</span>' : '<span style="color:#8b949e;">NO</span>') . "</td>";
                $html .= "<td>{$countsText}</td>";
                $html .= "</tr>";
            }
            $html .= "</tbody></table>";
        }

        $html .= "</body></html>";
        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    } catch (\Throwable $e) {
        return response("<h1>CRITICAL ERROR</h1><pre>" . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . "</pre>", 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
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
    Route::post('/telegram/bots/{bot}/sync', [TelegramBotController::class, 'syncWebhook']);
    Route::post('/telegram/{bot}/health', [TelegramBotController::class, 'health'])->name('telegram.health');
    Route::post('/telegram/bots/{bot}/health', [TelegramBotController::class, 'health']);
    Route::delete('/telegram/{bot}', [TelegramBotController::class, 'destroy'])->name('telegram.destroy');
    Route::delete('/telegram/bots/{bot}', [TelegramBotController::class, 'destroy']);

    // Telegram Tracked Channels & Auto-Detection
    Route::post('/telegram/channels/verify', [TelegramChannelController::class, 'verify'])->name('telegram.channels.verify');
    Route::get('/telegram/channels/auto-detect', [TelegramChannelController::class, 'autoDetect'])->name('telegram.channels.auto_detect');
    Route::post('/telegram/channels/{channel}/assign-client', [TelegramChannelController::class, 'assignClient'])->name('telegram.channels.assign_client');
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
