<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

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

                    // Ensure telegram_bots.client_id is nullable for global bots
                    if (file_exists($dbPath) && filesize($dbPath) > 0) {
                        try {
                            $cols = \Illuminate\Support\Facades\DB::select("PRAGMA table_info(telegram_bots)");
                            $clientCol = collect($cols)->firstWhere('name', 'client_id');
                            if ($clientCol && (int)$clientCol->notnull === 1) {
                                \Illuminate\Support\Facades\DB::statement("PRAGMA foreign_keys=OFF;");
                                \Illuminate\Support\Facades\DB::beginTransaction();
                                \Illuminate\Support\Facades\DB::statement('
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
                                \Illuminate\Support\Facades\DB::statement('INSERT INTO "telegram_bots_temp" SELECT * FROM "telegram_bots";');
                                \Illuminate\Support\Facades\DB::statement('DROP TABLE "telegram_bots";');
                                \Illuminate\Support\Facades\DB::statement('ALTER TABLE "telegram_bots_temp" RENAME TO "telegram_bots";');
                                \Illuminate\Support\Facades\DB::commit();
                                \Illuminate\Support\Facades\DB::statement("PRAGMA foreign_keys=ON;");
                            }
                        } catch (\Throwable $te) {
                            @error_log('AppServiceProvider telegram_bots schema check: ' . $te->getMessage());
                        }

                        // Ensure clients.ad_account_id column exists
                        try {
                            $clientCols = \Illuminate\Support\Facades\DB::select("PRAGMA table_info(clients)");
                            $hasAdAccountCol = collect($clientCols)->firstWhere('name', 'ad_account_id') !== null;
                            if (!$hasAdAccountCol && count($clientCols) > 0) {
                                \Illuminate\Support\Facades\DB::statement("ALTER TABLE clients ADD COLUMN ad_account_id INTEGER NULL;");
                            }
                        } catch (\Throwable $ce) {
                            @error_log('AppServiceProvider clients column check: ' . $ce->getMessage());
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            @error_log('AppServiceProvider database baseline check: ' . $e->getMessage());
        }
    }
}
