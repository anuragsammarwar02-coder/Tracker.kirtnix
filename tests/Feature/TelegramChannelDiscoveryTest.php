<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\TelegramEvent;
use App\Services\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramChannelDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_can_discover_three_or_more_channels_without_limits(): void
    {
        $bot = TelegramBot::first();
        $this->assertNotNull($bot);

        // Create 3 existing channels for this bot with real -100 numeric IDs
        TelegramChannel::truncate();

        TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1001234567890',
            'title' => 'Channel 1 (Gujarati Trader)',
            'username' => 'gujaratitrdexx',
            'type' => 'channel',
            'member_count' => 13587,
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1002194829104',
            'title' => 'Channel 2 (STOXK VIP Option)',
            'username' => 'stoxk_official',
            'type' => 'channel',
            'member_count' => 54200,
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1009876543210',
            'title' => 'Channel 3 (Forex Global Alpha)',
            'username' => 'forex_global_alpha',
            'type' => 'channel',
            'member_count' => 8950,
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1005554443332',
            'title' => 'Channel 4 (Private Crypto Room)',
            'username' => null, // Private channel
            'type' => 'channel',
            'member_count' => 3200,
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        $telegramService = app(TelegramService::class);
        $discovered = $telegramService->discoverAccessibleChannels($bot);

        // Verify that all 4 channels are returned (no limit of 2)
        $this->assertCount(4, $discovered);
        
        $chatIds = array_column($discovered, 'telegram_chat_id');
        $this->assertContains('-1001234567890', $chatIds);
        $this->assertContains('-1002194829104', $chatIds);
        $this->assertContains('-1009876543210', $chatIds);
        $this->assertContains('-1005554443332', $chatIds);

        // Test the JSON API endpoint
        $user = \App\Models\User::first();
        $response = $this->actingAs($user)->getJson(route('telegram.channels.auto_detect', ['bot_id' => $bot->id]));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'bot_username' => $bot->username,
        ]);
        $this->assertCount(4, $response->json('channels'));
    }

    public function test_channel_post_webhook_update_automatically_discovers_and_persists_third_channel(): void
    {
        $bot = TelegramBot::first();
        $this->assertNotNull($bot);

        // Ensure the third channel does not exist yet
        $thirdChatId = '-1003334445556';
        $this->assertDatabaseMissing('telegram_channels', [
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => $thirdChatId,
        ]);

        // Post a webhook update of type channel_post from the third channel
        $webhookPayload = [
            'update_id' => 99887766,
            'channel_post' => [
                'message_id' => 101,
                'chat' => [
                    'id' => -1003334445556,
                    'title' => 'Third Real Telegram Channel',
                    'username' => 'third_channel_real',
                    'type' => 'channel',
                ],
                'date' => time(),
                'text' => 'Hello from third Telegram channel where bot is admin!',
            ],
        ];

        $response = $this->postJson("/api/telegram/webhook/{$bot->webhook_secret}", $webhookPayload);
        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'processed' => true]);

        // Verify the third channel is now persisted in telegram_channels
        $this->assertDatabaseHas('telegram_channels', [
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1003334445556',
            'title' => 'Third Real Telegram Channel',
            'username' => 'third_channel_real',
            'is_bot_admin' => 1,
            'bot_status' => 'administrator',
        ]);

        // Verify discovery endpoint now includes this third channel
        $telegramService = app(TelegramService::class);
        $discovered = $telegramService->discoverAccessibleChannels($bot);
        $chatIds = array_column($discovered, 'telegram_chat_id');
        $this->assertContains('-1003334445556', $chatIds);
    }

    public function test_private_channel_without_username_is_supported_and_persisted(): void
    {
        $bot = TelegramBot::first();
        $privateChatId = '-1007778889990';

        $webhookPayload = [
            'update_id' => 99887767,
            'channel_post' => [
                'message_id' => 102,
                'chat' => [
                    'id' => -1007778889990,
                    'title' => 'Private Exclusive VIP Calls',
                    'type' => 'channel',
                    // No username provided for private channel
                ],
                'date' => time(),
                'text' => 'Exclusive post in private channel',
            ],
        ];

        $response = $this->postJson("/api/telegram/webhook/{$bot->webhook_secret}", $webhookPayload);
        $response->assertStatus(200);

        $this->assertDatabaseHas('telegram_channels', [
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => $privateChatId,
            'title' => 'Private Exclusive VIP Calls',
            'username' => null,
        ]);
    }

    public function test_webhook_preserves_existing_client_assignment_on_subsequent_updates(): void
    {
        $bot = TelegramBot::first();
        $client = Client::first();

        // Create channel assigned to a client
        $channel = TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'client_id' => $client->id,
            'telegram_chat_id' => '-1008889990001',
            'title' => 'Assigned Client Channel',
            'username' => 'assigned_channel',
            'type' => 'channel',
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        // Send an update for this channel
        $webhookPayload = [
            'update_id' => 99887768,
            'channel_post' => [
                'message_id' => 103,
                'chat' => [
                    'id' => -1008889990001,
                    'title' => 'Assigned Client Channel (Renamed)',
                    'username' => 'assigned_channel',
                    'type' => 'channel',
                ],
                'date' => time(),
                'text' => 'New update',
            ],
        ];

        $this->postJson("/api/telegram/webhook/{$bot->webhook_secret}", $webhookPayload);

        // Verify title updated but client_id remained intact!
        $this->assertDatabaseHas('telegram_channels', [
            'id' => $channel->id,
            'client_id' => $client->id,
            'title' => 'Assigned Client Channel (Renamed)',
        ]);
    }

    public function test_auto_detect_returns_all_three_channels_and_renders_in_ui(): void
    {
        $bot = TelegramBot::first();
        TelegramChannel::truncate();

        $c1 = TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1001234567890',
            'title' => 'Gujarati_trader',
            'username' => 'gujaratitrdexx',
            'type' => 'channel',
            'member_count' => 15800,
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        $c2 = TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1001984729104',
            'title' => 'STOX Pro VIP Calls',
            'username' => 'stoxk_pro_vip',
            'type' => 'channel',
            'member_count' => 16550,
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        $c3 = TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1002938475610',
            'title' => 'Alpha Forex VIP Club (Private)',
            'username' => null, // Private channel without username
            'type' => 'channel',
            'member_count' => 17300,
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        $user = \App\Models\User::first();

        // 1. Verify Auto-Detect JSON API
        $response = $this->actingAs($user)->getJson(route('telegram.channels.auto_detect', ['bot_id' => $bot->id]));
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'bot_username' => $bot->username,
        ]);

        $channels = $response->json('channels');
        $this->assertCount(3, $channels);
        $this->assertEquals('Gujarati_trader', $channels[0]['title']);
        $this->assertEquals('STOX Pro VIP Calls', $channels[1]['title']);
        $this->assertEquals('Alpha Forex VIP Club (Private)', $channels[2]['title']);
        $this->assertNull($channels[2]['username']);

        // 2. Verify View rendering
        $viewResponse = $this->actingAs($user)->get(route('telegram.index'));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Auto-detect private channel');
        $viewResponse->assertSee('Step 2: Discovered Telegram Channels');
        $viewResponse->assertSee('Gujarati_trader');
        $viewResponse->assertSee('STOX Pro VIP Calls');
        $viewResponse->assertSee('Alpha Forex VIP Club (Private)');
    }

    public function test_client_assigned_channel_redirects_dynamically_and_syncs_landing_page(): void
    {
        // 1. Create Bot 2 (e.g. @Akgrowthxtracker_bot)
        $bot2 = TelegramBot::create([
            'name' => 'Ak GrowthX TG Tracker',
            'bot_id' => '8788203640',
            'first_name' => 'Ak GrowthX TG Tracker',
            'username' => 'Akgrowthxtracker_bot',
            'bot_token' => '8788203640:AAFakeTokenForTestingOnly_AkGrowthX',
            'webhook_secret' => 'whsec_testing123456',
            'is_global' => true,
            'is_active' => true,
        ]);

        // 2. Create Client KX-005 (Haji Salman Memon)
        $client = Client::create([
            'company_name' => 'Haji Salman Memon',
            'client_name' => 'Haji Salman Memon',
            'kx_code' => 'KX-005',
            'status' => 'active',
        ]);

        // 3. Create Private Channel for Salman Memon under Bot 2
        $channel = TelegramChannel::create([
            'telegram_bot_id' => $bot2->id,
            'telegram_chat_id' => '-1003814083914',
            'title' => 'Haji Salman Memon India (Halal work)™',
            'username' => null, // Private channel
            'type' => 'channel',
            'member_count' => 4,
            'is_bot_admin' => true,
            'bot_status' => 'administrator',
            'is_active' => true,
        ]);

        // 4. Create Landing Page that originally had stale old URL (e.g. Gujarati trader)
        $landingPage = \App\Models\LandingPage::create([
            'client_id' => $client->id,
            'title' => 'SALMAN MEMON (OFFICIAL)',
            'slug' => 'hajisalm',
            'brand_name' => 'SALMAN MEMON (OFFICIAL)',
            'template_type' => 'forex_focus',
            'telegram_destination' => 'https://t.me/+I97PKY2br640MmZl', // Old stale link
            'primary_cta_text' => 'Join Free Telegram Channel',
            'tracking_token' => 'kx_salman_track_1',
            'is_active' => true,
        ]);

        $cta = \App\Models\Cta::create([
            'landing_page_id' => $landingPage->id,
            'client_id' => $client->id,
            'name' => 'Primary Hero CTA',
            'button_text' => 'Join Free Telegram Channel',
            'button_type' => 'primary',
            'tracking_token' => 'kx_salm_cta_1',
            'telegram_destination' => 'https://t.me/+I97PKY2br640MmZl', // Old stale link
            'is_active' => true,
        ]);

        // Mock Telegram Bot API createChatInviteLink response for Salman Memon channel
        \Illuminate\Support\Facades\Http::fake([
            'https://api.telegram.org/bot8788203640:AAFakeTokenForTestingOnly_AkGrowthX/createChatInviteLink*' => \Illuminate\Support\Facades\Http::response([
                'ok' => true,
                'result' => [
                    'invite_link' => 'https://t.me/+SalmanMemonPrivate123',
                    'name' => 'kx_client_' . $client->id,
                    'creates_join_request' => true,
                ],
            ], 200),
        ]);

        $user = \App\Models\User::first();

        // 5. Assign Channel to Client via API
        $assignResponse = $this->actingAs($user)->post(route('telegram.channels.assign_client', $channel), [
            'client_id' => $client->id,
        ]);
        $assignResponse->assertRedirect();

        // Verify channel is updated with client_id
        $this->assertEquals($client->id, $channel->fresh()->client_id);

        // Verify landing page and CTA were synced to Salman Memon channel invite link
        $this->assertEquals('https://t.me/+SalmanMemonPrivate123', $landingPage->fresh()->telegram_destination);
        $this->assertEquals('https://t.me/+SalmanMemonPrivate123', $cta->fresh()->telegram_destination);

        // 6. Test CTA click redirect
        $clickResponse = $this->get(route('public.cta_redirect', ['token' => $cta->tracking_token]));
        $clickResponse->assertStatus(200);
        $clickResponse->assertViewIs('tracking.redirect');
        $clickResponse->assertViewHas('deepLink', 'tg://join?invite=SalmanMemonPrivate123');
        $clickResponse->assertViewHas('webUrl', 'https://t.me/+SalmanMemonPrivate123');
    }
}
