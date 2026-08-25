<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Super Administrator
        User::updateOrCreate(
            ['email' => 'admin@kirtnix.in'],
            [
                'name' => 'KirtniX Admin',
                'password' => Hash::make('Kirti#13'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        // 2. Global Brand & System Settings
        Setting::set('app_name', 'Kirtnix TG Tracker', 'branding');
        Setting::set('brand_name', 'Kirtnix', 'branding');
        Setting::set('brand_tagline', 'Performance Marketing & Telegram Conversion Tracking SaaS', 'branding');
        Setting::set('brand_primary_color', '#EAB308', 'branding');
        Setting::set('brand_logo_url', '/assets/branding/kirtnix-logo-dark-horizontal.png', 'branding');
        Setting::set('brand_favicon_url', '/assets/branding/favicon.png', 'branding');
        Setting::set('hostinger_domain', 'tracker.kirtnix.in', 'general');
        Setting::set('meta_app_id', '4520673831531016', 'meta');
        Setting::set('meta_app_secret', '4400729382f0cf94b61599e165019281', 'meta');
        Setting::set('meta_api_version', 'v19.0', 'meta');
        Setting::set('support_telegram', '@kirtnixsupport', 'support');
        Setting::set('working_hours', '10:00 AM - 7:00 PM IST', 'support');
    }
}
