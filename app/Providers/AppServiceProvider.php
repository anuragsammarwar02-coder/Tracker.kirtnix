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
                }
            }
        } catch (\Throwable $e) {
            @error_log('AppServiceProvider database baseline check: ' . $e->getMessage());
        }
    }
}
