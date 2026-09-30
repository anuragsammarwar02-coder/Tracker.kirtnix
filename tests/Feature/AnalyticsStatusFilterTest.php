<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LandingPage;
use App\Models\TelegramEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsStatusFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_filter_analytics_detail_by_approved_pending_and_left_status()
    {
        $client = Client::create([
            'company_name' => 'Gujarat Traders',
            'client_name' => 'Nandu Meena',
            'kx_code' => 'KX-GUJ01',
            'currency_symbol' => '₹',
        ]);

        $landingPage = LandingPage::create([
            'client_id' => $client->id,
            'title' => 'Gujarat Traders Community',
            'slug' => 'mynkgujarati',
            'is_published' => true,
        ]);

        // Event 1: Approved member
        TelegramEvent::create([
            'client_id' => $client->id,
            'telegram_user_id' => '1001',
            'telegram_username' => 'approved_user',
            'first_name' => 'Approved',
            'event_type' => 'join',
            'status_after' => 'member',
            'source' => 'ads',
            'event_time' => now(),
        ]);

        // Event 2: Pending request
        TelegramEvent::create([
            'client_id' => $client->id,
            'telegram_user_id' => '1002',
            'telegram_username' => 'pending_user',
            'first_name' => 'Pending',
            'event_type' => 'join_request',
            'status_after' => 'pending',
            'source' => 'direct',
            'event_time' => now()->subMinute(),
        ]);

        // Event 3: Left member
        TelegramEvent::create([
            'client_id' => $client->id,
            'telegram_user_id' => '1003',
            'telegram_username' => 'left_user',
            'first_name' => 'Left',
            'event_type' => 'leave',
            'status_after' => 'left',
            'source' => 'direct',
            'event_time' => now()->subMinutes(2),
        ]);

        // 1. Filter: Approved
        $responseApproved = $this->get('/analytics/detail/mynkgujarati?status=approved');
        $responseApproved->assertStatus(200);
        $responseApproved->assertSee('@approved_user');
        $responseApproved->assertDontSee('@pending_user');
        $responseApproved->assertDontSee('@left_user');

        // 2. Filter: Pending
        $responsePending = $this->get('/analytics/detail/mynkgujarati?status=pending');
        $responsePending->assertStatus(200);
        $responsePending->assertSee('@pending_user');
        $responsePending->assertDontSee('@approved_user');
        $responsePending->assertDontSee('@left_user');

        // 3. Filter: Left
        $responseLeft = $this->get('/analytics/detail/mynkgujarati?status=left');
        $responseLeft->assertStatus(200);
        $responseLeft->assertSee('@left_user');
        $responseLeft->assertDontSee('@approved_user');
        $responseLeft->assertDontSee('@pending_user');

        // 4. Filter: Paid Ads
        $responsePaidAds = $this->get('/analytics/detail/mynkgujarati?status=paid_ads');
        $responsePaidAds->assertStatus(200);
        $responsePaidAds->assertSee('@approved_user');
        $responsePaidAds->assertDontSee('@pending_user');
        $responsePaidAds->assertDontSee('@left_user');

        // 5. No filter: All events
        $responseAll = $this->get('/analytics/detail/mynkgujarati');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('@approved_user');
        $responseAll->assertSee('@pending_user');
        $responseAll->assertSee('@left_user');

        // 6. Live Metrics API with status filter
        $liveApproved = $this->getJson('/analytics/detail/mynkgujarati/live-metrics?status=approved');
        $liveApproved->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonPath('total_events', 1)
            ->assertJsonPath('events.0.username', 'approved_user');

        $livePending = $this->getJson('/analytics/detail/mynkgujarati/live-metrics?status=pending');
        $livePending->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonPath('total_events', 1)
            ->assertJsonPath('events.0.username', 'pending_user');

        $liveLeft = $this->getJson('/analytics/detail/mynkgujarati/live-metrics?status=left');
        $liveLeft->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonPath('total_events', 1)
            ->assertJsonPath('events.0.username', 'left_user');

        $livePaidAds = $this->getJson('/analytics/detail/mynkgujarati/live-metrics?status=paid_ads');
        $livePaidAds->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonPath('total_events', 1)
            ->assertJsonPath('events.0.username', 'approved_user');
    }

    public function test_all_subscribers_and_join_history_events_render_without_suppression()
    {
        $client = Client::create([
            'company_name' => 'Haji Salman Memon',
            'client_name' => 'Haji Salman',
            'kx_code' => 'KX-HSM01',
            'currency_symbol' => '₹',
        ]);

        $landingPage = LandingPage::create([
            'client_id' => $client->id,
            'title' => 'Haji Salman Official Channel',
            'slug' => 'hajisalmem',
            'is_published' => true,
        ]);

        // Create 7 confirmed subscribers
        for ($i = 1; $i <= 7; $i++) {
            TelegramEvent::create([
                'client_id' => $client->id,
                'telegram_user_id' => (string) (70000 + $i),
                'telegram_username' => 'subscriber_' . $i,
                'first_name' => 'Subscriber ' . $i,
                'event_type' => 'join',
                'status_after' => 'member',
                'source' => ($i % 2 === 0) ? 'ads' : 'direct',
                'event_time' => now()->subMinutes(10 - $i),
            ]);
        }

        // 1 pending request
        TelegramEvent::create([
            'client_id' => $client->id,
            'telegram_user_id' => '80001',
            'telegram_username' => 'pending_request_user',
            'first_name' => 'Pending Person',
            'event_type' => 'join_request',
            'status_after' => 'pending',
            'source' => 'ads',
            'event_time' => now()->subMinutes(1),
        ]);

        // 1 channel leave
        TelegramEvent::create([
            'client_id' => $client->id,
            'telegram_user_id' => '90001',
            'telegram_username' => 'leave_person',
            'first_name' => 'Leave Person',
            'event_type' => 'leave',
            'status_after' => 'left',
            'source' => 'direct',
            'event_time' => now(),
        ]);

        // Detail page query
        $response = $this->get('/analytics/detail/hajisalmem');
        $response->assertStatus(200);

        // Check that KPIs match
        $response->assertSee('7'); // 7 Subscribers
        $response->assertSee('9 events'); // All 9 events displayed in counter

        // Check that all 7 subscribers + 1 pending + 1 leave are present in HTML
        for ($i = 1; $i <= 7; $i++) {
            $response->assertSee('@subscriber_' . $i);
        }
        $response->assertSee('@pending_request_user');
        $response->assertSee('@leave_person');

        // Check live-metrics JSON endpoint
        $liveResponse = $this->getJson('/analytics/detail/hajisalmem/live-metrics');
        $liveResponse->assertStatus(200)
            ->assertJson(['ok' => true])
            ->assertJsonPath('total_events', 9)
            ->assertJsonPath('kpis.subscribers', '8')
            ->assertJsonPath('kpis.pending_requests', '1')
            ->assertJsonPath('kpis.backouts', '1');
    }
}
