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
        // Safe check for sqlite directory if database exists
        try {
            if (config('database.default') === 'sqlite') {
                $dbPath = config('database.connections.sqlite.database');
                if ($dbPath && $dbPath !== ':memory:') {
                    $dir = dirname($dbPath);
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0755, true);
                    }
                }
            }
        } catch (\Throwable $e) {
            @error_log('AppServiceProvider boot check warning: ' . $e->getMessage());
        }
    }
}
