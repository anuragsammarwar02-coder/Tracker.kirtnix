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
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BudgetGstCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MetaConnection $metaConnection;
    protected MetaBusiness $metaBusiness;
    protected AdAccount $adAccountA;
    protected AdAccount $adAccountB;
    protected Client $clientA;
    protected Client $clientB;
    protected LandingPage $landingPageA;
    protected LandingPage $landingPageB;
    protected TelegramBot $botA;
    protected TelegramChannel $channelA;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'admin@kirtnix.in']);

        $this->metaConnection = MetaConnection::create([
            'access_token' => 'test_token',
            'token_type' => 'Bearer',
            'status' => 'active',
        ]);

        $this->metaBusiness = MetaBusiness::create([
            'meta_connection_id' => $this->metaConnection->id,
            'business_id' => 'biz_123',
            'name' => 'Kirtnix Media',
        ]);

        // Client A with Ad Account A (INR)
        $this->adAccountA = AdAccount::create([
            'meta_business_id' => $this->metaBusiness->id,
            'account_id' => 'act_3825638100985838',
            'name' => 'Streets Account 2',
            'currency' => 'INR',
            'status' => 'Active',
            'spend_limit' => 23305.10,
            'balance' => 21063.04,
            'lifetime_spend' => 2242.06,
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->clientA = Client::create([
            'kx_code' => 'KX-001',
            'company_name' => 'Gujarati Trader Agency',
            'client_name' => 'Gujarati Trader',
            'status' => 'active',
            'ad_account_id' => $this->adAccountA->id,
        ]);
        $this->adAccountA->update(['client_id' => $this->clientA->id]);

        $this->landingPageA = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Gujarati Trader LP',
            'slug' => 'mynkgujarati',
            'is_published' => true,
        ]);

        $this->ctaA = \App\Models\Cta::create([
            'landing_page_id' => $this->landingPageA->id,
            'client_id' => $this->clientA->id,
            'button_text' => 'Join Telegram Channel',
            'tracking_token' => 'kx_test_token',
            'telegram_destination' => 'https://t.me/test_channel',
            'is_active' => true,
        ]);

        $this->botA = TelegramBot::create([
            'client_id' => $this->clientA->id,
            'name' => 'Trader Bot',
            'username' => 'TraderBot',
            'bot_token' => '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11',
            'status' => 'active',
        ]);

        $this->channelA = TelegramChannel::create([
            'client_id' => $this->clientA->id,
            'telegram_bot_id' => $this->botA->id,
            'title' => 'Gujarati Trader Channel',
            'telegram_chat_id' => '-1001234567890',
            'type' => 'private',
            'is_active' => true,
        ]);

        // Client B with Ad Account B (USD)
        $this->adAccountB = AdAccount::create([
            'meta_business_id' => $this->metaBusiness->id,
            'account_id' => 'act_999888777666',
            'name' => 'Global Client Account',
            'currency' => 'USD',
            'status' => 'Active',
            'spend_limit' => 5000.00,
            'balance' => 3000.00,
            'lifetime_spend' => 2000.00,
            'timezone' => 'America/New_York',
        ]);

        $this->clientB = Client::create([
            'kx_code' => 'KX-002',
            'company_name' => 'Global Client Corp',
            'client_name' => 'John Doe',
            'status' => 'active',
            'ad_account_id' => $this->adAccountB->id,
        ]);
        $this->adAccountB->update(['client_id' => $this->clientB->id]);

        $this->landingPageB = LandingPage::create([
            'client_id' => $this->clientB->id,
            'title' => 'Global Client LP',
            'slug' => 'globalclient',
            'is_published' => true,
        ]);
    }

    /**
     * Requirement A & B:
     * - Meta remaining fund = ₹1,000 -> Expected Remaining Budget = ₹820.00 (1000 * 0.82)
     * - Meta remaining fund = ₹21,063.04 -> Expected Remaining Budget = ₹17,271.69 (21063.04 * 0.82)
     * - Meta lifetime spend = ₹2,242.06 -> Expected Total Budget Spend = ₹2,242.06 (actual lifetime spend without GST)
     */
    public function test_remaining_budget_applies_18_percent_gst_and_total_budget_spend_remains_raw_lifetime_spend(): void
    {
        $cacheKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today";
        Cache::put($cacheKey, [
            'connected' => true,
            'account_name' => 'Streets Account 2',
            'account_id' => 'act_3825638100985838',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 2205.43,
            'spend_total' => 2242.06,
            'lifetime_spend' => 2242.06,
            'spend_today' => 2205.43,
            'clicks' => 500,
            'impressions' => 10000,
            'reach' => 8000,
            'leads' => 0,
            'ctr' => 5.0,
            'cpc' => 4.41,
            'cpm' => 220.54,
            'spend_limit' => 23305.10,
            'balance' => 21063.04,
            'campaigns_count' => 13,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        // 1. Total Budget Spend must show exact Meta lifetime spend: ₹2,242.06
        $response->assertSee('₹2,242.06');
        $response->assertSee('id="budget-total"', false);

        // 2. Remaining Budget must show ₹21,063.04 * 0.82 = ₹17,271.69
        $response->assertSee('₹17,271.69');
        $response->assertSee('id="budget-remaining"', false);

        // 3. Live Metrics Endpoint
        $liveResponse = $this->actingAs($this->user)->get("/analytics/{$this->landingPageA->slug}/live-metrics?date_range=today");
        $liveResponse->assertOk();
        $liveData = $liveResponse->json('budget');

        $this->assertEquals('₹2,242.06', $liveData['total_budget_spend']);
        $this->assertEquals('₹17,271.69', $liveData['remaining_budget']);
    }

    /**
     * Requirement A2 & B2 with simple ₹1,000 fund and ₹2,000 lifetime spend:
     * - Remaining fund = ₹1,000 -> Displayed Remaining Budget = ₹820.00
     * - Lifetime spend = ₹2,000 -> Displayed Total Budget Spend = ₹2,000.00
     */
    public function test_simple_1000_fund_and_2000_lifetime_spend_expectations(): void
    {
        $this->adAccountA->update([
            'balance' => 1000.00,
            'lifetime_spend' => 2000.00,
            'spend_limit' => 3000.00,
        ]);

        $cacheKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today";
        Cache::put($cacheKey, [
            'connected' => true,
            'account_name' => 'Streets Account 2',
            'account_id' => 'act_3825638100985838',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 500.00,
            'spend_total' => 2000.00,
            'lifetime_spend' => 2000.00,
            'spend_today' => 500.00,
            'clicks' => 100,
            'impressions' => 2000,
            'reach' => 1500,
            'spend_limit' => 3000.00,
            'balance' => 1000.00,
            'campaigns_count' => 1,
        ], 60);

        $liveResponse = $this->actingAs($this->user)->get("/analytics/{$this->landingPageA->slug}/live-metrics?date_range=today");
        $liveResponse->assertOk();
        $liveData = $liveResponse->json('budget');

        $this->assertEquals('₹2,000.00', $liveData['total_budget_spend']);
        $this->assertEquals('₹820.00', $liveData['remaining_budget']);
    }

    /**
     * Requirement C & E:
     * - Meta spend = ₹500, Clicks = 100 -> Expected CPC = ₹4.10 (500 * 0.82 / 100)
     * - Meta spend = ₹1,000, Clicks = 100 -> Expected CPC = ₹8.20 (1000 * 0.82 / 100)
     * - Verify GST is applied exactly once (not 0.82 * 0.82)
     */
    public function test_cpc_calculated_with_18_percent_gst_adjusted_spend_over_meta_clicks(): void
    {
        $cacheKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today";
        Cache::put($cacheKey, [
            'connected' => true,
            'account_name' => 'Streets Account 2',
            'account_id' => 'act_3825638100985838',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 500.00,
            'spend_total' => 2000.00,
            'spend_today' => 500.00,
            'clicks' => 100,
            'impressions' => 2000,
            'reach' => 1500,
            'spend_limit' => 3000.00,
            'balance' => 1000.00,
            'campaigns_count' => 1,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        // 500 * 0.82 / 100 = 4.10
        $response->assertSee('₹4.10');

        $liveResponse = $this->actingAs($this->user)->get("/analytics/{$this->landingPageA->slug}/live-metrics?date_range=today");
        $liveResponse->assertOk();
        $this->assertEquals('₹4.10', $liveResponse->json('kpis.cost_per_click'));

        // Test with ₹1,000 spend and 100 clicks -> ₹8.20
        Cache::put($cacheKey, [
            'connected' => true,
            'account_name' => 'Streets Account 2',
            'account_id' => 'act_3825638100985838',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 1000.00,
            'spend_total' => 2000.00,
            'spend_today' => 1000.00,
            'clicks' => 100,
            'impressions' => 4000,
            'reach' => 3000,
            'spend_limit' => 3000.00,
            'balance' => 1000.00,
            'campaigns_count' => 1,
        ], 60);

        $liveResponse2 = $this->actingAs($this->user)->get("/analytics/{$this->landingPageA->slug}/live-metrics?date_range=today");
        $liveResponse2->assertOk();
        $this->assertEquals('₹8.20', $liveResponse2->json('kpis.cost_per_click'));
    }

    /**
     * Requirement D & F:
     * - Meta spend = ₹500, Confirmed subscribers = 20 -> Expected Cost/subscriber = ₹20.50 (500 * 0.82 / 20)
     * - Meta spend = ₹1,000, Confirmed subscribers = 20 -> Expected Cost/subscriber = ₹41.00 (1000 * 0.82 / 20)
     * - Confirmed Telegram joins only remain the source of truth for subscriber count (leaves, pending, clicks are not subscribers)
     */
    public function test_cost_per_subscriber_uses_18_percent_gst_adjusted_spend_and_confirmed_telegram_joins(): void
    {
        // 1. Simulate 50 Landing Page Views & 30 CTA Clicks (must NOT be counted as subscribers)
        for ($i = 1; $i <= 50; $i++) {
            \App\Models\LandingPageView::create([
                'landing_page_id' => $this->landingPageA->id,
                'client_id' => $this->clientA->id,
                'visitor_id' => "vid_{$i}",
                'is_unique' => true,
                'viewed_at' => now(),
            ]);
        }
        for ($i = 1; $i <= 30; $i++) {
            \App\Models\CtaClick::create([
                'landing_page_id' => $this->landingPageA->id,
                'cta_id' => $this->ctaA->id,
                'client_id' => $this->clientA->id,
                'tracking_token' => 'kx_test_token',
                'destination_url' => 'https://t.me/test_channel',
                'visitor_id' => "vid_{$i}",
                'is_unique' => true,
                'clicked_at' => now(),
            ]);
        }

        // 2. Create 20 confirmed join events for Client A
        for ($i = 1; $i <= 20; $i++) {
            TelegramEvent::create([
                'client_id' => $this->clientA->id,
                'channel_id' => $this->channelA->id,
                'telegram_user_id' => 10000 + $i,
                'telegram_username' => "subscriber_{$i}",
                'first_name' => "User {$i}",
                'event_type' => 'join',
                'status_after' => 'member',
                'source' => 'ads',
                'event_time' => now(),
            ]);
        }

        // 3. Add 3 channel leaves (these must NOT inflate subscriber count)
        for ($k = 1; $k <= 3; $k++) {
            TelegramEvent::create([
                'client_id' => $this->clientA->id,
                'channel_id' => $this->channelA->id,
                'telegram_user_id' => 30000 + $k,
                'telegram_username' => "leave_{$k}",
                'first_name' => "Left {$k}",
                'event_type' => 'leave',
                'status_after' => 'left',
                'source' => 'ads',
                'event_time' => now(),
            ]);
        }

        $cacheKey = "meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today";
        Cache::put($cacheKey, [
            'connected' => true,
            'account_name' => 'Streets Account 2',
            'account_id' => 'act_3825638100985838',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 500.00,
            'spend_total' => 2000.00,
            'spend_today' => 500.00,
            'clicks' => 100,
            'impressions' => 2000,
            'reach' => 1500,
            'spend_limit' => 3000.00,
            'balance' => 1000.00,
            'campaigns_count' => 1,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        // 20 confirmed subscribers
        $response->assertSee('<span id="kpi-subscribers" class="text-2xl sm:text-3xl font-extrabold text-slate-950 tracking-tight">20</span>', false);

        // Cost per subscriber = (500 * 0.82) / 20 = 410 / 20 = 20.50
        $response->assertSee('₹20.50');

        $liveResponse = $this->actingAs($this->user)->get("/analytics/{$this->landingPageA->slug}/live-metrics?date_range=today");
        $liveResponse->assertOk();
        $this->assertEquals('20', $liveResponse->json('kpis.subscribers'));
        $this->assertEquals('₹20.50', $liveResponse->json('kpis.cost_per_subscriber'));

        // Test with ₹1,000 spend and 20 subscribers -> (1000 * 0.82) / 20 = 41.00
        Cache::put($cacheKey, [
            'connected' => true,
            'account_name' => 'Streets Account 2',
            'account_id' => 'act_3825638100985838',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 1000.00,
            'spend_total' => 2000.00,
            'spend_today' => 1000.00,
            'clicks' => 100,
            'impressions' => 2000,
            'reach' => 1500,
            'spend_limit' => 3000.00,
            'balance' => 1000.00,
            'campaigns_count' => 1,
        ], 60);

        $liveResponse2 = $this->actingAs($this->user)->get("/analytics/{$this->landingPageA->slug}/live-metrics?date_range=today");
        $liveResponse2->assertOk();
        $this->assertEquals('₹41.00', $liveResponse2->json('kpis.cost_per_subscriber'));
    }

    /**
     * Requirement G:
     * - Ensure different clients / ad accounts do not mix financial data or calculations
     */
    public function test_client_and_ad_account_isolation_for_financial_and_gst_metrics(): void
    {
        // Client A: INR account, Spend = 1000, Balance = 5000 -> Remaining = 4100
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Streets Account 2',
            'account_id' => 'act_3825638100985838',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_scoped' => 1000.00,
            'spend_total' => 2500.00,
            'spend_today' => 1000.00,
            'clicks' => 100,
            'impressions' => 2000,
            'reach' => 1500,
            'spend_limit' => 10000.00,
            'balance' => 5000.00,
            'campaigns_count' => 1,
        ], 60);

        // Client B: USD account, Spend = 200, Balance = 1000 -> Remaining = 820
        Cache::put("meta_analytics:client_{$this->clientB->id}:acc_{$this->adAccountB->id}:range_today", [
            'connected' => true,
            'account_name' => 'Global Client Account',
            'account_id' => 'act_999888777666',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'date_range' => 'today',
            'spend_scoped' => 200.00,
            'spend_total' => 1200.00,
            'spend_today' => 200.00,
            'clicks' => 50,
            'impressions' => 1000,
            'reach' => 800,
            'spend_limit' => 5000.00,
            'balance' => 1000.00,
            'campaigns_count' => 1,
        ], 60);

        $resA = $this->actingAs($this->user)->get("/analytics/{$this->landingPageA->slug}/live-metrics?date_range=today");
        $resA->assertOk();
        $this->assertEquals('₹2,500.00', $resA->json('budget.total_budget_spend'));
        $this->assertEquals('₹4,100.00', $resA->json('budget.remaining_budget'));
        $this->assertEquals('₹8.20', $resA->json('kpis.cost_per_click'));

        $resB = $this->actingAs($this->user)->get("/analytics/{$this->landingPageB->slug}/live-metrics?date_range=today");
        $resB->assertOk();
        $this->assertEquals('$1,200.00', $resB->json('budget.total_budget_spend'));
        $this->assertEquals('$820.00', $resB->json('budget.remaining_budget'));
        $this->assertEquals('$3.28', $resB->json('kpis.cost_per_click')); // (200 * 0.82) / 50 = 164 / 50 = 3.28
    }
}
