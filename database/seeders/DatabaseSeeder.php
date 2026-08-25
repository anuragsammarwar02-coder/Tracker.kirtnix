<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Campaign;
use App\Models\CampaignInsight;
use App\Models\LandingPage;
use App\Models\Cta;
use App\Models\TrackingSession;
use App\Models\LandingPageView;
use App\Models\CtaClick;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\TelegramEvent;
use App\Models\Setting;
use App\Models\Notification;
use App\Models\Report;
use App\Models\LoginRequest;
use App\Models\TeamPermission;
use App\Models\MetaConnection;
use App\Models\MetaBusiness;
use App\Models\AdAccount;
use App\Services\MetaSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Initial Administrator & Team
        $admin = User::updateOrCreate(
            ['email' => 'admin@kirtnix.agency'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('Kirtnix@2026!'),
                'role' => 'owner',
                'status' => 'active',
            ]
        );
        $agencyAdmin = $admin;

        $manager = User::updateOrCreate(
            ['email' => 'rahul.media@kirtnix.agency'],
            [
                'name' => 'Rahul Sharma (Senior Media Buyer)',
                'password' => Hash::make('admin123'),
                'role' => 'manager',
                'status' => 'active',
            ]
        );

        $analyst = User::updateOrCreate(
            ['email' => 'priya.cro@kirtnix.agency'],
            [
                'name' => 'Priya Patel (CRO & Analytics)',
                'password' => Hash::make('admin123'),
                'role' => 'analyst',
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
        Setting::set('meta_api_version', 'v19.0', 'meta');
        Setting::set('support_telegram', '@kirtnixsupport', 'support');
        Setting::set('working_hours', '10:00 AM - 7:00 PM IST', 'support');

        // 3. Realistic Clients
        $client1 = Client::updateOrCreate(
            ['kx_code' => 'KX-001'],
            [
                'company_name' => 'STOXK Academy',
                'client_name' => 'Nandu Meena',
                'industry' => 'STOXK / Stock Market',
                'email' => 'nandu@stoxk.in',
                'phone' => '+91 98290 12345',
                'logo_path' => 'assets/branding/kirtnix-logo-dark-icon.png',
                'status' => 'active',
                'meta_ads_connected' => true,
                'monthly_budget' => 4500.00,
                'notes' => 'Top performing client with high-converting Nifty & BankNifty daily educational breakdown.',
                'timezone' => 'Asia/Kolkata',
            ]
        );

        $client2 = Client::updateOrCreate(
            ['kx_code' => 'KX-002'],
            [
                'company_name' => 'Forex Focus Global',
                'client_name' => 'Alexander Vance',
                'industry' => 'Forex Trading Education',
                'email' => 'alex@forexfocus.io',
                'phone' => '+1 (555) 349-2810',
                'logo_path' => 'assets/branding/kirtnix-logo-dark-icon.png',
                'status' => 'active',
                'meta_ads_connected' => true,
                'monthly_budget' => 3200.00,
                'notes' => 'Premium international Forex market education channel with live webinar funnels.',
                'timezone' => 'UTC',
            ]
        );

        $client3 = Client::updateOrCreate(
            ['kx_code' => 'KX-003'],
            [
                'company_name' => 'Gujarati Trader Alpha',
                'client_name' => 'Bhavik Patel',
                'industry' => 'Regional Language Trading',
                'email' => 'bhavik@gujaratitrader.com',
                'phone' => '+91 97250 88990',
                'logo_path' => 'assets/branding/kirtnix-logo-dark-icon.png',
                'status' => 'active',
                'meta_ads_connected' => true,
                'monthly_budget' => 2800.00,
                'notes' => 'Gujarati language trading channel with high engagement in Ahmedabad and Surat.',
                'timezone' => 'Asia/Kolkata',
            ]
        );

        $client4 = Client::updateOrCreate(
            ['kx_code' => 'KX-004'],
            [
                'company_name' => 'Trade with Vikash',
                'client_name' => 'Vikash Sharma',
                'industry' => 'BankNifty Momentum',
                'email' => 'vikash@tradewithvikash.in',
                'phone' => '+91 98110 54321',
                'logo_path' => 'assets/branding/kirtnix-logo-dark-icon.png',
                'status' => 'active',
                'meta_ads_connected' => true,
                'monthly_budget' => 3800.00,
                'notes' => 'Intraday option buying strategy learning group.',
                'timezone' => 'Asia/Kolkata',
            ]
        );

        $client5 = Client::updateOrCreate(
            ['kx_code' => 'KX-005'],
            [
                'company_name' => 'Crypto Momentum Alpha',
                'client_name' => 'Rohan Mehta',
                'industry' => 'Crypto / Web3 Signals',
                'email' => 'rohan@cryptoalpha.io',
                'phone' => '+971 50 123 4567',
                'logo_path' => 'assets/branding/kirtnix-logo-dark-icon.png',
                'status' => 'active',
                'meta_ads_connected' => false,
                'monthly_budget' => 2000.00,
                'notes' => 'Dubai-based crypto derivatives educational community.',
                'timezone' => 'Asia/Dubai',
            ]
        );

        // 4. Meta Connections & Synced Ad Accounts (92+ Accounts as per Spec)
        $metaConnection = MetaConnection::updateOrCreate(
            ['user_id' => $agencyAdmin->id],
            [
                'facebook_user_id' => 'fb_kirtnix_agency_admin',
                'facebook_name' => 'KirtniX Performance Agency',
                'access_token' => 'EAABsbCS...kirtnix_agency_live_token',
                'status' => 'active',
                'sync_status' => 'completed',
                'last_sync_at' => now()->subMinutes(12),
            ]
        );

        $metaSync = new MetaSyncService();
        $metaSync->syncAll($metaConnection);

        // 5. Landing Pages
        $camp1 = Campaign::where('name', 'GJ001')->first() ?? Campaign::first();
        $camp4 = Campaign::where('name', 'GJ004')->first() ?? Campaign::first();

        $lp1 = LandingPage::updateOrCreate(
            ['slug' => 'stoxk-pro'],
            [
                'client_id' => $client1->id,
                'campaign_id' => $camp1?->id,
                'title' => 'STOXK | Nandu Meena Trading Room',
                'template_type' => 'forex_focus',
                'brand_name' => 'STOXK ACADEMY',
                'brand_tagline' => 'Price Action & Option Chain Learning Community',
                'brand_logo_url' => '/assets/branding/kirtnix-logo-dark-icon.png',
                'badge_text' => 'Official Educational Telegram · 50K+ Community',
                'hero_heading' => 'Learn Stock Market & Option Chain Analysis with Nandu Meena',
                'hero_subheading' => 'Daily pre-market analysis, live market levels, chart patterns, and educational breakdowns designed for disciplined traders.',
                'features_json' => [
                    ['icon' => '📈', 'title' => 'Pre-Market Levels', 'desc' => 'Daily Key Support & Resistance zones before market open.'],
                    ['icon' => '🎯', 'title' => 'Option Chain Breakdown', 'desc' => 'Open Interest (OI) buildup analysis and PCR indicators.'],
                    ['icon' => '📊', 'title' => 'Chart Pattern Setups', 'desc' => 'Price action educational case studies and risk management.'],
                    ['icon' => '💡', 'title' => 'Live Q&A Sessions', 'desc' => 'Weekly educational webinars answering community questions.']
                ],
                'about_heading' => 'About Nandu Meena & STOXK',
                'about_text' => 'STOXK is a leading financial market education community founded by Nandu Meena. We teach self-reliant price action analysis.',
                'disclaimer_text' => 'Trading stocks and options involves risk of loss. Past performance is not indicative of future results.',
                'footer_text' => '© 2026 STOXK Academy · All rights reserved.',
                'primary_cta_text' => 'Join Free Telegram Channel',
                'secondary_cta_text' => 'Open Telegram App',
                'telegram_destination' => 'https://t.me/+sncMUjBZ9a41ZDll',
                'telegram_channel_username' => 'stoxk_official',
                'meta_pixel_id' => '1130260856232291',
                'meta_access_token' => 'EAAGm0PX4ZCpsBO_sample_token',
                'gtm_id' => 'GTM-STOXK01',
                'is_active' => true,
            ]
        );

        $lp2 = LandingPage::updateOrCreate(
            ['slug' => 'forex-focus-tg'],
            [
                'client_id' => $client2->id,
                'campaign_id' => $camp4?->id,
                'title' => 'Forex Focus | Forex Market Education',
                'template_type' => 'forex_focus',
                'brand_name' => 'FOREX FOCUS',
                'brand_tagline' => 'Free Market Education · Community Learning',
                'brand_logo_url' => '/assets/branding/kirtnix-logo-dark-icon.png',
                'badge_text' => 'Educational Channel · Daily Market Insights',
                'hero_heading' => 'Understand the Forex Markets with Clarity & Confidence',
                'hero_subheading' => 'Learn how currency pairs move, read chart patterns, and follow structured market breakdowns — strictly for educational purposes.',
                'features_json' => [
                    ['icon' => '📊', 'title' => 'Market Structure & Trends', 'desc' => 'How major pairs move, support/resistance ideas, and reading charts.'],
                    ['icon' => '💱', 'title' => 'Currency Pairs & Price Action', 'desc' => 'How traders study momentum, structure and market behaviour.'],
                ],
                'about_heading' => 'About this community',
                'about_text' => 'Forex Focus is an educational Telegram community.',
                'disclaimer_text' => 'Forex trading involves high level of risk.',
                'footer_text' => '© 2026 Forex Focus · Educational content only',
                'primary_cta_text' => 'Join Free Telegram Channel',
                'secondary_cta_text' => 'Open Telegram Channel',
                'telegram_destination' => 'https://t.me/+sncMUjBZ9a41ZDll',
                'telegram_channel_username' => 'forexfocus_edu',
                'meta_pixel_id' => '1130260856232291',
                'meta_access_token' => 'EAAGm0PX4ZCpsBO_sample_token',
                'gtm_id' => 'GTM-KIRTNIX01',
                'is_active' => true,
            ]
        );

        $lp3 = LandingPage::updateOrCreate(
            ['slug' => 'gujaratitrdexx'],
            [
                'client_id' => $client1->id,
                'campaign_id' => $camp4?->id,
                'title' => 'gujaratitrdexx',
                'page_source' => 'vercel',
                'external_url' => 'https://gujaratitrdexx.vercel.app',
                'vercel_project_name' => 'gujaratitrde',
                'tracking_token' => '7b39a48e-289c-4b3d-9f4a-4e892c90df11',
                'template_type' => 'gujarati_trader',
                'brand_name' => 'GUJARATI TRADER ALPHA',
                'brand_tagline' => 'ગુજરાતી ભાષામાં શેરબજાર અને ઓપ્શન શીખો',
                'brand_logo_url' => '/assets/branding/kirtnix-logo-dark-icon.png',
                'badge_text' => '100% ફ્રી લર્નિંગ ટેલિગ્રામ ચેનલ',
                'hero_heading' => 'ઓપ્શન ટ્રેડિંગ અને પ્રાઇસ એક્શન હવે શીખો સરળ ગુજરાતીમાં',
                'hero_subheading' => 'ડેઇલી નિફ્ટી અને બેંકનિફ્ટી લેવલ્સ, ઓપ્શન ચેઇન ડેટા અને કેન્ડલસ્ટિક પેટર્નનું લર્નિંગ એનાલિસિસ.',
                'features_json' => [
                    ['icon' => '📈', 'title' => 'ડેઇલી માર્કેટ લેવલ્સ', 'desc' => 'માર્કેટ ખુલતા પહેલા મહત્વપૂર્ણ સપોર્ટ અને રેઝિસ્ટન્સ.'],
                    ['icon' => '🎯', 'title' => 'ઓપ્શન ચેઇન ડેટા', 'desc' => 'સરળ ભાષામાં ઓપન ઇન્ટરેસ્ટ (OI) સમજૂતી.'],
                    ['icon' => '📊', 'title' => 'પ્રાઇસ એક્શન સ્ટ્રેટેજી', 'desc' => 'કેન્ડલસ્ટિક અને ચાર્ટ પેટર્નનું લાઈવ એનાલિસિસ.']
                ],
                'about_heading' => 'અમારા વિશે',
                'about_text' => 'ગુજરાતી ટ્રેડર એક એજ્યુકેશનલ ટેલિગ્રામ ચેનલ છે.',
                'disclaimer_text' => 'શેરબજાર અને ઓપ્શન ટ્રેડિંગ જોખમ ભરેલું છે. અમે કોઈપણ ટિપ્સ આપતા નથી.',
                'footer_text' => '© 2026 Gujarati Trader · શૈક્ષણિક હેતુ માટે જ',
                'primary_cta_text' => 'ફ્રી ટેલિગ્રામ ચેનલ જોઈન કરો',
                'secondary_cta_text' => 'Telegram App ખોલો',
                'telegram_destination' => 'https://t.me/+sncMUjBZ9a41ZDll',
                'telegram_channel_username' => 'gujaratitrdexx',
                'meta_pixel_id' => '1130260856232291',
                'gtm_id' => 'GTM-GT01',
                'is_active' => true,
            ]
        );

        $lpVercel = LandingPage::updateOrCreate(
            ['slug' => 'focusuu'],
            [
                'client_id' => $client2->id,
                'campaign_id' => $camp1?->id,
                'title' => 'focusuu',
                'page_source' => 'vercel',
                'external_url' => 'https://focusuu.vercel.app',
                'vercel_project_name' => 'focusuu',
                'tracking_token' => '9c91e175-b0a1-46ac-8d53-78a71205face',
                'template_type' => 'custom',
                'brand_name' => 'FocusUU Global',
                'brand_tagline' => 'High Speed Trading Focus Room',
                'primary_cta_text' => 'Join Free Telegram Channel',
                'telegram_destination' => 'https://t.me/+sncMUjBZ9a41ZDll',
                'meta_pixel_id' => '1130260856232291',
                'is_active' => true,
            ]
        );

        // 6. CTAs
        $cta1 = Cta::updateOrCreate(
            ['landing_page_id' => $lp1->id, 'button_type' => 'primary'],
            [
                'client_id' => $client1->id,
                'campaign_id' => $camp1?->id,
                'name' => 'STOXK Hero CTA',
                'button_text' => 'Join Free Telegram Channel',
                'tracking_token' => 'kx_stoxk_hero',
                'telegram_destination' => 'https://t.me/+sncMUjBZ9a41ZDll',
                'direct_protocol' => 'auto',
                'click_count' => 1480,
                'is_active' => true,
            ]
        );

        $cta2 = Cta::updateOrCreate(
            ['landing_page_id' => $lp2->id, 'button_type' => 'primary'],
            [
                'client_id' => $client2->id,
                'campaign_id' => $camp4?->id,
                'name' => 'Forex Focus Hero CTA',
                'button_text' => 'Join Free Telegram Channel',
                'tracking_token' => 'kx_ff_hero',
                'telegram_destination' => 'https://t.me/+sncMUjBZ9a41ZDll',
                'direct_protocol' => 'auto',
                'click_count' => 1240,
                'is_active' => true,
            ]
        );

        $cta3 = Cta::updateOrCreate(
            ['landing_page_id' => $lp3->id, 'button_type' => 'primary'],
            [
                'client_id' => $client3->id,
                'campaign_id' => $camp4?->id,
                'name' => 'Gujarati Trader Hero CTA',
                'button_text' => 'ફ્રી ટેલિગ્રામ ચેનલ જોઈન કરો',
                'tracking_token' => 'kx_gt_hero',
                'telegram_destination' => 'https://t.me/+gujaratitrdexx_vip',
                'direct_protocol' => 'auto',
                'click_count' => 890,
                'is_active' => true,
            ]
        );

        // 7. Telegram Bots & Tracked Channels (Canonical -100... ID)
        $bot1 = TelegramBot::updateOrCreate(
            ['username' => 'kirtnixtgtracker_bot'],
            [
                'client_id' => $client1->id,
                'name' => 'KirtniX TG Tracker Bot',
                'bot_token' => '8956518773:AAH9_kirtnix_agency_bot_token',
                'channel_id' => '-1001234567890',
                'channel_title' => 'Gujrati_trader',
                'channel_username' => 'gujaratitrdexx',
                'webhook_secret' => 'whsec_kirtnix_' . Str::random(16),
                'is_webhook_active' => true,
                'is_active' => true,
                'last_webhook_ping_at' => now()->subMinutes(1),
            ]
        );

        $bot2 = TelegramBot::updateOrCreate(
            ['username' => 'ForexFocusVerifyBot'],
            [
                'client_id' => $client2->id,
                'name' => 'Forex Focus Verify Bot',
                'bot_token' => '7192847192:AAH9_sample_ff_token',
                'channel_id' => '-1002194829105',
                'channel_title' => 'Forex Focus Global Community',
                'channel_username' => 'forexfocus_global',
                'webhook_secret' => 'whsec_ff_' . Str::random(16),
                'is_webhook_active' => true,
                'is_active' => true,
                'last_webhook_ping_at' => now()->subMinutes(5),
            ]
        );

        $channel1 = TelegramChannel::updateOrCreate(
            ['telegram_chat_id' => '-1001234567890'],
            [
                'telegram_bot_id' => $bot1->id,
                'client_id' => $client1->id,
                'landing_page_id' => $lp3->id,
                'title' => 'Gujrati_trader',
                'username' => 'gujaratitrdexx',
                'type' => 'channel',
                'member_count' => 13587,
                'is_bot_admin' => true,
                'bot_status' => 'administrator',
                'is_active' => true,
                'connected_at' => now()->subMonths(1),
                'last_synced_at' => now(),
            ]
        );

        $channel2 = TelegramChannel::updateOrCreate(
            ['telegram_chat_id' => '-1002194829104'],
            [
                'telegram_bot_id' => $bot1->id,
                'client_id' => $client1->id,
                'landing_page_id' => $lp1->id,
                'title' => 'STOXK Option Trading Room VIP',
                'username' => 'stoxk_official',
                'type' => 'channel',
                'member_count' => 54200,
                'is_bot_admin' => true,
                'bot_status' => 'administrator',
                'is_active' => true,
                'connected_at' => now()->subMonths(2),
                'last_synced_at' => now(),
            ]
        );

        // 8. Realistic Member Lifecycle Events with Ads vs Direct Attribution
        $memberFixtures = [
            ['name' => 'Aarav Sharma', 'username' => 'aarav_trader', 'event' => 'join', 'source' => 'ads', 'camp' => 'GJ004', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 2],
            ['name' => 'Rohan Varma', 'username' => 'rohan_scalps', 'event' => 'join', 'source' => 'ads', 'camp' => 'GJ004', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 8],
            ['name' => 'Sneha Patel', 'username' => 'sneha_nifty', 'event' => 'join', 'source' => 'ads', 'camp' => 'GJ003', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 14],
            ['name' => 'Vikram Joshi', 'username' => 'vikram_j', 'event' => 'join', 'source' => 'direct', 'camp' => 'GJ004', 'country' => 'IN', 'device' => 'Desktop', 'mins' => 21],
            ['name' => 'Deepak Gupta', 'username' => 'deepak_g', 'event' => 'pending', 'source' => 'ads', 'camp' => 'GJ004', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 30],
            ['name' => 'Pooja Shah', 'username' => 'pooja_trader', 'event' => 'join', 'source' => 'ads', 'camp' => 'GJ002', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 45],
            ['name' => 'Anil Mehta', 'username' => 'anil_m', 'event' => 'leave', 'source' => 'direct', 'camp' => 'GJ001', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 60],
            ['name' => 'Kiran Rao', 'username' => 'kiran_r', 'event' => 'join', 'source' => 'ads', 'camp' => 'GJ004', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 85],
            ['name' => 'Manish Soni', 'username' => 'manish_s', 'event' => 'join', 'source' => 'ads', 'camp' => 'GJ004', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 110],
            ['name' => 'Jayesh Dave', 'username' => 'jayesh_d', 'event' => 'join', 'source' => 'ads', 'camp' => 'GJ003', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 140],
            ['name' => 'Sanjay Parmar', 'username' => 'sanjay_p', 'event' => 'pending', 'source' => 'ads', 'camp' => 'GJ004', 'country' => 'IN', 'device' => 'Mobile', 'mins' => 190],
            ['name' => 'Harshil Shah', 'username' => 'harshil_s', 'event' => 'join', 'source' => 'direct', 'camp' => 'GJ004', 'country' => 'IN', 'device' => 'Desktop', 'mins' => 240],
        ];

        foreach ($memberFixtures as $m) {
            $camp = Campaign::where('name', $m['camp'])->first() ?? $camp1;
            TelegramEvent::create([
                'telegram_bot_id' => $bot1->id,
                'telegram_channel_id' => $channel1->id,
                'client_id' => $client1->id,
                'campaign_id' => $camp?->id,
                'telegram_user_id' => (string) rand(100000000, 999999999),
                'telegram_username' => $m['username'],
                'first_name' => explode(' ', $m['name'])[0],
                'last_name' => explode(' ', $m['name'])[1] ?? '',
                'event_type' => $m['event'],
                'invite_link' => 'https://t.me/+gujaratitrdexx_vip',
                'source' => $m['source'],
                'country' => $m['country'],
                'device' => $m['device'],
                'status_after' => $m['event'] === 'join' ? 'member' : ($m['event'] === 'leave' ? 'left' : 'pending'),
                'event_time' => now()->subMinutes($m['mins']),
            ]);
        }

        // 9. Realistic Notifications
        Notification::updateOrCreate(
            ['title' => 'Meta CAPI Synchronized Successfully'],
            [
                'user_id' => $agencyAdmin->id,
                'client_id' => $client1->id,
                'type' => 'meta_api',
                'severity' => 'info',
                'message' => 'All 342 queued Lead conversion events were successfully delivered to Meta Events Manager with 0 drop-offs.',
                'link' => '/conversion-logs',
                'is_read' => false,
            ]
        );

        Notification::updateOrCreate(
            ['title' => 'Telegram Join Surge Alert: GJ001'],
            [
                'user_id' => $agencyAdmin->id,
                'client_id' => $client1->id,
                'type' => 'telegram',
                'severity' => 'info',
                'message' => 'Campaign GJ001 (Nandu Meena) reached a record 42.8% Join Rate in the last 6 hours.',
                'link' => '/analytics',
                'is_read' => false,
            ]
        );

        Notification::updateOrCreate(
            ['title' => 'Budget Pace Advisory'],
            [
                'user_id' => $agencyAdmin->id,
                'client_id' => $client3->id,
                'type' => 'budget_alert',
                'severity' => 'warning',
                'message' => 'Client KX-003 (Gujarati Trader) has utilized 78% of their allocated monthly budget with 12 days remaining.',
                'link' => '/campaigns',
                'is_read' => false,
            ]
        );

        // 10. Realistic Reports
        Report::updateOrCreate(
            ['title' => 'Weekly Performance Audit — STOXK Academy (Nandu Meena)'],
            [
                'client_id' => $client1->id,
                'user_id' => $agencyAdmin->id,
                'date_range' => 'Last 7 Days',
                'spend' => 1420.50,
                'reach' => 68400,
                'views' => 4820,
                'joins' => 1480,
                'exits' => 84,
                'cost_per_join' => 0.96,
                'conversion_rate' => 30.7,
                'ai_summary' => 'STOXK Academy recorded phenomenal performance across Meta Ads. Campaign GJ001 generated 1,480 verified Telegram joins at an ultra-competitive Cost Per Join of $0.96 (₹79.80). Meta Conversions API delivered 100% event attribution with zero data loss.',
                'ai_observations' => "• Instagram Reels creative outperformed Static Carousel ads by 2.4x in Click-Through Rate (CTR).\n• Highest join volume occurred between 8:30 AM – 9:15 AM IST (Pre-Market window).\n• Retention rate is strong with only 5.6% backout/exit rate.",
                'ai_recommendations' => "• Scale campaign budget by +25% on ad sets targeting Tier 1 metros.\n• Introduce a countdown timer on landing page /lp/stoxk-pro to test urgency.\n• Duplicate winning video creative into a Hindi-specific retargeting ad set.",
                'ai_issues' => "• Minor traffic saturation detected on Saturday/Sunday non-trading days.",
                'ai_next_actions' => "1. Increase daily spend cap to $250.\n2. Schedule ad delivery to pause on weekend non-trading hours.\n3. Sync lookalike audiences of verified Telegram members with Meta Ads Manager.",
                'status' => 'completed',
            ]
        );

        // 11. Security & Login Requests
        LoginRequest::updateOrCreate(
            ['ip_address' => '103.21.144.82', 'email' => 'admin@kirtnix.agency'],
            [
                'user_id' => $agencyAdmin->id,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/128.0.0.0 Safari/537.36',
                'location' => 'Mumbai, India',
                'device' => 'Windows PC (Chrome)',
                'status' => 'approved',
                'requested_at' => now()->subMinutes(15),
            ]
        );

        LoginRequest::updateOrCreate(
            ['ip_address' => '152.58.204.19', 'email' => 'rahul.media@kirtnixity.agency'],
            [
                'user_id' => $manager->id,
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Safari/605.1.15',
                'location' => 'Surat, India',
                'device' => 'MacBook Pro (Safari)',
                'status' => 'approved',
                'requested_at' => now()->subHours(3),
            ]
        );

        // 12. Team Permissions Matrix
        $roles = ['owner', 'admin', 'manager', 'analyst', 'client'];
        $permissions = [
            'view_own_client_only' => ['owner' => false, 'admin' => false, 'manager' => false, 'analyst' => false, 'client' => true],
            'create_edit_landing_pages' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => false, 'client' => false],
            'publish_unpublish_pages' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => false, 'client' => false],
            'view_page_analytics' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => true, 'client' => true],
            'connect_facebook_meta' => ['owner' => true, 'admin' => true, 'manager' => false, 'analyst' => false, 'client' => false],
            'assign_ad_accounts' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => false, 'client' => false],
            'view_spend_budget' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => true, 'client' => true],
            'manage_agency_bot' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => false, 'client' => false],
            'view_join_history' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => true, 'client' => true],
            'use_kirtnix_copilot' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => true, 'client' => false],
            'ai_copywriter' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => false, 'client' => false],
            'view_audit_log' => ['owner' => true, 'admin' => true, 'manager' => false, 'analyst' => false, 'client' => false],
            'export_reports' => ['owner' => true, 'admin' => true, 'manager' => true, 'analyst' => true, 'client' => true],
        ];

        foreach ($permissions as $permKey => $roleMap) {
            foreach ($roles as $r) {
                TeamPermission::updateOrCreate(
                    ['role' => $r, 'permission_key' => $permKey],
                    ['is_granted' => $roleMap[$r] ?? false]
                );
            }
        }
    }
}
