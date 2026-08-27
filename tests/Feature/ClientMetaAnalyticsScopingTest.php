<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\LandingPage;
use App\Models\MetaBusiness;
use App\Models\MetaConnection;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\TelegramEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientMetaAnalyticsScopingTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MetaBusiness $metaBusiness;
    protected AdAccount $adAccountA;
    protected AdAccount $adAccountB;
    protected Client $clientA;
    protected Client $clientB;
    protected Client $clientC;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'admin@kirtnix.in']);

        $metaConnection = MetaConnection::create([
            'access_token' => 'test_token',
            'token_type' => 'Bearer',
            'status' => 'active',
        ]);

        $this->metaBusiness = MetaBusiness::create([
            'meta_connection_id' => $metaConnection->id,
            'business_id' => 'biz_999',
            'name' => 'Kirtnix Agency',
        ]);

        // Ad Account A (INR)
        $this->adAccountA = AdAccount::create([
            'meta_business_id' => $this->metaBusiness->id,
            'account_id' => 'act_4151051451781245',
            'name' => 'Kirtnix Official 2025',
            'currency' => 'INR',
            'status' => 'ACTIVE',
            'spend_limit' => 100000.00,
            'lifetime_spend' => 24500.00,
            'timezone' => 'Asia/Kolkata',
        ]);

        // Ad Account B (USD)
        $this->adAccountB = AdAccount::create([
            'meta_business_id' => $this->metaBusiness->id,
            'account_id' => 'act_9988776655443322',
            'name' => 'US Global Forex',
            'currency' => 'USD',
            'status' => 'ACTIVE',
            'spend_limit' => 5000.00,
            'lifetime_spend' => 1200.00,
            'timezone' => 'America/New_York',
        ]);

        // Client A assigned to Ad Account A
        $this->clientA = Client::create([
            'kx_code' => 'KX-001',
            'company_name' => 'Anurag Forex',
            'client_name' => 'Anurag',
            'status' => 'active',
            'ad_account_id' => $this->adAccountA->id,
        ]);
        $this->adAccountA->update(['client_id' => $this->clientA->id]);

        // Client B assigned to Ad Account B
        $this->clientB = Client::create([
            'kx_code' => 'KX-002',
            'company_name' => 'Global Forex Corp',
            'client_name' => 'Michael',
            'status' => 'active',
            'ad_account_id' => $this->adAccountB->id,
        ]);
        $this->adAccountB->update(['client_id' => $this->clientB->id]);

        // Client C without any Ad Account
        $this->clientC = Client::create([
            'kx_code' => 'KX-003',
            'company_name' => 'Unconnected Client',
            'client_name' => 'Sarah',
            'status' => 'active',
            'ad_account_id' => null,
        ]);
    }

    public function test_client_overview_shows_assigned_meta_ad_account_and_no_other_account(): void
    {
        // Add campaigns to Account A
        Campaign::create([
            'client_id' => $this->clientA->id,
            'ad_account_id' => $this->adAccountA->id,
            'campaign_id' => 'cmp_a1',
            'name' => 'Kirtnix Growth Campaign',
            'slug' => 'kirtnix-growth',
            'status' => 'ACTIVE',
            'spend' => 24500.00,
            'reach' => 120000,
            'impressions' => 250000,
            'clicks' => 8400,
        ]);

        // Add campaigns to Account B
        Campaign::create([
            'client_id' => $this->clientB->id,
            'ad_account_id' => $this->adAccountB->id,
            'campaign_id' => 'cmp_b1',
            'name' => 'US Forex Scaling',
            'slug' => 'us-forex',
            'status' => 'ACTIVE',
            'spend' => 1200.00,
            'reach' => 15000,
            'impressions' => 30000,
            'clicks' => 600,
        ]);

        // 1. Check Client A Overview
        $responseA = $this->actingAs($this->user)->get(route('clients.show', $this->clientA));
        $responseA->assertOk();
        $responseA->assertSee('Kirtnix Official 2025');
        $responseA->assertSee('act_4151051451781245');
        $responseA->assertSee('Ad spend (lifetime)');
        $responseA->assertSee('24,500');
        // Scoped: Client A must NOT see Client B's spend metrics
        $responseA->assertDontSee('1,200');

        // 2. Check Client B Overview
        $responseB = $this->actingAs($this->user)->get(route('clients.show', $this->clientB));
        $responseB->assertOk();
        $responseB->assertSee('US Global Forex');
        $responseB->assertSee('act_9988776655443322');
        $responseB->assertSee('1,200');
        // Scoped: Client B must NOT see Client A's spend metrics
        $responseB->assertDontSee('24,500');

        // 3. Check Client C Overview (Unassigned)
        $responseC = $this->actingAs($this->user)->get(route('clients.show', $this->clientC));
        $responseC->assertOk();
        $responseC->assertSee('Not Assigned');
        $responseC->assertSee('No Meta Ad Account assigned yet');
        // Must NOT show Client A or B spend metrics
        $responseC->assertDontSee('24,500');
        $responseC->assertDontSee('1,200');
    }

    public function test_spend_today_is_zero_when_no_spend_today_and_never_fake_multiplied(): void
    {
        Campaign::create([
            'client_id' => $this->clientA->id,
            'ad_account_id' => $this->adAccountA->id,
            'campaign_id' => 'cmp_a1',
            'name' => 'Kirtnix Campaign',
            'slug' => 'kirtnix-campaign',
            'status' => 'ACTIVE',
            'spend' => 10000.00,
            'reach' => 50000,
            'impressions' => 100000,
            'clicks' => 2000,
        ]);

        $response = $this->actingAs($this->user)->get(route('clients.show', $this->clientA));
        $response->assertOk();

        // In previous buggy version: $spendToday was round($spendMonth * 0.25, 2) = 2,500
        // Now it must strictly be 0
        $response->assertSee('₹0');
        $response->assertDontSee('₹2,500');
    }

    public function test_join_history_excludes_raw_channel_post_webhook_events(): void
    {
        $bot = TelegramBot::create([
            'client_id' => $this->clientA->id,
            'name' => 'Kirtnix Tracker Bot',
            'username' => 'kirtnix_tracker_bot',
            'bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'is_active' => true,
        ]);

        $channel = TelegramChannel::create([
            'client_id' => $this->clientA->id,
            'telegram_bot_id' => $bot->id,
            'telegram_chat_id' => '-1002233445566',
            'title' => 'Kirtnix Official Channel',
            'username' => 'kirtnixofficial',
            'is_active' => true,
        ]);

        $landingPage = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Kirtnix Digital',
            'slug' => 'kirtnix-digital',
            'is_published' => true,
        ]);

        // 1. Raw channel_post event from Telegram
        TelegramEvent::create([
            'client_id' => $this->clientA->id,
            'channel_id' => $channel->id,
            'telegram_user_id' => '0',
            'telegram_username' => 'kirtnix',
            'first_name' => 'Kirtnix Channel',
            'event_type' => 'channel_post',
            'status_after' => 'processed',
            'source' => 'channel_post',
            'event_time' => now(),
        ]);

        // 2. Real ad-driven join
        TelegramEvent::create([
            'client_id' => $this->clientA->id,
            'channel_id' => $channel->id,
            'telegram_user_id' => '77889911',
            'telegram_username' => 'rajesh_trader',
            'first_name' => 'Rajesh',
            'event_type' => 'join',
            'status_after' => 'member',
            'source' => 'ads',
            'event_time' => now()->subMinutes(5),
        ]);

        // 3. Real direct join
        TelegramEvent::create([
            'client_id' => $this->clientA->id,
            'channel_id' => $channel->id,
            'telegram_user_id' => '66554433',
            'telegram_username' => 'priya_forex',
            'first_name' => 'Priya',
            'event_type' => 'join',
            'status_after' => 'member',
            'source' => 'direct',
            'event_time' => now()->subMinutes(10),
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', $landingPage->slug));
        $response->assertOk();

        // Must see real subscribers
        $response->assertSee('Rajesh');
        $response->assertSee('Ad Join');
        $response->assertSee('Paid Ads');

        $response->assertSee('Priya');
        $response->assertSee('Direct Join');
        $response->assertSee('Direct / Organic');

        // Must NEVER see raw channel_post event as subscriber join history
        $response->assertDontSee('channel_post');
    }

    public function test_analytics_detail_page_is_strictly_isolated_between_clients(): void
    {
        $lpA = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Client A Page',
            'slug' => 'client-a-page',
            'is_published' => true,
        ]);

        $lpB = LandingPage::create([
            'client_id' => $this->clientB->id,
            'title' => 'Client B Page',
            'slug' => 'client-b-page',
            'is_published' => true,
        ]);

        Campaign::create([
            'client_id' => $this->clientA->id,
            'ad_account_id' => $this->adAccountA->id,
            'campaign_id' => 'cmp_a_unique',
            'name' => 'Alpha Campaign A',
            'slug' => 'alpha-campaign-a',
            'status' => 'ACTIVE',
            'spend' => 37500.00,
            'reach' => 88000,
            'impressions' => 190000,
            'clicks' => 5200,
        ]);

        Campaign::create([
            'client_id' => $this->clientB->id,
            'ad_account_id' => $this->adAccountB->id,
            'campaign_id' => 'cmp_b_unique',
            'name' => 'Beta Campaign B',
            'slug' => 'beta-campaign-b',
            'status' => 'ACTIVE',
            'spend' => 4500.00,
            'reach' => 12000,
            'impressions' => 22000,
            'clicks' => 310,
        ]);

        // 1. Check Page A
        $resA = $this->actingAs($this->user)->get(route('analytics.detail', $lpA->slug));
        $resA->assertOk();
        $resA->assertSee('Kirtnix Official 2025');
        $resA->assertSee('act_4151051451781245');
        $resA->assertSee('Alpha Campaign A');
        $resA->assertSee('37,500');
        // Scoped: Must NOT see Client B ad account or campaigns
        $resA->assertDontSee('US Global Forex');
        $resA->assertDontSee('act_9988776655443322');
        $resA->assertDontSee('Beta Campaign B');
        $resA->assertDontSee('4,500');

        // 2. Check Page B
        $resB = $this->actingAs($this->user)->get(route('analytics.detail', $lpB->slug));
        $resB->assertOk();
        $resB->assertSee('US Global Forex');
        $resB->assertSee('act_9988776655443322');
        $resB->assertSee('Beta Campaign B');
        $resB->assertSee('4,500');
        // Scoped: Must NOT see Client A ad account or campaigns
        $resB->assertDontSee('Kirtnix Official 2025');
        $resB->assertDontSee('act_4151051451781245');
        $resB->assertDontSee('Alpha Campaign A');
        $resB->assertDontSee('37,500');
    }

    public function test_cache_keys_are_strictly_client_and_ad_account_isolated(): void
    {
        $service = app(\App\Services\MetaSyncService::class);

        $metricsA = $service->getAdAccountMetrics($this->adAccountA);
        $metricsB = $service->getAdAccountMetrics($this->adAccountB);

        $this->assertEquals('Kirtnix Official 2025', $metricsA['account_name']);
        $this->assertEquals('act_4151051451781245', $metricsA['account_id']);
        $this->assertEquals('INR', $metricsA['currency']);

        $this->assertEquals('US Global Forex', $metricsB['account_name']);
        $this->assertEquals('act_9988776655443322', $metricsB['account_id']);
        $this->assertEquals('USD', $metricsB['currency']);

        $cacheKeyA = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}";
        $cacheKeyB = "meta_analytics:client_{$this->clientB->id}:acc_{$this->adAccountB->id}";

        $this->assertTrue(\Illuminate\Support\Facades\Cache::has($cacheKeyA));
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has($cacheKeyB));
        $this->assertNotEquals($cacheKeyA, $cacheKeyB);
    }

    public function test_analytics_detail_cpc_is_derived_from_meta_clicks_and_equals_3_40(): void
    {
        $lp = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Kirtnix Digital',
            'slug' => 'kirtnix-digital',
            'is_published' => true,
        ]);

        $cta = \App\Models\Cta::create([
            'client_id' => $this->clientA->id,
            'landing_page_id' => $lp->id,
            'name' => 'Join Telegram',
            'button_text' => 'Join Now',
            'button_type' => 'telegram',
            'telegram_destination' => 'https://t.me/test',
        ]);

        // Simulate 1 Telegram CTA click on landing page
        \App\Models\CtaClick::create([
            'client_id' => $this->clientA->id,
            'cta_id' => $cta->id,
            'landing_page_id' => $lp->id,
            'tracking_token' => $cta->tracking_token,
            'destination_url' => 'https://t.me/test',
            'session_id' => 'sess_1',
            'visitor_id' => 'vis_1',
            'clicked_at' => now(),
        ]);

        // Mock Meta API metrics: Spend = 1694.91, Meta Clicks = 498
        $cacheKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_lifetime";
        $fallbackKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}";
        $mockMetrics = [
            'connected' => true,
            'account_name' => 'Kirtnix Official 2025',
            'account_id' => 'act_4151051451781245',
            'business_name' => 'Arabika Kofi',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'status' => 'Active',
            'timezone' => 'Asia/Kolkata',
            'last_sync' => 'Just now',
            'spend_today' => 0.00,
            'spend_scoped' => 1694.91,
            'spend_total' => 1694.91,
            'clicks' => 498,
            'impressions' => 8239,
            'reach' => 7056,
            'leads' => 0,
            'ctr' => 6.04,
            'cpc' => 3.40,
            'cpm' => 205.72,
            'spend_limit' => 0.00,
            'balance' => 0.00,
            'campaigns_count' => 4,
        ];
        \Illuminate\Support\Facades\Cache::put($cacheKey, $mockMetrics, 60);
        \Illuminate\Support\Facades\Cache::put($fallbackKey, $mockMetrics, 60);

        // 1. Detail page HTML response
        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'lifetime']));
        $response->assertOk();

        // Must display CPC = ₹3.40 (1694.91 / 498 clicks)
        $response->assertSee('₹3.40');
        $response->assertSee('id="kpi-cost-per-click"', false);
        $response->assertSee('Spend / Meta ad clicks (CPC)');

        // 2. Live Metrics API endpoint
        $liveResponse = $this->actingAs($this->user)->get("/analytics/{$lp->slug}/live-metrics?date_range=lifetime");
        $liveResponse->assertOk();
        $liveData = $liveResponse->json();
        $this->assertEquals('₹3.40', $liveData['kpis']['cost_per_click']);
    }

    public function test_total_budget_comes_from_real_meta_budget_field_and_is_not_derived_from_lifetime_spend(): void
    {
        $lp = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Kirtnix Budget Test',
            'slug' => 'kirtnix-budget-test',
            'is_published' => true,
        ]);

        // Case A: No spend limit in Meta -> Total Budget must be 0.00 and NEVER derived from lifetime spend
        $this->adAccountA->update(['spend_limit' => 0.00]);
        $cacheKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_lifetime";
        $fallbackKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}";
        $mockMetrics = [
            'connected' => true,
            'account_name' => 'Kirtnix Official 2025',
            'account_id' => 'act_4151051451781245',
            'business_name' => 'Arabika Kofi',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'spend_today' => 0.00,
            'spend_scoped' => 1694.91,
            'spend_total' => 1694.91,
            'clicks' => 498,
            'impressions' => 8239,
            'reach' => 7056,
            'spend_limit' => 0.00,
            'balance' => 0.00,
            'campaigns_count' => 4,
        ];
        \Illuminate\Support\Facades\Cache::put($cacheKey, $mockMetrics, 60);
        \Illuminate\Support\Facades\Cache::put($fallbackKey, $mockMetrics, 60);

        $resA = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'lifetime']));
        $resA->assertOk();
        // Total Spending is 1694.91
        $resA->assertSee('₹1,694.91');
        // Total Budget must NOT be 1694.91, it must be 0.00
        $resA->assertSee('No spend limit configured in Meta');

        // Case B: When Meta has a real spend limit (spend_cap) e.g. 5000.00
        $this->adAccountA->update(['spend_limit' => 5000.00]);
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        \Illuminate\Support\Facades\Cache::forget($fallbackKey);

        $resB = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'lifetime']));
        $resB->assertOk();
        $resB->assertSee('₹5,000.00');
        $resB->assertSee('Account spend limit from Meta');
    }

    public function test_reach_and_impressions_are_zero_when_no_meta_delivery_on_selected_date(): void
    {
        $lp = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Zero Delivery Date Test',
            'slug' => 'zero-delivery-date-test',
            'is_published' => true,
        ]);

        // Mock Meta API metrics for today: 0 delivery (no ads ran today)
        $cacheKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today";
        \Illuminate\Support\Facades\Cache::put($cacheKey, [
            'connected' => true,
            'account_name' => 'Kirtnix Official 2025',
            'account_id' => 'act_4151051451781245',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 0.00,
            'spend_total' => 1694.91,
            'lifetime_spend' => 1694.91,
            'spend_today' => 0.00,
            'clicks' => 0,
            'impressions' => 0,
            'reach' => 0,
            'leads' => 0,
            'ctr' => 0.00,
            'cpc' => 0.00,
            'cpm' => 0.00,
            'spend_limit' => 0.00,
            'balance' => 0.00,
            'campaigns_count' => 4,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'today']));
        $response->assertOk();

        // Reach and Impressions must be 0, NOT lifetime 7056 or 8239
        $response->assertSee('<span id="kpi-reach" class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">0</span>', false);
        $response->assertSee('<span id="kpi-impressions" class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">0</span>', false);
        $response->assertSee('<span id="budget-spending" class="text-3xl font-extrabold text-slate-900 tracking-tight">₹0.00</span>', false);

        // Live metrics endpoint for today must also return 0
        $liveResponse = $this->actingAs($this->user)->get("/analytics/{$lp->slug}/live-metrics?date_range=today");
        $liveResponse->assertOk();
        $liveData = $liveResponse->json('kpis');
        $this->assertEquals('0', $liveData['reach']);
        $this->assertEquals('0', $liveData['impressions']);
        $this->assertEquals('₹0.00', $liveData['cost_per_click']);
    }

    public function test_performance_section_has_only_one_subscriber_card_and_no_duplicate(): void
    {
        $lp = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Single Subscriber Card Test',
            'slug' => 'single-sub-card-test',
            'is_published' => true,
        ]);

        Campaign::create([
            'client_id' => $this->clientA->id,
            'ad_account_id' => $this->adAccountA->id,
            'campaign_id' => 'cmp_outcome_sub',
            'name' => 'Ad Campaign 1',
            'slug' => 'ad-campaign-1',
            'status' => 'paused',
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', $lp->slug));
        $response->assertOk();

        // 1. Single Subscriber KPI card must exist
        $response->assertSee('id="kpi-subscribers"', false);

        // 2. Duplicate 'Approved members' card must NOT exist in HTML
        $response->assertDontSee('id="kpi-approved-members"', false);
        $response->assertDontSee('Approved members');

        // 3. Campaign table Outcome column '• Subscribers' MUST be preserved
        $response->assertSee('Subscribers');
    }

    public function test_total_budget_is_date_scoped_and_never_derived_from_lifetime_spend(): void
    {
        $lp = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Date Scoped Budget Test',
            'slug' => 'date-scoped-budget-test',
            'is_published' => true,
        ]);

        $this->adAccountA->update(['spend_limit' => 0.00]);

        // Campaign with active daily budget of ₹500/day
        Campaign::create([
            'client_id' => $this->clientA->id,
            'ad_account_id' => $this->adAccountA->id,
            'campaign_id' => 'cmp_daily_budget',
            'name' => 'Active Daily Campaign',
            'slug' => 'active-daily-campaign',
            'status' => 'active',
            'active_daily_budget' => 500.00,
        ]);

        // Today: Budget should be 1 day of daily budget = ₹500.00
        $resToday = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'today']));
        $resToday->assertOk();
        $resToday->assertSee('₹500.00');
        $resToday->assertSee('Active daily budget (1 day)');

        // Last 7 days: Budget should be 7 days = ₹3,500.00
        $res7d = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'last_7_days']));
        $res7d->assertOk();
        $res7d->assertSee('₹3,500.00');
        $res7d->assertSee('Active daily budget (7 days)');

        // Last 30 days: Budget should be 30 days = ₹15,000.00
        $res30d = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'last_30_days']));
        $res30d->assertOk();
        $res30d->assertSee('₹15,000.00');
        $res30d->assertSee('Active daily budget (30 days)');
    }

    public function test_date_filter_correctly_scopes_budget_and_spend_metrics(): void
    {
        $lp = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Date Filter Scope Test',
            'slug' => 'date-filter-scope-test',
            'is_published' => true,
        ]);

        // Mock today: ₹0.00 spend, 0 clicks, 0 reach
        \Illuminate\Support\Facades\Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Kirtnix Official 2025',
            'account_id' => 'act_4151051451781245',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 0.00,
            'spend_total' => 1694.91,
            'lifetime_spend' => 1694.91,
            'spend_today' => 0.00,
            'clicks' => 0,
            'impressions' => 0,
            'reach' => 0,
            'spend_limit' => 0.00,
            'balance' => 0.00,
            'campaigns_count' => 4,
        ], 60);

        // Mock lifetime: ₹1,694.91 spend, 498 clicks, 7,056 reach
        \Illuminate\Support\Facades\Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_lifetime", [
            'connected' => true,
            'account_name' => 'Kirtnix Official 2025',
            'account_id' => 'act_4151051451781245',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'lifetime',
            'spend_scoped' => 1694.91,
            'spend_total' => 1694.91,
            'lifetime_spend' => 1694.91,
            'spend_today' => 0.00,
            'clicks' => 498,
            'impressions' => 8239,
            'reach' => 7056,
            'spend_limit' => 0.00,
            'balance' => 0.00,
            'campaigns_count' => 4,
        ], 60);

        // Request Today
        $resToday = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'today']));
        $resToday->assertOk();
        $resToday->assertSee('<span id="budget-spending" class="text-3xl font-extrabold text-slate-900 tracking-tight">₹0.00</span>', false);

        // Request Lifetime
        $resLifetime = $this->actingAs($this->user)->get(route('analytics.detail', [$lp->slug, 'date_range' => 'lifetime']));
        $resLifetime->assertOk();
        $resLifetime->assertSee('<span id="budget-spending" class="text-3xl font-extrabold text-slate-900 tracking-tight">₹1,694.91</span>', false);
        $resLifetime->assertSee('<span id="kpi-reach" class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">7,056</span>', false);
        $resLifetime->assertSee('<span id="kpi-impressions" class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">8,239</span>', false);
        $resLifetime->assertSee('₹3.40');
    }
}
