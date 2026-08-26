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
        // 1. Initial Super Administrators (supports both admin@kirtnix.in and admin@kirtnix.agency)
        User::firstOrCreate(
            ['email' => 'admin@kirtnix.in'],
            [
                'name' => 'KirtniX Admin',
                'password' => Hash::make('Kirti#13'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        User::firstOrCreate(
            ['email' => 'admin@kirtnix.agency'],
            [
                'name' => 'KirtniX Agency Admin',
                'password' => Hash::make('Kirtnix@2026!'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        // 2. Global Brand & System Settings (Never overwrite user-modified settings)
        $defaultSettings = [
            'app_name' => ['Kirtnix TG Tracker', 'branding'],
            'brand_name' => ['Kirtnix', 'branding'],
            'brand_tagline' => ['Performance Marketing & Telegram Conversion Tracking SaaS', 'branding'],
            'brand_primary_color' => ['#EAB308', 'branding'],
            'brand_logo_url' => ['/assets/branding/kirtnix-logo-dark-horizontal.png', 'branding'],
            'brand_favicon_url' => ['/assets/branding/favicon.png', 'branding'],
            'hostinger_domain' => ['tracker.kirtnix.in', 'general'],
            'meta_app_id' => ['4520673831531016', 'meta'],
            'meta_app_secret' => ['4400729382f0cf94b61599e165019281', 'meta'],
            'meta_api_version' => ['v19.0', 'meta'],
            'support_telegram' => ['@kirtnixsupport', 'support'],
            'working_hours' => ['10:00 AM - 7:00 PM IST', 'support'],
        ];

        foreach ($defaultSettings as $key => [$value, $group]) {
            if (!Setting::where('key', $key)->exists()) {
                Setting::create([
                    'key' => $key,
                    'value' => $value,
                    'group' => $group,
                ]);
            }
        }

        // 3. Baseline Mock Data (ONLY in automated testing environment)
        if (app()->environment('testing')) {
            $clientA = \App\Models\Client::firstOrCreate(
                ['kx_code' => 'KX-001'],
                [
                    'company_name' => 'Forex Focus Academy',
                    'client_name' => 'Anurag Sharma',
                    'industry' => 'Forex Trading',
                    'email' => 'client@forexfocus.com',
                    'status' => 'active',
                    'meta_ads_connected' => true,
                    'monthly_budget' => 5000.00,
                ]
            );

        $clientB = \App\Models\Client::firstOrCreate(
            ['kx_code' => 'KX-002'],
            [
                'company_name' => 'Gujarati Trader',
                'client_name' => 'Bhavik Patel',
                'industry' => 'Options Trading',
                'email' => 'bhavik@gujaratitrader.in',
                'status' => 'active',
                'meta_ads_connected' => true,
                'monthly_budget' => 3500.00,
            ]
        );

        $lpA = \App\Models\LandingPage::firstOrCreate(
            ['slug' => 'forex-focus-tg'],
            [
                'client_id' => $clientA->id,
                'title' => 'FOREX FOCUS',
                'brand_name' => 'Forex Focus',
                'brand_tagline' => 'Understand the Forex Markets',
                'template_type' => 'forex_focus',
                'hero_heading' => 'Understand the Forex Markets with Real-Time Accuracy',
                'primary_cta_text' => 'Join VIP Telegram',
                'telegram_destination' => 'https://t.me/forexfocusvip',
                'meta_pixel_id' => '1130260856232291',
                'page_source' => 'native',
                'tracking_token' => 'kx_forex_token_001',
                'deployment_status' => 'published',
                'is_active' => true,
            ]
        );

        \App\Models\Cta::firstOrCreate(
            ['landing_page_id' => $lpA->id, 'button_type' => 'primary'],
            [
                'client_id' => $clientA->id,
                'name' => 'Primary Hero CTA',
                'button_text' => 'Join VIP Telegram',
                'tracking_token' => 'kx_ff_hero',
                'telegram_destination' => 'https://t.me/+sncMUjBZ9a41ZDll',
                'direct_protocol' => 'auto',
                'is_active' => true,
            ]
        );

        $lpB = \App\Models\LandingPage::firstOrCreate(
            ['slug' => 'gujaratitrdexx'],
            [
                'client_id' => $clientB->id,
                'title' => 'Gujarati Trader Scaling Hub',
                'brand_name' => 'Gujarati Trader',
                'template_type' => 'gujarati_trader',
                'hero_heading' => 'Scalping & Option Buying Blueprint',
                'primary_cta_text' => 'Join Free Telegram',
                'telegram_destination' => 'https://t.me/gujaratitraderfree',
                'page_source' => 'native',
                'tracking_token' => 'kx_gujarati_token_002',
                'deployment_status' => 'published',
                'is_active' => true,
            ]
        );

        \App\Models\Cta::firstOrCreate(
            ['landing_page_id' => $lpB->id, 'button_type' => 'primary'],
            [
                'client_id' => $clientB->id,
                'name' => 'Primary Hero CTA',
                'button_text' => 'Join Free Telegram',
                'tracking_token' => 'kx_hero_cta_002',
                'telegram_destination' => 'https://t.me/gujaratitraderfree',
                'direct_protocol' => 'auto',
                'is_active' => true,
            ]
        );

        $bot = \App\Models\TelegramBot::firstOrCreate(
            ['username' => 'kirtnixtgtracker_bot'],
            [
                'client_id' => $clientA->id,
                'name' => 'KirtniX TG Tracker Bot',
                'bot_token' => '8956518773:AAH9_test_token',
                'channel_id' => '-1001234567890',
                'channel_title' => 'Forex Focus VIP',
                'webhook_secret' => 'whsec_test_secret_123',
                'is_webhook_active' => true,
            ]
        );

        \App\Models\TelegramChannel::firstOrCreate(
            ['telegram_chat_id' => '-1001234567890'],
            [
                'telegram_bot_id' => $bot->id,
                'client_id' => $clientA->id,
                'landing_page_id' => $lpA->id,
                'title' => 'Forex Focus VIP',
                'username' => 'forexfocusvip',
                'type' => 'channel',
                'member_count' => 1480,
                'is_bot_admin' => true,
            ]
        );
        }
    }
}
