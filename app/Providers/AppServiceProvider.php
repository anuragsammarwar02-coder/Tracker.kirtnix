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
        // Safe database migration check
        try {
            if (extension_loaded('pdo_sqlite') && !Schema::hasTable('users')) {
                @Artisan::call('migrate', ['--force' => true]);
                @Artisan::call('db:seed', ['--force' => true]);
            }
        } catch (\Throwable $e) {
            @error_log('AppServiceProvider migration error: ' . $e->getMessage());
        }
    }
}
