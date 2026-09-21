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

    /**
     * Test that when an administrator approves a join request,
     * the subscriber logged is the joining user (Trader Index Option) and NOT the approving admin (AK GrowthX agency).
     */
    public function test_admin_approval_logs_actual_subscriber_not_admin(): void
    {
        $bot = TelegramBot::firstOrFail();
        $channel = TelegramChannel::where('telegram_bot_id', $bot->id)->first() ?? TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'client_id' => $bot->client_id,
            'telegram_chat_id' => '-1003344556677',
            'title' => 'Admin Approval Channel',
            'is_bot_admin' => true,
        ]);

        $adminUserId = 11223344;
        $subscriberUserId = 99887766;

        // 1. User submits join request
        $joinReqPayload = [
            'update_id' => 7001,
            'chat_join_request' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => [
                    'id' => $subscriberUserId,
                    'first_name' => 'Trader',
                    'last_name' => 'Index Option',
                    'username' => 'trader_index_option',
                ],
                'date' => time(),
            ]
        ];
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $joinReqPayload)->assertStatus(200);

        // Verify request logged for Trader Index Option
        $this->assertDatabaseHas('telegram_events', [
            'telegram_user_id' => (string) $subscriberUserId,
            'first_name' => 'Trader',
            'last_name' => 'Index Option',
            'event_type' => 'join_request',
        ]);

        // 2. Admin (AK GrowthX agency) approves the request in Telegram
        $adminApprovePayload = [
            'update_id' => 7002,
            'chat_member' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => [
                    'id' => $adminUserId,
                    'first_name' => 'AK GrowthX',
                    'last_name' => 'agency',
                    'username' => 'ak_growthx_agency',
                ],
                'date' => time(),
                'old_chat_member' => [
                    'user' => ['id' => $subscriberUserId, 'first_name' => 'Trader', 'last_name' => 'Index Option'],
                    'status' => 'restricted',
                ],
                'new_chat_member' => [
                    'user' => ['id' => $subscriberUserId, 'first_name' => 'Trader', 'last_name' => 'Index Option', 'username' => 'trader_index_option'],
                    'status' => 'member',
                ],
            ]
        ];
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $adminApprovePayload)->assertStatus(200);

        // Verify that the join event is attributed to subscriber (Trader Index Option), NOT the admin!
        $this->assertDatabaseHas('telegram_events', [
            'telegram_user_id' => (string) $subscriberUserId,
            'first_name' => 'Trader',
            'last_name' => 'Index Option',
            'event_type' => 'join',
        ]);

        $this->assertDatabaseMissing('telegram_events', [
            'telegram_user_id' => (string) $adminUserId,
        ]);
    }

    /**
     * Test that channel leaves are recorded and visible in Complete Join History.
     */
    public function test_channel_leave_is_tracked_and_visible_in_history(): void
    {
        $client = Client::firstOrFail();
        $bot = TelegramBot::where('client_id', $client->id)->firstOrFail();
        $channel = TelegramChannel::where('telegram_bot_id', $bot->id)->firstOrFail();

        $leavingUserId = 88776655;

        $leavePayload = [
            'update_id' => 8001,
            'chat_member' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => [
                    'id' => $leavingUserId,
                    'first_name' => 'Leaving',
                    'last_name' => 'Member',
                    'username' => 'leaving_member',
                ],
                'date' => time(),
                'old_chat_member' => [
                    'user' => ['id' => $leavingUserId, 'first_name' => 'Leaving', 'last_name' => 'Member'],
                    'status' => 'member',
                ],
                'new_chat_member' => [
                    'user' => ['id' => $leavingUserId, 'first_name' => 'Leaving', 'last_name' => 'Member'],
                    'status' => 'left',
                ],
            ]
        ];

        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $leavePayload)->assertStatus(200);

        // Verify event logged with event_type = leave
        $this->assertDatabaseHas('telegram_events', [
            'telegram_user_id' => (string) $leavingUserId,
            'first_name' => 'Leaving',
            'event_type' => 'leave',
        ]);

        // Verify analytics detail page displays Channel Leave badge
        $res = $this->get(route('analytics.detail', $client->kx_code));
        $res->assertStatus(200);
        $res->assertSee('Channel Leave');
        $res->assertSee('Leaving Member');
    }

    /**
     * Requirement: Join Request transitions from pending to approved in-place
     * Member appears only once in the Complete Join History table.
     */
    public function test_join_request_transitions_to_approved_in_place_without_duplicate_row(): void
    {
        $client = Client::firstOrFail();
        $bot = TelegramBot::where('client_id', $client->id)->firstOrFail();
        $channel = TelegramChannel::where('telegram_bot_id', $bot->id)->firstOrFail();

        $memberUserId = 77112233;

        // Step 1: User requests to join (chat_join_request)
        $joinReqPayload = [
            'update_id' => 9001,
            'chat_join_request' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => [
                    'id' => $memberUserId,
                    'first_name' => 'Trader',
                    'last_name' => 'Vanshika',
                    'username' => 'TraderVanshika',
                ],
                'date' => time(),
            ]
        ];

        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $joinReqPayload)->assertStatus(200);

        // Verify initial state is pending
        $this->assertDatabaseHas('telegram_events', [
            'telegram_user_id' => (string) $memberUserId,
            'event_type' => 'join_request',
            'status_after' => 'pending',
        ]);

        $res1 = $this->get(route('analytics.detail', $client->kx_code));
        $res1->assertStatus(200);
        $res1->assertSee('@TraderVanshika');
        $res1->assertSee('pending');

        // Step 2: Admin approves/accepts the join request (chat_member with new_chat_member = member)
        $approvePayload = [
            'update_id' => 9002,
            'chat_member' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => [
                    'id' => 998877, // Admin user ID
                    'first_name' => 'AK GrowthX',
                    'username' => 'ak_growthx_admin',
                ],
                'date' => time(),
                'old_chat_member' => [
                    'user' => ['id' => $memberUserId, 'first_name' => 'Trader', 'last_name' => 'Vanshika', 'username' => 'TraderVanshika'],
                    'status' => 'restricted',
                ],
                'new_chat_member' => [
                    'user' => ['id' => $memberUserId, 'first_name' => 'Trader', 'last_name' => 'Vanshika', 'username' => 'TraderVanshika'],
                    'status' => 'member',
                ],
            ]
        ];

        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $approvePayload)->assertStatus(200);

        // Verify that the existing event was updated in-place to join/member, NOT duplicated!
        $this->assertEquals(
            1,
            TelegramEvent::where('telegram_channel_id', $channel->id)->where('telegram_user_id', (string) $memberUserId)->count(),
            'Only 1 TelegramEvent record should exist for this subscriber transition'
        );

        $this->assertDatabaseHas('telegram_events', [
            'telegram_user_id' => (string) $memberUserId,
            'event_type' => 'join',
            'status_after' => 'approved',
        ]);

        // Verify that detail view renders approved and only 1 occurrence of @TraderVanshika
        $res2 = $this->get(route('analytics.detail', $client->kx_code));
        $res2->assertStatus(200);
        $res2->assertSee('@TraderVanshika');
        $res2->assertSee('approved');
        $this->assertEquals(1, substr_count($res2->getContent(), '@TraderVanshika'));
    }

    /**
     * Requirement: Original arrival/join_request timestamp is preserved when admin approves.
     * Approving a yesterday's request today does NOT inflate today's subscriber count.
     */
    public function test_original_event_time_preserved_when_admin_approves_and_daily_analytics_not_skewed(): void
    {
        $client = Client::firstOrFail();
        $bot = TelegramBot::where('client_id', $client->id)->firstOrFail();
        $channel = TelegramChannel::where('telegram_bot_id', $bot->id)->firstOrFail();

        // Ensure active campaign exists for client
        $campaign = Campaign::firstOrCreate(
            ['client_id' => $client->id, 'name' => 'Scalping Masterclass Ad'],
            ['slug' => 'scalping-masterclass-ad', 'status' => 'ACTIVE', 'ad_account_id' => $client->ad_account_id]
        );

        $yesterdayUserId = 55667788;
        $yesterdayTime = now()->subDay()->subHours(2);

        // Step 1: User requested yesterday
        $joinReqPayload = [
            'update_id' => 9101,
            'chat_join_request' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => [
                    'id' => $yesterdayUserId,
                    'first_name' => 'Yesterday',
                    'last_name' => 'Trader',
                    'username' => 'YesterdayTrader',
                ],
                'date' => $yesterdayTime->timestamp,
            ]
        ];

        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $joinReqPayload)->assertStatus(200);

        // Manually set event_time and created_at to yesterday for this test
        TelegramEvent::where('telegram_user_id', (string) $yesterdayUserId)->update([
            'event_time' => $yesterdayTime,
            'created_at' => $yesterdayTime,
        ]);

        // Step 2: Admin approves TODAY
        $approvePayload = [
            'update_id' => 9102,
            'chat_member' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => [
                    'id' => 998877, // Admin user ID
                    'first_name' => 'AK GrowthX',
                    'username' => 'ak_growthx_admin',
                ],
                'date' => time(),
                'old_chat_member' => [
                    'user' => ['id' => $yesterdayUserId, 'first_name' => 'Yesterday', 'last_name' => 'Trader', 'username' => 'YesterdayTrader'],
                    'status' => 'restricted',
                ],
                'new_chat_member' => [
                    'user' => ['id' => $yesterdayUserId, 'first_name' => 'Yesterday', 'last_name' => 'Trader', 'username' => 'YesterdayTrader'],
                    'status' => 'member',
                ],
            ]
        ];

        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $approvePayload)->assertStatus(200);

        // Verify that event_time remained yesterday's timestamp!
        $event = TelegramEvent::where('telegram_user_id', (string) $yesterdayUserId)->first();
        $this->assertNotNull($event);
        $this->assertEquals('approved', $event->status_after);
        $this->assertEquals($yesterdayTime->format('Y-m-d H:i:s'), $event->event_time->format('Y-m-d H:i:s'));

        // Verify Today analytics: does NOT count this approved member in today's subscribers count
        $todayRes = $this->get(route('analytics.detail', ['slug' => $client->kx_code, 'date_range' => 'today']));
        $todayRes->assertStatus(200);

        // Live metrics endpoint for today
        $todayLive = $this->getJson(route('api.analytics.live_metrics', ['slug' => $client->kx_code, 'date_range' => 'today']));
        $todayLive->assertStatus(200);
        $this->assertEquals(0, $todayLive->json('kpis.subscribers'));

        // Live metrics endpoint for yesterday
        $yesterdayLive = $this->getJson(route('api.analytics.live_metrics', ['slug' => $client->kx_code, 'date_range' => 'yesterday']));
        $yesterdayLive->assertStatus(200);
        $this->assertEquals(1, $yesterdayLive->json('kpis.subscribers'));
    }

    public function test_multiple_campaigns_attribution_accurately_tracks_distinct_campaign_names_per_subscriber(): void
    {
        $client = Client::firstOrFail();
        $bot = TelegramBot::where('client_id', $client->id)->firstOrFail();
        $channel = TelegramChannel::where('telegram_bot_id', $bot->id)->firstOrFail();
        $landingPage = LandingPage::where('client_id', $client->id)->firstOrFail();
        $cta = Cta::where('landing_page_id', $landingPage->id)->firstOrFail();

        // Create 2 Distinct Campaigns for this client
        $camp1 = Campaign::create([
            'client_id' => $client->id,
            'name' => 'New Lead ad || 19 Sept 1',
            'slug' => 'new-lead-ad-19-sept-1',
            'campaign_id' => 'cmp_111111111',
            'status' => 'active',
            'ad_account_id' => $client->ad_account_id,
        ]);

        $camp2 = Campaign::create([
            'client_id' => $client->id,
            'name' => 'New Lead ad || 19 Sept 2',
            'slug' => 'new-lead-ad-19-sept-2',
            'campaign_id' => 'cmp_222222222',
            'status' => 'active',
            'ad_account_id' => $client->ad_account_id,
        ]);

        // Subscriber A arrives from Campaign 1
        $sessionA = TrackingSession::create([
            'session_id' => (string) Str::uuid(),
            'visitor_id' => 'vid_user_multi_a',
            'client_id' => $client->id,
            'landing_page_id' => $landingPage->id,
            'campaign_id' => $camp1->id,
            'utm_source' => 'meta',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'New Lead ad || 19 Sept 1',
            'fbclid' => 'fb_click_111',
        ]);

        $clickA = CtaClick::create([
            'tracking_session_id' => $sessionA->id,
            'cta_id' => $cta->id,
            'landing_page_id' => $landingPage->id,
            'client_id' => $client->id,
            'campaign_id' => $camp1->id,
            'tracking_token' => $cta->tracking_token,
            'destination_url' => 'https://t.me/multicamp_channel',
            'visitor_id' => 'vid_user_multi_a',
        ]);

        // Telegram webhook for Subscriber A
        $userAId = 11110001;
        $joinPayloadA = [
            'update_id' => 9001,
            'chat_join_request' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => ['id' => $userAId, 'first_name' => 'Subscriber', 'last_name' => 'One', 'username' => 'subscriber_one'],
                'user_chat_id' => $userAId,
                'date' => time(),
            ]
        ];
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $joinPayloadA)->assertStatus(200);

        // Subscriber B arrives from Campaign 2
        $sessionB = TrackingSession::create([
            'session_id' => (string) Str::uuid(),
            'visitor_id' => 'vid_user_multi_b',
            'client_id' => $client->id,
            'landing_page_id' => $landingPage->id,
            'campaign_id' => $camp2->id,
            'utm_source' => 'meta',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'New Lead ad || 19 Sept 2',
            'fbclid' => 'fb_click_222',
        ]);

        $clickB = CtaClick::create([
            'tracking_session_id' => $sessionB->id,
            'cta_id' => $cta->id,
            'landing_page_id' => $landingPage->id,
            'client_id' => $client->id,
            'campaign_id' => $camp2->id,
            'tracking_token' => $cta->tracking_token,
            'destination_url' => 'https://t.me/multicamp_channel',
            'visitor_id' => 'vid_user_multi_b',
        ]);

        // Telegram webhook for Subscriber B
        $userBId = 22220002;
        $joinPayloadB = [
            'update_id' => 9002,
            'chat_join_request' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => ['id' => $userBId, 'first_name' => 'Subscriber', 'last_name' => 'Two', 'username' => 'subscriber_two'],
                'user_chat_id' => $userBId,
                'date' => time(),
            ]
        ];
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $joinPayloadB)->assertStatus(200);

        // Verify Event A is linked to Campaign 1
        $eventA = TelegramEvent::where('telegram_user_id', (string) $userAId)->first();
        $this->assertNotNull($eventA);
        $this->assertEquals($camp1->id, $eventA->campaign_id);

        // Verify Event B is linked to Campaign 2
        $eventB = TelegramEvent::where('telegram_user_id', (string) $userBId)->first();
        $this->assertNotNull($eventB);
        $this->assertEquals($camp2->id, $eventB->campaign_id);

        // Verify detail page displays both campaign names individually
        $detailRes = $this->get(route('analytics.detail', $client->kx_code));
        $detailRes->assertStatus(200);
        $detailRes->assertSee('New Lead ad || 19 Sept 1');
        $detailRes->assertSee('New Lead ad || 19 Sept 2');

        // Verify live metrics API returns both campaign names
        $liveMetricsRes = $this->getJson(route('api.analytics.live_metrics', ['slug' => $client->kx_code]));
        $liveMetricsRes->assertStatus(200);
        $recentEvents = $liveMetricsRes->json('events');
        $this->assertNotEmpty($recentEvents);

        $campNames = array_column($recentEvents, 'campaign');
        $this->assertContains('New Lead ad || 19 Sept 1', $campNames);
        $this->assertContains('New Lead ad || 19 Sept 2', $campNames);
    }

    public function test_direct_joins_accurately_attributed_as_direct_organic_and_never_steal_claimed_ad_clicks(): void
    {
        $client = Client::firstOrFail();
        $bot = TelegramBot::where('client_id', $client->id)->firstOrFail();
        $channel = TelegramChannel::where('telegram_bot_id', $bot->id)->firstOrFail();
        $landingPage = LandingPage::where('client_id', $client->id)->firstOrFail();
        $cta = Cta::where('landing_page_id', $landingPage->id)->firstOrFail();

        $camp = Campaign::create([
            'client_id' => $client->id,
            'name' => 'Paid Scalping Masterclass',
            'slug' => 'paid-scalping-masterclass',
            'campaign_id' => 'cmp_999888777',
            'status' => 'active',
            'ad_account_id' => $client->ad_account_id,
        ]);

        // Step 1: User 1 arrives from Paid Ads and clicks CTA
        $session1 = TrackingSession::create([
            'session_id' => (string) Str::uuid(),
            'visitor_id' => 'vid_user_ad_1',
            'client_id' => $client->id,
            'landing_page_id' => $landingPage->id,
            'campaign_id' => $camp->id,
            'utm_source' => 'meta',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'Paid Scalping Masterclass',
            'fbclid' => 'fb_ad_click_1',
        ]);

        $click1 = CtaClick::create([
            'tracking_session_id' => $session1->id,
            'cta_id' => $cta->id,
            'landing_page_id' => $landingPage->id,
            'client_id' => $client->id,
            'campaign_id' => $camp->id,
            'tracking_token' => $cta->tracking_token,
            'destination_url' => 'https://t.me/scalping_channel',
            'visitor_id' => 'vid_user_ad_1',
        ]);

        // User 1 joins Telegram
        $adUserTgId = 77112233;
        $joinPayloadAd = [
            'update_id' => 9501,
            'chat_join_request' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => ['id' => $adUserTgId, 'first_name' => 'Ad', 'last_name' => 'Subscriber', 'username' => 'ad_subscriber'],
                'user_chat_id' => $adUserTgId,
                'date' => time(),
            ]
        ];
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $joinPayloadAd)->assertStatus(200);

        // Step 2: User 2 arrives DIRECTLY on Telegram (organic/shared channel link) with NO CTA click
        $directUserTgId = 88334455;
        $joinPayloadDirect = [
            'update_id' => 9502,
            'chat_join_request' => [
                'chat' => ['id' => (int) $channel->telegram_chat_id, 'title' => $channel->title, 'type' => 'channel'],
                'from' => ['id' => $directUserTgId, 'first_name' => 'Direct', 'last_name' => 'Organic', 'username' => 'direct_organic'],
                'user_chat_id' => $directUserTgId,
                'date' => time() + 10,
            ]
        ];
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $joinPayloadDirect)->assertStatus(200);

        // Verify User 1 is marked as ads and linked to campaign
        $adEvent = TelegramEvent::where('telegram_user_id', (string) $adUserTgId)->first();
        $this->assertNotNull($adEvent);
        $this->assertEquals('ads', $adEvent->source);
        $this->assertEquals($camp->id, $adEvent->campaign_id);
        $this->assertEquals($click1->id, $adEvent->cta_click_id);

        // Verify User 2 is marked as direct and has NO campaign
        $directEvent = TelegramEvent::where('telegram_user_id', (string) $directUserTgId)->first();
        $this->assertNotNull($directEvent);
        $this->assertEquals('direct', $directEvent->source);
        $this->assertNull($directEvent->campaign_id);
        $this->assertNull($directEvent->cta_click_id);

        // Verify Detail view shows Paid Ads for User 1 and Direct / Organic for User 2
        $detailRes = $this->get(route('analytics.detail', $client->kx_code));
        $detailRes->assertStatus(200);
        $detailRes->assertSee('Paid Ads');
        $detailRes->assertSee('Direct / Organic');
        $detailRes->assertSee('Paid Scalping Masterclass');

        // Verify Live metrics endpoint
        $liveRes = $this->getJson(route('api.analytics.live_metrics', ['slug' => $client->kx_code]));
        $liveRes->assertStatus(200);
        $events = $liveRes->json('events');

        $directRow = collect($events)->firstWhere('user_id', (string) $directUserTgId);
        $this->assertNotNull($directRow);
        $this->assertEquals('Direct / Organic', $directRow['source_label']);
        $this->assertFalse($directRow['is_ads']);
        $this->assertNull($directRow['campaign']);

        $adRow = collect($events)->firstWhere('user_id', (string) $adUserTgId);
        $this->assertNotNull($adRow);
        $this->assertEquals('Paid Ads', $adRow['source_label']);
        $this->assertTrue($adRow['is_ads']);
        $this->assertEquals('Paid Scalping Masterclass', $adRow['campaign']);
    }
}


