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
use App\Models\MetaBusiness;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\TelegramEvent;
use App\Models\TelegramInvite;
use App\Models\TrackingSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientDeletionAndPerformanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_delete_client_with_large_volume_of_associated_tracking_data(): void
    {
        $conn = \App\Models\MetaConnection::create([
            'access_token' => 'EAAB_test_token',
            'status' => 'active',
        ]);

        $business = MetaBusiness::create([
            'meta_connection_id' => $conn->id,
            'business_id' => 'biz_test_999',
            'name' => 'Performance Test Agency',
        ]);

        $adAccount = AdAccount::create([
            'meta_business_id' => $business->id,
            'account_id' => 'act_test_del_123',
            'name' => 'Test Deletion Ad Account',
            'status' => 'active',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'lifetime_spend' => 5000.00,
        ]);

        $client = Client::create([
            'company_name' => 'Haji Salman Memon',
            'client_name' => 'Salman',
            'kx_code' => 'KX-035',
            'industry' => 'Stock Market',
            'category' => 'Stock Market & Options Trading',
            'ad_account_id' => $adAccount->id,
        ]);

        $adAccount->update(['client_id' => $client->id]);

        $landingPage = LandingPage::create([
            'client_id' => $client->id,
            'title' => 'Salman Wealth Landing Page',
            'slug' => 'salman-wealth',
            'is_published' => true,
        ]);

        $cta = Cta::create([
            'client_id' => $client->id,
            'landing_page_id' => $landingPage->id,
            'label' => 'Join VIP Channel',
            'telegram_destination' => 'https://t.me/salmantrading',
        ]);

        $campaign = Campaign::create([
            'client_id' => $client->id,
            'ad_account_id' => $adAccount->id,
            'name' => 'Salman Scaling Campaign',
            'slug' => 'salman-scaling-campaign',
            'status' => 'ACTIVE',
            'spend' => 2500.00,
            'reach' => 15000,
            'impressions' => 20000,
        ]);

        $bot = TelegramBot::create([
            'client_id' => $client->id,
            'name' => 'Salman Bot',
            'username' => 'salman_bot',
            'bot_token' => '123456:salman_secret_token',
            'webhook_secret' => 'salman_secret',
        ]);

        $channel = TelegramChannel::create([
            'client_id' => $client->id,
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-100987654321',
            'title' => 'Salman Premium Calls',
        ]);

        // Create tracking records
        for ($i = 1; $i <= 5; $i++) {
            $session = TrackingSession::create([
                'session_id' => "sess_{$i}",
                'visitor_id' => "vis_{$i}",
                'client_id' => $client->id,
                'landing_page_id' => $landingPage->id,
                'campaign_id' => $campaign->id,
            ]);

            LandingPageView::create([
                'tracking_session_id' => $session->id,
                'landing_page_id' => $landingPage->id,
                'client_id' => $client->id,
                'campaign_id' => $campaign->id,
                'visitor_id' => "vis_{$i}",
                'is_unique' => true,
            ]);

            $click = CtaClick::create([
                'tracking_session_id' => $session->id,
                'cta_id' => $cta->id,
                'landing_page_id' => $landingPage->id,
                'client_id' => $client->id,
                'campaign_id' => $campaign->id,
                'tracking_token' => "tok_{$i}",
                'visitor_id' => "vis_{$i}",
                'destination_url' => 'https://t.me/salmantrading',
            ]);

            $invite = TelegramInvite::create([
                'invite_link' => "https://t.me/+inv_{$i}",
                'tracking_session_id' => $session->id,
                'landing_page_id' => $landingPage->id,
                'client_id' => $client->id,
                'telegram_bot_id' => $bot->id,
                'telegram_channel_id' => $channel->id,
                'visitor_id' => "vis_{$i}",
                'status' => 'used',
            ]);

            $event = TelegramEvent::create([
                'client_id' => $client->id,
                'telegram_bot_id' => $bot->id,
                'telegram_channel_id' => $channel->id,
                'campaign_id' => $campaign->id,
                'cta_click_id' => $click->id,
                'telegram_user_id' => "tg_usr_{$i}",
                'telegram_username' => "trader_{$i}",
                'invite_link' => "https://t.me/+inv_{$i}",
                'event_type' => 'join',
                'raw_payload' => ['update_id' => $i],
                'event_time' => now(),
            ]);

            Conversion::create([
                'conversion_token' => "conv_{$i}",
                'client_id' => $client->id,
                'landing_page_id' => $landingPage->id,
                'campaign_id' => $campaign->id,
                'telegram_bot_id' => $bot->id,
                'telegram_channel_id' => $channel->id,
                'telegram_event_id' => $event->id,
                'tracking_session_id' => $session->id,
                'cta_click_id' => $click->id,
                'visitor_id' => "vis_{$i}",
                'telegram_user_id' => "tg_usr_{$i}",
                'event_type' => 'join',
                'status' => 'verified',
                'event_time' => now(),
            ]);
        }

        // Another client to ensure isolation
        $otherClient = Client::create([
            'company_name' => 'Gujarati Trader',
            'client_name' => 'Mayank',
            'kx_code' => 'KX-002',
            'industry' => 'Stock Market',
        ]);

        $response = $this->actingAs($this->user)->delete(route('clients.destroy', $client));

        $response->assertRedirect(route('clients.index'));
        $response->assertSessionHas('success');

        // Verify client Haji Salman Memon is gone
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);
        $this->assertDatabaseMissing('landing_pages', ['client_id' => $client->id]);
        $this->assertDatabaseMissing('landing_page_views', ['client_id' => $client->id]);
        $this->assertDatabaseMissing('cta_clicks', ['client_id' => $client->id]);
        $this->assertDatabaseMissing('telegram_events', ['client_id' => $client->id]);
        $this->assertDatabaseMissing('conversions', ['client_id' => $client->id]);
        $this->assertDatabaseMissing('campaigns', ['client_id' => $client->id]);

        // Verify ad account was unassigned and not deleted
        $this->assertDatabaseHas('ad_accounts', [
            'id' => $adAccount->id,
            'client_id' => null,
        ]);

        // Verify other client is intact
        $this->assertDatabaseHas('clients', ['id' => $otherClient->id]);
    }

    public function test_clients_index_page_loads_with_performance_counts(): void
    {
        $client = Client::create([
            'company_name' => 'Kirtnix Official',
            'client_name' => 'Anurag',
            'kx_code' => 'KX-001',
            'industry' => 'Digital Marketing',
            'category' => 'Digital Marketing & Lead Gen',
        ]);

        $response = $this->actingAs($this->user)->get(route('clients.index'));

        $response->assertOk();
        $response->assertSee('Kirtnix Official');
        $response->assertSee('KX-001');
    }
}
