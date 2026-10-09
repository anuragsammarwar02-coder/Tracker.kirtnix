<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Events\ConnectionEstablished;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Force debug to true on hostinger to display precise diagnostic if error occurs
        Config::set('app.debug', true);

        // Enforce persistent SQLite database path on Hostinger production outside public_html
        if (DIRECTORY_SEPARATOR === '/') {
            $domainRoot = dirname(base_path());
            if (is_dir($domainRoot) && (str_contains(base_path(), 'public_html') || str_contains(base_path(), 'domains'))) {
                $persistentDir = $domainRoot . '/data';
                if (!is_dir($persistentDir)) {
                    @mkdir($persistentDir, 0775, true);
                }
                if (is_dir($persistentDir)) {
                    $persistentDb = $persistentDir . '/database.sqlite';
                    Config::set('database.connections.sqlite.database', $persistentDb);
                }
            }
        }
    }

    public function boot(): void
    {
        // Enforce HTTPS scheme when configured in production
        if (str_starts_with((string) config('app.url'), 'https://') || app()->isProduction()) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        // Configure all SQLite connections for high performance & concurrency (WAL mode + 60s busy timeout)
        try {
            if (config('database.default') === 'sqlite') {
                $pdo = DB::connection()->getPdo();
                if ($pdo) {
                    $pdo->setAttribute(\PDO::ATTR_TIMEOUT, 60);
                    $pdo->exec("PRAGMA journal_mode = WAL;");
                    $pdo->exec("PRAGMA synchronous = NORMAL;");
                    $pdo->exec("PRAGMA busy_timeout = 60000;");
                    $pdo->exec("PRAGMA cache_size = -64000;");
                    $pdo->exec("PRAGMA temp_store = MEMORY;");
                }
            }
        } catch (\Throwable $e) {
            // Ignore if connection not ready
        }

        Event::listen(ConnectionEstablished::class, function ($event) {
            if ($event->connectionName === 'sqlite' || $event->connection->getDriverName() === 'sqlite') {
                try {
                    $pdo = $event->connection->getPdo();
                    if ($pdo) {
                        $pdo->setAttribute(\PDO::ATTR_TIMEOUT, 60);
                        $pdo->exec("PRAGMA journal_mode = WAL;");
                        $pdo->exec("PRAGMA synchronous = NORMAL;");
                        $pdo->exec("PRAGMA busy_timeout = 60000;");
                        $pdo->exec("PRAGMA cache_size = -64000;");
                        $pdo->exec("PRAGMA temp_store = MEMORY;");
                    }
                } catch (\Throwable $e) {
                    // Ignore if memory or read-only
                }
            }
        });

        // Automatically clear stale config and route cache if present
        if (file_exists(base_path('bootstrap/cache/config.php'))) {
            @unlink(base_path('bootstrap/cache/config.php'));
        }
        if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
            @unlink(base_path('bootstrap/cache/routes-v7.php'));
        }
        $viewsDir = storage_path('framework/views');
        if (is_dir($viewsDir) && request()->has('clear_cache')) {
            foreach (@glob($viewsDir . '/*.php') ?: [] as $vf) {
                @unlink($vf);
            }
        }

        // Safe check for sqlite database: if file is 0 bytes or absent, restore verified clean baseline
        try {
            if (config('database.default') === 'sqlite') {
                $dbPath = config('database.connections.sqlite.database');
                if ($dbPath && $dbPath !== ':memory:') {
                    $dir = dirname($dbPath);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                    }
                    if (is_dir($dir)) {
                        @chmod($dir, 0775);
                    }

                    // Only if persistent database file does not exist OR is 0 bytes, migrate from legacy path or restore baseline
                    $isZeroBytes = !file_exists($dbPath) || (file_exists($dbPath) && filesize($dbPath) === 0);
                    $snapshotGzPath = database_path('snapshots/clean_baseline.sqlite.gz');

                    if ($isZeroBytes) {
                        $legacyCandidates = [
                            base_path('u123456789_tracker'),
                            base_path('database/database.sqlite'),
                        ];
                        $migrated = false;
                        foreach ($legacyCandidates as $cand) {
                            if ($cand !== $dbPath && file_exists($cand) && filesize($cand) > 0) {
                                @copy($cand, $dbPath);
                                @chmod($dbPath, 0664);
                                $migrated = true;
                                break;
                            }
                        }

                        if (!$migrated && file_exists($snapshotGzPath)) {
                            $gzData = file_get_contents($snapshotGzPath);
                            $rawSqlite = @gzdecode($gzData);
                            if ($rawSqlite !== false && strlen($rawSqlite) === 458752) {
                                @file_put_contents($dbPath, $rawSqlite);
                                @chmod($dbPath, 0664);
                            }
                        }
                    }

                    // Apply WAL mode and ensure table schema & performance indexes exist
                    if (file_exists($dbPath) && filesize($dbPath) > 0) {
                        try {
                            DB::statement("PRAGMA journal_mode = WAL;");
                            DB::statement("PRAGMA synchronous = NORMAL;");
                            DB::statement("PRAGMA busy_timeout = 60000;");
                            DB::statement("PRAGMA cache_size = -64000;");
                            DB::statement("PRAGMA temp_store = MEMORY;");
                        } catch (\Throwable $pe) {
                            // ignore
                        }

                        // Ensure telegram_bots.client_id is nullable for global bots
                        try {
                            $cols = DB::select("PRAGMA table_info(telegram_bots)");
                            $clientCol = collect($cols)->firstWhere('name', 'client_id');
                            if ($clientCol && (int)$clientCol->notnull === 1) {
                                DB::statement("PRAGMA foreign_keys=OFF;");
                                DB::beginTransaction();
                                DB::statement('
                                    CREATE TABLE IF NOT EXISTS "telegram_bots_temp" (
                                        "id" integer primary key autoincrement not null, 
                                        "client_id" integer, 
                                        "name" varchar not null, 
                                        "username" varchar not null, 
                                        "bot_token" text not null, 
                                        "channel_id" varchar, 
                                        "channel_title" varchar, 
                                        "channel_username" varchar, 
                                        "webhook_secret" varchar not null, 
                                        "webhook_url" varchar, 
                                        "is_webhook_active" tinyint(1) not null default "0", 
                                        "last_webhook_ping_at" datetime, 
                                        "is_active" tinyint(1) not null default "1", 
                                        "created_at" datetime, 
                                        "updated_at" datetime, 
                                        foreign key("client_id") references "clients"("id") on delete set null
                                    );
                                ');
                                DB::statement('INSERT INTO "telegram_bots_temp" SELECT * FROM "telegram_bots";');
                                DB::statement('DROP TABLE "telegram_bots";');
                                DB::statement('ALTER TABLE "telegram_bots_temp" RENAME TO "telegram_bots";');
                                DB::commit();
                                DB::statement("PRAGMA foreign_keys=ON;");
                            }
                        } catch (\Throwable $te) {
                            @error_log('AppServiceProvider telegram_bots schema check: ' . $te->getMessage());
                        }

                        // Ensure clients.ad_account_id & category columns exist
                        try {
                            $clientCols = DB::select("PRAGMA table_info(clients)");
                            $hasAdAccountCol = collect($clientCols)->firstWhere('name', 'ad_account_id') !== null;
                            if (!$hasAdAccountCol && count($clientCols) > 0) {
                                DB::statement("ALTER TABLE clients ADD COLUMN ad_account_id INTEGER NULL;");
                            }

                            $hasCategoryCol = collect($clientCols)->firstWhere('name', 'category') !== null;
                            if (!$hasCategoryCol && count($clientCols) > 0) {
                                DB::statement("ALTER TABLE clients ADD COLUMN category VARCHAR(100) NULL DEFAULT 'Stock Market & Options Trading';");
                                DB::statement("UPDATE clients SET category = industry WHERE (category IS NULL OR category = '') AND industry IS NOT NULL AND industry != '';");
                            }
                        } catch (\Throwable $ce) {
                            @error_log('AppServiceProvider clients column check: ' . $ce->getMessage());
                        }

                        // Auto-ensure performance indexes on high-traffic tracking and relational tables
                        try {
                            $indexes = [
                                'CREATE INDEX IF NOT EXISTS idx_lp_views_client_id ON landing_page_views (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_lp_views_lp_id ON landing_page_views (landing_page_id);',
                                'CREATE INDEX IF NOT EXISTS idx_lp_views_client_lp ON landing_page_views (client_id, landing_page_id);',
                                'CREATE INDEX IF NOT EXISTS idx_lp_views_viewed_at ON landing_page_views (viewed_at);',
                                'CREATE INDEX IF NOT EXISTS idx_cta_clicks_client_id ON cta_clicks (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_cta_clicks_lp_id ON cta_clicks (landing_page_id);',
                                'CREATE INDEX IF NOT EXISTS idx_cta_clicks_cta_id ON cta_clicks (cta_id);',
                                'CREATE INDEX IF NOT EXISTS idx_cta_clicks_client_lp ON cta_clicks (client_id, landing_page_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_events_client_id ON telegram_events (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_events_bot_id ON telegram_events (telegram_bot_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_events_campaign_id ON telegram_events (campaign_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_events_event_type ON telegram_events (event_type);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_events_client_event ON telegram_events (client_id, event_type);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_events_event_time ON telegram_events (event_time);',
                                'CREATE INDEX IF NOT EXISTS idx_trk_sess_client_id ON tracking_sessions (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_trk_sess_lp_id ON tracking_sessions (landing_page_id);',
                                'CREATE INDEX IF NOT EXISTS idx_trk_sess_campaign_id ON tracking_sessions (campaign_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_inv_client_id ON telegram_invites (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_inv_lp_id ON telegram_invites (landing_page_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_inv_status ON telegram_invites (status);',
                                'CREATE INDEX IF NOT EXISTS idx_conv_client_id ON conversions (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_conv_lp_id ON conversions (landing_page_id);',
                                'CREATE INDEX IF NOT EXISTS idx_conv_campaign_id ON conversions (campaign_id);',
                                'CREATE INDEX IF NOT EXISTS idx_conv_status ON conversions (status);',
                                'CREATE INDEX IF NOT EXISTS idx_conv_event_type ON conversions (event_type);',
                                'CREATE INDEX IF NOT EXISTS idx_conv_client_status ON conversions (client_id, status);',
                                'CREATE INDEX IF NOT EXISTS idx_ctas_client_id ON ctas (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_ctas_lp_id ON ctas (landing_page_id);',
                                'CREATE INDEX IF NOT EXISTS idx_lp_client_id ON landing_pages (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_camp_client_id ON campaigns (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_camp_ad_account_id ON campaigns (ad_account_id);',
                                'CREATE INDEX IF NOT EXISTS idx_ci_campaign_id ON campaign_insights (campaign_id);',
                                'CREATE INDEX IF NOT EXISTS idx_ad_accounts_client_id ON ad_accounts (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_bots_client_id ON telegram_bots (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_tg_channels_client_id ON telegram_channels (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_reports_client_id ON reports (client_id);',
                                'CREATE INDEX IF NOT EXISTS idx_notif_client_id ON notifications (client_id);',
                            ];

                            foreach ($indexes as $sql) {
                                DB::statement($sql);
                            }
                        } catch (\Throwable $ie) {
                            @error_log('AppServiceProvider index check: ' . $ie->getMessage());
                        }

                        // Auto-sync new Meta App credentials in settings table if not yet updated
                        try {
                            if (Schema::hasTable('settings')) {
                                $currentAppId = DB::table('settings')->where('key', 'meta_app_id')->value('value');
                                if (empty($currentAppId) || $currentAppId === '1427417489333099') {
                                    DB::table('settings')->updateOrInsert(
                                        ['key' => 'meta_app_id'],
                                        ['value' => '1812606023369831', 'group' => 'meta', 'updated_at' => now()]
                                    );
                                    DB::table('settings')->updateOrInsert(
                                        ['key' => 'meta_app_secret'],
                                        ['value' => '12f7da9cb5e45f880f1e77bbb4ed2c66', 'group' => 'meta', 'updated_at' => now()]
                                    );
                                }
                            }
                        } catch (\Throwable $se) {
                            @error_log('AppServiceProvider meta credentials sync: ' . $se->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            @error_log('AppServiceProvider database baseline check: ' . $e->getMessage());
        }
    }
}
