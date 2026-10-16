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

        // Configure SQLite connections for concurrency when opened (low busy timeout, WAL mode)
        Event::listen(ConnectionEstablished::class, function ($event) {
            if ($event->connectionName === 'sqlite' || $event->connection->getDriverName() === 'sqlite') {
                try {
                    $pdo = $event->connection->getPdo();
                    if ($pdo) {
                        $pdo->setAttribute(\PDO::ATTR_TIMEOUT, 5);
                        $pdo->exec("PRAGMA journal_mode = WAL;");
                        $pdo->exec("PRAGMA synchronous = NORMAL;");
                        $pdo->exec("PRAGMA busy_timeout = 5000;");
                        $pdo->exec("PRAGMA cache_size = -64000;");
                        $pdo->exec("PRAGMA temp_store = MEMORY;");
                    }
                } catch (\Throwable $e) {
                    // Ignore if memory or read-only
                }
            }
        });

        // Safe one-time check for sqlite database: if file is 0 bytes or absent, restore verified clean baseline
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

                    // Only if persistent database file does not exist OR is 0 bytes, restore baseline
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
                }
            }
        } catch (\Throwable $e) {
            @error_log('AppServiceProvider database baseline check: ' . $e->getMessage());
        }
    }
}
