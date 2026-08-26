<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Campaign;
use App\Models\LandingPage;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\Notification;
use App\Models\LoginRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SaaSExtendedTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected LandingPage $landingPage;
    protected TelegramBot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'admin@kirtnix.agency',
            'role' => 'admin',
        ]);

        $this->client = Client::create([
            'kx_code' => 'KX-001',
            'company_name' => 'STOXK Academy',
            'client_name' => 'Nandu Meena',
            'industry' => 'Stock Market',
            'status' => 'active',
            'meta_ads_connected' => true,
            'monthly_budget' => 4500.00,
        ]);

        $this->landingPage = LandingPage::create([
            'client_id' => $this->client->id,
            'title' => 'gujaratitrdexx',
            'slug' => 'gujaratitrdexx',
            'is_published' => true,
            'is_active' => true,
        ]);

        $this->bot = TelegramBot::create([
            'client_id' => $this->client->id,
            'name' => 'KirtniX TG Tracker Bot',
            'username' => 'kirtnixtgtracker_bot',
            'bot_token' => '8956518773:AAH9_test_token',
            'channel_id' => '-1001234567890',
            'channel_title' => 'Gujrati_trader',
            'webhook_secret' => 'whsec_test_secret_123',
            'is_webhook_active' => true,
        ]);
    }

    public function test_analytics_dashboard_renders_with_funnel_and_charts()
    {
        $response = $this->actingAs($this->user)->get(route('analytics.index'));
        $response->assertStatus(200);
        $response->assertSee('Analytics');
        $response->assertSee('Performance Overview');
        $response->assertSee('Conversion Funnel');
        $response->assertSee('Campaign Performance');
        $response->assertSee('Landing Page Performance');
        $response->assertSee('Telegram Performance');
        $response->assertSee('KirtniX AI Insights');
    }

    public function test_analytics_detail_page_renders_with_budget_and_meta_ad_account()
    {
        $response = $this->actingAs($this->user)->get(route('analytics.detail', 'gujaratitrdexx'));
        $response->assertStatus(200);
        $response->assertSee('gujaratitrdexx');
        $response->assertSee('Budget Overview');
        $response->assertSee('Total Spending');
        $response->assertSee('Total Budget');
        $response->assertSee('Remaining Budget');
        $response->assertSee('Ad Account (Live from Meta)');
        $response->assertSee('Campaign Objectives (Live from Meta)');
        $response->assertSee('Performance Metrics');
        $response->assertSee('Complete Join History');
    }

    public function test_telegram_tracking_renders_with_bots_and_channels()
    {
        $response = $this->actingAs($this->user)->get(route('telegram.index'));
        $response->assertStatus(200);
        $response->assertSee('Telegram tracking');
        $response->assertSee('kirtnixtgtracker_bot');
        $response->assertSee('Tracked channels');
        $response->assertSee('Live Member Feed');
    }

    public function test_telegram_auto_detect_channels_endpoint()
    {
        $response = $this->actingAs($this->user)->getJson(route('telegram.channels.auto_detect', ['bot_id' => $this->bot->id]));
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'bot_username',
            'channels',
        ]);
        $this->assertTrue(count($response->json('channels')) > 0);
    }

    public function test_meta_connect_and_sync_endpoints()
    {
        $connectRes = $this->actingAs($this->user)->post(route('meta.connect'), [
            'access_token' => 'EAABsbCS_sample_token',
        ]);
        $connectRes->assertRedirect(route('settings.index', ['tab' => 'meta']));

        $conn = \App\Models\MetaConnection::first();
        \App\Models\AdAccount::create([
            'meta_connection_id' => $conn->id,
            'account_id' => 'act_real_12345',
            'name' => 'Real Ad Account',
            'currency' => 'INR',
            'status' => 'Active',
            'is_active' => true,
        ]);

        $syncRes = $this->actingAs($this->user)->postJson(route('meta.sync'));
        $syncRes->assertStatus(200);
        $syncRes->assertJsonStructure(['success', 'message', 'accounts_count']);
        $this->assertTrue($syncRes->json('accounts_count') >= 1);
    }

    public function test_access_management_matrix_renders_and_invites_member()
    {
        $response = $this->actingAs($this->user)->get(route('access.index'));
        $response->assertStatus(200);
        $response->assertSee('Role Permission Matrix');
        $response->assertSee('Active Team Members');

        $inviteRes = $this->actingAs($this->user)->post(route('access.storeMember'), [
            'name' => 'Vikas Patel',
            'email' => 'vikas@agency.com',
            'role' => 'manager',
        ]);

        $inviteRes->assertRedirect(route('access.index'));
        $this->assertDatabaseHas('users', ['email' => 'vikas@agency.com', 'role' => 'manager']);
    }

    public function test_conversion_logs_renders_counters_and_retries_queue()
    {
        $response = $this->actingAs($this->user)->get(route('conversion_logs.index'));
        $response->assertStatus(200);
        $response->assertSee('Meta Delivery Log');
        $response->assertSee('Delivered');
        $response->assertSee('Sent');

        $retryRes = $this->actingAs($this->user)->post(route('conversion_logs.retry'));
        $retryRes->assertRedirect(route('conversion_logs.index'));
    }

    public function test_reports_page_and_ai_generation()
    {
        $response = $this->actingAs($this->user)->get(route('reports.index'));
        $response->assertStatus(200);
        $response->assertSee('Reports');
        $response->assertSee('Generate AI Report');

        $genRes = $this->actingAs($this->user)->post(route('reports.generate_ai'), [
            'client_id' => $this->client->id,
            'date_range' => 'Last 7 Days',
        ]);

        $genRes->assertRedirect(route('reports.index', ['client_id' => $this->client->id]));
        $this->assertDatabaseHas('reports', ['client_id' => $this->client->id]);
    }

    public function test_notifications_center_and_mark_read()
    {
        $notif = Notification::create([
            'type' => 'tracking_issue',
            'severity' => 'warning',
            'title' => 'Test Notification',
            'message' => 'This is a test notification message.',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)->get(route('notifications.index'));
        $response->assertStatus(200);
        $response->assertSee('Test Notification');

        $markRes = $this->actingAs($this->user)->post(route('notifications.mark_read', $notif));
        $markRes->assertRedirect();
        $this->assertTrue($notif->fresh()->is_read);
    }

    public function test_ai_copilot_chat_endpoint_answers_questions()
    {
        $response = $this->actingAs($this->user)->postJson(route('ai.chat'), [
            'message' => 'Which client performed best this week?',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply', 'timestamp']);
        $this->assertStringContainsString('Best Performing Client', $response->json('reply'));
    }

    public function test_global_search_api_returns_json_results()
    {
        $response = $this->actingAs($this->user)->getJson(route('api.search', ['q' => 'STOXK']));
        $response->assertStatus(200);
        $response->assertJsonStructure(['results']);
        $this->assertTrue(count($response->json('results')) > 0);
    }

    public function test_security_login_requests_status_update()
    {
        $loginReq = LoginRequest::create([
            'email' => 'employee@agency.com',
            'ip_address' => '103.21.244.1',
            'location' => 'Mumbai, India',
            'device' => 'Windows Chrome',
            'status' => 'pending',
            'requested_at' => now(),
        ]);

        $response = $this->actingAs($this->user)->get(route('login_requests.index'));
        $response->assertStatus(200);
        $response->assertSee('employee@agency.com');

        $updateRes = $this->actingAs($this->user)->post(route('login_requests.update_status', $loginReq), [
            'status' => 'approved',
        ]);

        $updateRes->assertRedirect(route('login_requests.index'));
        $this->assertEquals('approved', $loginReq->fresh()->status);
    }

    public function test_public_marketing_analytics_page_renders_for_guests()
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Turn your marketing data into decisions');
        $response->assertSee('KIRTNi');
        $response->assertSee('Conversion Funnel');
    }
}
