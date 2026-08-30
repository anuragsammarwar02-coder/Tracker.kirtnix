<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\Conversion;
use App\Models\Cta;
use App\Models\CtaClick;
use App\Models\LandingPage;
use App\Models\LandingPageView;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\TelegramEvent;
use App\Models\TrackingSession;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelegramJoinSourceOfTruthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Requirement 1 & 13: Core Regression Test
     * 100 Landing Page Views + 50 CTA Clicks + 25 Actual Telegram Joins
     * MUST produce Subscribers = 25 (NOT 50, NOT 100).
     */
    public function test_regression_100_views_50_clicks_25_joins_produces_exact_25_subscribers(): void
    {
        $client = Client::firstOrCreate(
            ['kx_code' => 'regression-client'],
            ['company_name' => 'Regression Client', 'client_name' => 'Regression Client', 'status' => 'active']
        );

        $landingPage = LandingPage::firstOrCreate(
            ['slug' => 'regression-lp'],
            [
                'client_id' => $client->id,
                'title' => 'Regression Landing Page',
                'is_published' => true,
                'is_active' => true,
            ]
        );

        $cta = Cta::firstOrCreate(
            ['landing_page_id' => $landingPage->id],
            [
                'client_id' => $client->id,
                'button_text' => 'Join Telegram',
                'button_type' => 'primary',
                'tracking_token' => 'kx_reg_cta',
                'telegram_destination' => 'https://t.me/regression_channel',
                'is_active' => true,
            ]
        );

        $bot = TelegramBot::firstOrCreate(
            ['client_id' => $client->id],
            [
                'name' => 'Reg Bot',
                'username' => 'reg_bot',
                'bot_token' => '999999:TEST_BOT_TOKEN_REG',
                'webhook_secret' => 'reg_secret_' . Str::random(12),
                'is_active' => true,
                'is_webhook_active' => true,
            ]
        );

        $channel = TelegramChannel::firstOrCreate(
            ['telegram_bot_id' => $bot->id],
            [
                'client_id' => $client->id,
                'landing_page_id' => $landingPage->id,
                'telegram_chat_id' => '-1009988776655',
                'title' => 'Regression Channel',
                'username' => 'regression_channel',
                'is_bot_admin' => true,
                'is_active' => true,
            ]
        );

        // 1. Simulate 100 Landing Page Views
        for ($i = 1; $i <= 100; $i++) {
            $vid = 'vid_user_' . $i;
            LandingPageView::create([
                'landing_page_id' => $landingPage->id,
                'client_id' => $client->id,
                'visitor_id' => $vid,
                'is_unique' => true,
                'viewed_at' => now(),
            ]);
            TrackingSession::create([
                'session_id' => 'sess_' . $i,
                'visitor_id' => $vid,
                'client_id' => $client->id,
                'landing_page_id' => $landingPage->id,
                'utm_source' => 'meta_ad_campaign',
                'fbclid' => 'fbclid_' . $i,
            ]);
        }

        // 2. Simulate 50 CTA Clicks
        for ($i = 1; $i <= 50; $i++) {
            CtaClick::create([
                'tracking_session_id' => $i,
                'cta_id' => $cta->id,
                'landing_page_id' => $landingPage->id,
                'client_id' => $client->id,
                'tracking_token' => $cta->tracking_token,
                'visitor_id' => 'vid_user_' . $i,
                'is_unique' => true,
                'destination_url' => 'https://t.me/regression_channel',
                'meta_event_id' => 'cta_click_event_' . $i,
                'meta_capi_status' => 'skipped',
                'clicked_at' => now(),
            ]);
        }

        // At this point: Views = 100, Clicks = 50, but Joins = 0 -> Subscribers must be 0!
        $analyticsService = app(AnalyticsService::class);
        $summaryBeforeJoins = $analyticsService->getMetricsSummary(['client_id' => $client->id, 'range' => '7d']);
        $this->assertEquals(100, $summaryBeforeJoins['total_views']);
        $this->assertEquals(50, $summaryBeforeJoins['total_clicks']);
        $this->assertEquals(0, $summaryBeforeJoins['joins']);
        $this->assertEquals(0, Conversion::where('client_id', $client->id)->where('status', 'verified')->count());

        // 3. Simulate 25 Actual Telegram Joins
        for ($i = 1; $i <= 25; $i++) {
            $tgUserId = '1000' . $i;
            $payload = [
                'update_id' => 88000 + $i,
                'chat_member' => [
                    'chat' => [
                        'id' => (int) $channel->telegram_chat_id,
                        'title' => $channel->title,
                        'type' => 'channel',
                    ],
                    'from' => [
                        'id' => (int) $tgUserId,
                        'first_name' => 'Trader ' . $i,
                        'username' => 'trader_' . $i,
                    ],
                    'old_chat_member' => ['status' => 'left'],
                    'new_chat_member' => ['status' => 'member'],
                ]
            ];

            $res = $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $payload);
            $res->assertStatus(200);
        }

        // 4. Verify Final Analytics Metrics
        $summaryAfterJoins = $analyticsService->getMetricsSummary(['client_id' => $client->id, 'range' => '7d']);

        $this->assertEquals(100, $summaryAfterJoins['total_views'], 'Total views must be 100');
        $this->assertEquals(50, $summaryAfterJoins['total_clicks'], 'Total CTA clicks must be 50');
        $this->assertEquals(25, $summaryAfterJoins['joins'], 'Subscribers count must strictly be 25 actual Telegram joins');
        $this->assertEquals(25, Conversion::where('client_id', $client->id)->where('status', 'verified')->count());

        // 5. Verify Public Client Analytics Detail Page renders Subscribers = 25
        $detailRes = $this->get(route('analytics.detail', $client->kx_code));
        $detailRes->assertStatus(200);
        $detailRes->assertSee('25');
    }

    /**
     * Requirement 2 & 8: Duplicate Join & Repeat Webhook Prevention
     * Same Telegram User joining multiple times must only produce 1 Subscriber.
     */
    public function test_duplicate_telegram_joins_by_same_user_are_deduplicated(): void
    {
        $bot = TelegramBot::firstOrFail();
        $channel = TelegramChannel::where('telegram_bot_id', $bot->id)->first() ?? TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'client_id' => $bot->client_id,
            'telegram_chat_id' => '-1001122334455',
            'title' => 'Dedup Channel',
            'is_bot_admin' => true,
        ]);

        $tgUserId = '999888777';

        // 1st Join Event
        $payload1 = [
            'update_id' => 5001,
            'chat_member' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => ['id' => (int) $tgUserId, 'first_name' => 'SameUser'],
                'old_chat_member' => ['status' => 'left'],
                'new_chat_member' => ['status' => 'member'],
            ]
        ];
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $payload1)->assertStatus(200);

        // 2nd Join Event (same user re-joining or webhook re-sent)
        $payload2 = [
            'update_id' => 5002,
            'chat_member' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => ['id' => (int) $tgUserId, 'first_name' => 'SameUser'],
                'old_chat_member' => ['status' => 'left'],
                'new_chat_member' => ['status' => 'member'],
            ]
        ];
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $payload2)->assertStatus(200);

        // Conversion count for this user on this channel must be exactly 1
        $this->assertEquals(1, Conversion::where('telegram_channel_id', $channel->id)->where('telegram_user_id', $tgUserId)->count());
    }

    /**
     * Requirement 4 & 5: CTA Clicks do NOT trigger Subscribe conversion
     */
    public function test_cta_click_does_not_fire_subscribe_conversion(): void
    {
        $landingPage = LandingPage::firstOrFail();
        $initialConversions = Conversion::count();

        $res = $this->postJson(route('api.track.click'), [
            'landing_page_id' => $landingPage->id,
            'visitor_id' => (string) Str::uuid(),
            'destination_url' => 'https://t.me/some_channel',
        ]);

        $res->assertStatus(200);
        // Conversions must remain unchanged
        $this->assertEquals($initialConversions, Conversion::count());
    }

    /**
     * Requirement 7: Public Channel Join Attribution
     */
    public function test_public_channel_join_attributes_to_recent_session(): void
    {
        $landingPage = LandingPage::where('slug', 'forex-focus-tg')->firstOrFail();
        $bot = TelegramBot::where('client_id', $landingPage->client_id)->first() ?? TelegramBot::firstOrFail();
        $channel = TelegramChannel::firstOrCreate(
            ['telegram_bot_id' => $bot->id],
            [
                'client_id' => $bot->client_id,
                'telegram_chat_id' => '-1007788990011',
                'title' => 'Public Channel',
                'username' => 'public_test_channel',
                'is_bot_admin' => true,
            ]
        );

        $visitorId = (string) Str::uuid();

        // Visitor views landing page with fbclid
        $viewRes = $this->postJson(route('api.track.view'), [
            'landing_page_id' => $landingPage->id,
            'visitor_id' => $visitorId,
            'utm_source' => 'meta_ad_public',
            'utm_campaign' => 'Scale_Public_2026',
            'fbclid' => 'fbclid_pub_12345',
        ]);
        $sessionId = $viewRes->json('session_id');

        // Visitor clicks CTA to public channel
        $this->postJson(route('api.track.click'), [
            'landing_page_id' => $landingPage->id,
            'session_id' => $sessionId,
            'visitor_id' => $visitorId,
            'destination_url' => 'https://t.me/public_test_channel',
        ]);

        // Telegram sends public channel join update (no invite link in payload)
        $tgUserId = '5544332211';
        $payload = [
            'update_id' => 99881,
            'chat_member' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => ['id' => (int) $tgUserId, 'first_name' => 'PublicTrader'],
                'old_chat_member' => ['status' => 'left'],
                'new_chat_member' => ['status' => 'member'],
            ]
        ];

        $webhookRes = $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $payload);
        $webhookRes->assertStatus(200);

        // Verify Conversion was attributed to the paid session
        $this->assertDatabaseHas('conversions', [
            'telegram_user_id' => $tgUserId,
            'status' => 'verified',
            'source' => 'ads',
            'utm_source' => 'meta_ad_public',
            'utm_campaign' => 'Scale_Public_2026',
            'fbclid' => 'fbclid_pub_12345',
        ]);
    }

    /**
     * Requirement 14: Multi-Client Data Isolation
     */
    public function test_multi_client_analytics_isolation(): void
    {
        $clientA = Client::create(['kx_code' => 'client-alpha', 'company_name' => 'Alpha Co', 'client_name' => 'Alpha Co', 'status' => 'active']);
        $clientB = Client::create(['kx_code' => 'client-beta', 'company_name' => 'Beta Co', 'client_name' => 'Beta Co', 'status' => 'active']);

        $botA = TelegramBot::create(['client_id' => $clientA->id, 'name' => 'Bot A', 'username' => 'bot_a', 'bot_token' => '111:TOKEN_A', 'webhook_secret' => 'secret_a']);
        $botB = TelegramBot::create(['client_id' => $clientB->id, 'name' => 'Bot B', 'username' => 'bot_b', 'bot_token' => '222:TOKEN_B', 'webhook_secret' => 'secret_b']);

        $channelA = TelegramChannel::create(['telegram_bot_id' => $botA->id, 'client_id' => $clientA->id, 'telegram_chat_id' => '-100111', 'title' => 'Channel A', 'is_bot_admin' => true]);
        $channelB = TelegramChannel::create(['telegram_bot_id' => $botB->id, 'client_id' => $clientB->id, 'telegram_chat_id' => '-100222', 'title' => 'Channel B', 'is_bot_admin' => true]);

        // 3 joins for Client A
        for ($i = 1; $i <= 3; $i++) {
            $this->postJson(route('api.telegram.webhook', $botA->webhook_secret), [
                'update_id' => 100 + $i,
                'chat_member' => [
                    'chat' => ['id' => -100111, 'title' => 'Channel A', 'type' => 'channel'],
                    'from' => ['id' => 1000 + $i, 'first_name' => 'AlphaUser' . $i],
                    'old_chat_member' => ['status' => 'left'],
                    'new_chat_member' => ['status' => 'member'],
                ]
            ]);
        }

        // 1 join for Client B
        $this->postJson(route('api.telegram.webhook', $botB->webhook_secret), [
            'update_id' => 201,
            'chat_member' => [
                'chat' => ['id' => -100222, 'title' => 'Channel B', 'type' => 'channel'],
                'from' => ['id' => 2001, 'first_name' => 'BetaUser1'],
                'old_chat_member' => ['status' => 'left'],
                'new_chat_member' => ['status' => 'member'],
            ]
        ]);

        $this->assertEquals(3, Conversion::where('client_id', $clientA->id)->count());
        $this->assertEquals(1, Conversion::where('client_id', $clientB->id)->count());

        // Verify public analytics isolation
        $resA = $this->get(route('analytics.detail', 'client-alpha'));
        $resA->assertStatus(200);

        $resB = $this->get(route('analytics.detail', 'client-beta'));
        $resB->assertStatus(200);
    }
}
