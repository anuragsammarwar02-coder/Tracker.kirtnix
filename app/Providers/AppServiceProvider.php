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
    }

    public function boot(): void
    {
        // Automatically clear stale route cache if present
        if (file_exists(base_path('bootstrap/cache/routes-v7.php'))) {
            @unlink(base_path('bootstrap/cache/routes-v7.php'));
        }

        // Safe check for sqlite database: if file is 0 bytes or absent, restore verified clean baseline
        try {
            if (config('database.default') === 'sqlite') {
                $dbPath = config('database.connections.sqlite.database');
                if ($dbPath && $dbPath !== ':memory:') {
                    $dir = dirname($dbPath);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }

                    // Only if database file does not exist OR is 0 bytes, restore verified clean baseline snapshot
                    $isZeroBytes = !file_exists($dbPath) || (file_exists($dbPath) && filesize($dbPath) === 0);
                    $snapshotGzPath = database_path('snapshots/clean_baseline.sqlite.gz');

                    if ($isZeroBytes && file_exists($snapshotGzPath)) {
                        $gzData = file_get_contents($snapshotGzPath);
                        $rawSqlite = @gzdecode($gzData);
                        if ($rawSqlite !== false && strlen($rawSqlite) === 458752) {
                            @file_put_contents($dbPath, $rawSqlite);
                            @chmod($dbPath, 0664);
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
                    }
                }
            }
        } catch (\Throwable $e) {
            @error_log('AppServiceProvider database baseline check: ' . $e->getMessage());
        }
    }
}
