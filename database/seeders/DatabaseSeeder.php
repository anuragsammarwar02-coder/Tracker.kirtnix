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
        User::updateOrCreate(
            ['email' => 'admin@kirtnix.in'],
            [
                'name' => 'KirtniX Admin',
                'password' => Hash::make('Kirti#13'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['email' => 'admin@kirtnix.agency'],
            [
                'name' => 'KirtniX Agency Admin',
                'password' => Hash::make('Kirtnix@2026!'),
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

        // 3. Baseline Client & Landing Page for Tracking Engine (if missing)
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
