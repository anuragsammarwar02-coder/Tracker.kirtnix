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

        // 4. No filter: All events
        $responseAll = $this->get('/analytics/detail/mynkgujarati');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('@approved_user');
        $responseAll->assertSee('@pending_user');
        $responseAll->assertSee('@left_user');

        // 5. Live Metrics API with status filter
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
    }
}
