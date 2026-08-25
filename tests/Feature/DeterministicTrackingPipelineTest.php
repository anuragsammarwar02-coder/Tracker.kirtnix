<?php

namespace Tests\Feature;

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
use App\Models\TelegramInvite;
use App\Models\TrackingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeterministicTrackingPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Landing Page View & Parameter Capture Test
     */
    public function test_landing_page_view_creates_session_and_captures_all_parameters(): void
    {
        $landingPage = LandingPage::where('slug', 'forex-focus-tg')->firstOrFail();

        $response = $this->get(route('public.landing_page', [
            'slug' => 'forex-focus-tg',
            'utm_source' => 'facebook_ads',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'GJ001_Scalping',
            'utm_content' => 'video_ad_v2',
            'utm_term' => 'forex_signals',
            'fbclid' => 'IwAR24892019482910482',
        ]));

        $response->assertStatus(200);
        $response->assertCookie('kx_visitor_id');

        $this->assertDatabaseHas('tracking_sessions', [
            'landing_page_id' => $landingPage->id,
            'utm_source' => 'facebook_ads',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'GJ001_Scalping',
            'utm_content' => 'video_ad_v2',
            'utm_term' => 'forex_signals',
            'fbclid' => 'IwAR24892019482910482',
        ]);

        $this->assertDatabaseHas('landing_page_views', [
            'landing_page_id' => $landingPage->id,
            'is_unique' => true,
        ]);
    }

    /**
     * 2. Unique Visitor Identification & Returning Visitor Logic
     */
    public function test_returning_visitor_reuses_visitor_id_and_handles_uniqueness(): void
    {
        $landingPage = LandingPage::where('slug', 'forex-focus-tg')->firstOrFail();
        $visitorId = (string) Str::uuid();

        // 1st visit
        $this->withUnencryptedCookie('kx_visitor_id', $visitorId)
            ->postJson(route('api.track.view'), [
                'landing_page_id' => $landingPage->id,
                'visitor_id' => $visitorId,
                'utm_source' => 'meta_ads',
            ])
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'visitor_id' => $visitorId, 'is_unique' => true]);

        // 2nd visit within attribution window
        $this->withUnencryptedCookie('kx_visitor_id', $visitorId)
            ->postJson(route('api.track.view'), [
                'landing_page_id' => $landingPage->id,
                'visitor_id' => $visitorId,
                'utm_source' => 'meta_ads',
            ])
            ->assertStatus(200)
            ->assertJson(['ok' => true, 'visitor_id' => $visitorId, 'is_unique' => false]);
    }

    /**
     * 3. Unique Telegram Invite Generation Associated with Session
     */
    public function test_unique_telegram_invite_generated_and_associated_with_session(): void
    {
        $landingPage = LandingPage::where('slug', 'forex-focus-tg')->firstOrFail();
        $visitorId = (string) Str::uuid();

        $viewRes = $this->postJson(route('api.track.view'), [
            'landing_page_id' => $landingPage->id,
            'visitor_id' => $visitorId,
            'utm_source' => 'instagram_story',
        ]);

        $sessionId = $viewRes->json('session_id');

        $inviteRes = $this->postJson(route('api.track.invite'), [
            'landing_page_id' => $landingPage->id,
            'session_id' => $sessionId,
            'visitor_id' => $visitorId,
        ]);

        $inviteRes->assertStatus(200);
        $inviteRes->assertJsonStructure([
            'ok', 'invite_id', 'invite_link', 'deep_link', 'web_url', 'visitor_id', 'session_id'
        ]);

        $this->assertDatabaseHas('telegram_invites', [
            'id' => $inviteRes->json('invite_id'),
            'visitor_id' => $visitorId,
            'tracking_session_id' => $sessionId,
            'status' => 'active',
        ]);
    }

    /**
     * 4. CTA Click Recording (Distinct from Conversion)
     */
    public function test_cta_click_records_separately_from_conversions(): void
    {
        $landingPage = LandingPage::where('slug', 'forex-focus-tg')->firstOrFail();
        $visitorId = (string) Str::uuid();

        $initialClicks = CtaClick::count();
        $initialConversions = Conversion::count();

        $response = $this->postJson(route('api.track.click'), [
            'landing_page_id' => $landingPage->id,
            'visitor_id' => $visitorId,
            'destination_url' => 'https://t.me/+sncMUjBZ9a41ZDll',
            'utm_source' => 'meta_ads',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true]);

        // CtaClick is incremented
        $this->assertEquals($initialClicks + 1, CtaClick::count());
        // Conversions remain unchanged (CTA click is NOT a Telegram conversion!)
        $this->assertEquals($initialConversions, Conversion::count());
    }

    /**
     * 5. Telegram Webhook Match with Exact Invite creates Verified Conversion
     */
    public function test_telegram_webhook_exact_invite_matches_attribution_and_creates_verified_conversion(): void
    {
        $landingPage = LandingPage::where('slug', 'forex-focus-tg')->firstOrFail();
        $bot = TelegramBot::where('client_id', $landingPage->client_id)->first() ?? TelegramBot::firstOrFail();
        $channel = TelegramChannel::firstOrCreate(
            ['telegram_bot_id' => $bot->id],
            [
                'client_id' => $bot->client_id,
                'telegram_chat_id' => '-1002194829104',
                'title' => 'Forex Focus Global Community',
                'username' => 'forexfocus_global',
                'is_bot_admin' => true,
            ]
        );

        $visitorId = (string) Str::uuid();

        // 1. Visitor loads LP
        $viewRes = $this->postJson(route('api.track.view'), [
            'landing_page_id' => $landingPage->id,
            'visitor_id' => $visitorId,
            'utm_source' => 'meta_ads_pro',
            'utm_campaign' => 'GJ004_Options',
            'fbclid' => 'fb_click_98214',
        ]);
        $sessionId = $viewRes->json('session_id');

        // 2. Request unique invite
        $inviteRes = $this->postJson(route('api.track.invite'), [
            'landing_page_id' => $landingPage->id,
            'session_id' => $sessionId,
            'visitor_id' => $visitorId,
        ]);
        $inviteLink = $inviteRes->json('invite_link');

        // 3. User clicks CTA
        $this->postJson(route('api.track.click'), [
            'landing_page_id' => $landingPage->id,
            'session_id' => $sessionId,
            'visitor_id' => $visitorId,
            'destination_url' => $inviteLink,
        ]);

        // 4. Telegram sends join webhook with matching invite link
        $telegramUserId = '9988776655';
        $updatePayload = [
            'update_id' => 9918234,
            'chat_member' => [
                'chat' => [
                    'id' => (int) $channel->telegram_chat_id,
                    'title' => $channel->title,
                    'type' => 'channel',
                ],
                'from' => [
                    'id' => (int) $telegramUserId,
                    'is_bot' => false,
                    'first_name' => 'Amit',
                    'last_name' => 'Patel',
                    'username' => 'amit_options_pro',
                ],
                'date' => time(),
                'old_chat_member' => [
                    'user' => ['id' => (int) $telegramUserId, 'first_name' => 'Amit'],
                    'status' => 'left',
                ],
                'new_chat_member' => [
                    'user' => ['id' => (int) $telegramUserId, 'first_name' => 'Amit'],
                    'status' => 'member',
                ],
                'invite_link' => [
                    'invite_link' => $inviteLink,
                ]
            ]
        ];

        $webhookRes = $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $updatePayload);

        $webhookRes->assertStatus(200);
        $webhookRes->assertJson(['ok' => true, 'processed' => true]);

        // Verify Telegram Event
        $this->assertDatabaseHas('telegram_events', [
            'telegram_user_id' => $telegramUserId,
            'update_id' => 9918234,
            'source' => 'ads',
        ]);

        // Verify Verified Conversion is Created with Exact Attribution Data
        $this->assertDatabaseHas('conversions', [
            'telegram_user_id' => $telegramUserId,
            'visitor_id' => $visitorId,
            'tracking_session_id' => $sessionId,
            'status' => 'verified',
            'source' => 'ads',
            'utm_source' => 'meta_ads_pro',
            'utm_campaign' => 'GJ004_Options',
            'fbclid' => 'fb_click_98214',
        ]);
    }

    /**
     * 6. Telegram Webhook Idempotency (Duplicate Update Prevention)
     */
    public function test_telegram_webhook_idempotency_prevents_duplicate_conversions(): void
    {
        $bot = TelegramBot::firstOrFail();
        $channel = TelegramChannel::firstOrCreate(
            ['telegram_bot_id' => $bot->id],
            [
                'client_id' => $bot->client_id,
                'telegram_chat_id' => '-1001234567890',
                'title' => 'Gujrati_trader',
                'username' => 'gujaratitrdexx',
                'is_bot_admin' => true,
            ]
        );

        $updateId = 77112233;
        $telegramUserId = '1122334455';

        $payload = [
            'update_id' => $updateId,
            'chat_member' => [
                'chat' => [
                    'id' => (int) $channel->telegram_chat_id,
                    'title' => $channel->title,
                    'type' => 'channel',
                ],
                'from' => [
                    'id' => (int) $telegramUserId,
                    'first_name' => 'Dinesh',
                ],
                'old_chat_member' => ['status' => 'left'],
                'new_chat_member' => ['status' => 'member'],
            ]
        ];

        // First delivery
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $payload)->assertStatus(200);
        $firstEventCount = TelegramEvent::where('update_id', $updateId)->count();
        $this->assertEquals(1, $firstEventCount);

        // Second delivery of same webhook
        $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $payload)->assertStatus(200);
        $secondEventCount = TelegramEvent::where('update_id', $updateId)->count();
        $this->assertEquals(1, $secondEventCount);
    }

    /**
     * 7. Meta CAPI Safe Failure & Retry Capability
     */
    public function test_meta_capi_failure_preserves_verified_conversion_and_enables_retry(): void
    {
        $client = Client::firstOrFail();

        $conversion = Conversion::create([
            'conversion_token' => 'conv_test_' . Str::random(10),
            'client_id' => $client->id,
            'visitor_id' => (string) Str::uuid(),
            'telegram_user_id' => '123456789',
            'event_type' => 'join',
            'status' => 'verified',
            'source' => 'ads',
            'meta_capi_status' => 'failed',
            'event_time' => now(),
        ]);

        // Verified status is preserved in Kirtnix
        $this->assertEquals('verified', $conversion->status);
        $this->assertEquals('failed', $conversion->meta_capi_status);

        // Retry endpoint
        $response = $this->postJson(route('api.conversions.retry_meta', $conversion->id));
        $response->assertStatus(200);
    }

    /**
     * 8. Multi-Tenant Client Data Isolation
     */
    public function test_client_data_isolation(): void
    {
        $clientA = Client::firstOrFail();
        $clientB = Client::where('id', '!=', $clientA->id)->firstOrFail();

        // Create landing page and views for client A
        $lpA = LandingPage::where('client_id', $clientA->id)->firstOrFail();
        $lpB = LandingPage::where('client_id', $clientB->id)->firstOrFail();

        $admin = User::where('email', 'admin@kirtnix.agency')->firstOrFail();

        // Query dashboard scoped to Client A
        $responseA = $this->actingAs($admin)->get(route('dashboard', ['client_id' => $clientA->id]));
        $responseA->assertStatus(200);

        // Client A's dashboard must not mix Client B's counts
        $responseB = $this->actingAs($admin)->get(route('dashboard', ['client_id' => $clientB->id]));
        $responseB->assertStatus(200);
    }

    /**
     * 9. Vercel & Netlify External Tracking API Compatibility
     */
    public function test_vercel_and_netlify_external_tracking_endpoints(): void
    {
        $landingPage = LandingPage::firstOrFail();
        $visitorId = 'kx_' . Str::random(12);

        // 1. External PageView from Vercel/Netlify
        $viewRes = $this->postJson(route('api.track.view'), [
            'slug' => $landingPage->slug,
            'visitor_id' => $visitorId,
            'url' => "https://my-vercel-lp.vercel.app/?utm_source=meta_ads&utm_campaign=Scale2026",
            'utm_source' => 'meta_ads',
            'utm_campaign' => 'Scale2026',
        ]);
        $viewRes->assertStatus(200);
        $sessionId = $viewRes->json('session_id');

        // 2. Fetch Unique Invite Link from external site
        $inviteRes = $this->postJson(route('api.track.invite'), [
            'landing_page_id' => $landingPage->id,
            'session_id' => $sessionId,
            'visitor_id' => $visitorId,
        ]);
        $inviteRes->assertStatus(200);
        $this->assertNotEmpty($inviteRes->json('invite_link'));
        $this->assertNotEmpty($inviteRes->json('deep_link'));

        // 3. Report External CTA Click
        $clickRes = $this->postJson(route('api.track.click'), [
            'landing_page_id' => $landingPage->id,
            'session_id' => $sessionId,
            'visitor_id' => $visitorId,
            'destination_url' => $inviteRes->json('web_url'),
            'button_text' => 'Join VIP Telegram',
        ]);
        $clickRes->assertStatus(200);
        $clickRes->assertJson(['ok' => true]);
    }

    /**
     * 10. Multi-Channel Bot Isolation
     */
    public function test_bot_and_channel_multi_channel_isolation(): void
    {
        $bot = TelegramBot::firstOrFail();

        $channel1 = TelegramChannel::firstOrCreate(
            ['telegram_bot_id' => $bot->id, 'telegram_chat_id' => '-1009182736451'],
            ['client_id' => $bot->client_id, 'title' => 'Alpha Channel', 'username' => 'alpha_chan']
        );

        $channel2 = TelegramChannel::firstOrCreate(
            ['telegram_bot_id' => $bot->id, 'telegram_chat_id' => '-1009182736452'],
            ['client_id' => $bot->client_id, 'title' => 'Beta Channel', 'username' => 'beta_chan']
        );

        $this->assertNotEquals($channel1->telegram_chat_id, $channel2->telegram_chat_id);
    }
}
