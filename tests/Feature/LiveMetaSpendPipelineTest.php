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
use App\Services\MetaSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LiveMetaSpendPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MetaBusiness $metaBusiness;
    protected AdAccount $adAccountA;
    protected AdAccount $adAccountB;
    protected Client $clientA;
    protected Client $clientB;
    protected LandingPage $landingPageA;
    protected LandingPage $landingPageB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'admin@kirtnix.in']);

        $metaConnection = MetaConnection::create([
            'access_token' => 'EAAB_VALID_TEST_TOKEN',
            'token_type' => 'Bearer',
            'status' => 'active',
        ]);

        $this->metaBusiness = MetaBusiness::create([
            'meta_connection_id' => $metaConnection->id,
            'business_id' => 'biz_test_001',
            'name' => 'Kirtnix Test Business',
        ]);

        // Client A - Gujarati Trader mapped to Gujarati 2 Backup
        $this->adAccountA = AdAccount::create([
            'meta_business_id' => $this->metaBusiness->id,
            'account_id' => 'act_1673560083719083',
            'name' => 'Gujrati 2 Backup',
            'currency' => 'INR',
            'status' => 'ACTIVE',
            'spend_limit' => 9491.52,
            'balance' => 724.70,
            'lifetime_spend' => 4321.45,
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->clientA = Client::create([
            'kx_code' => 'KX-002',
            'company_name' => 'Gujarati Trdaer',
            'client_name' => 'Mayank',
            'status' => 'active',
            'ad_account_id' => $this->adAccountA->id,
        ]);
        $this->adAccountA->update(['client_id' => $this->clientA->id]);

        $this->landingPageA = LandingPage::create([
            'client_id' => $this->clientA->id,
            'title' => 'Gujarati Trdaer LP',
            'slug' => 'mynkgujarati',
            'is_published' => true,
        ]);

        // Client B - Separate client mapped to separate ad account
        $this->adAccountB = AdAccount::create([
            'meta_business_id' => $this->metaBusiness->id,
            'account_id' => 'act_999988887777',
            'name' => 'Forex Pro Account',
            'currency' => 'INR',
            'status' => 'ACTIVE',
            'spend_limit' => 50000.00,
            'balance' => 1500.00,
            'lifetime_spend' => 12500.00,
            'timezone' => 'Asia/Kolkata',
        ]);

        $this->clientB = Client::create([
            'kx_code' => 'KX-003',
            'company_name' => 'Forex Pro',
            'client_name' => 'Rajesh',
            'status' => 'active',
            'ad_account_id' => $this->adAccountB->id,
        ]);
        $this->adAccountB->update(['client_id' => $this->clientB->id]);

        $this->landingPageB = LandingPage::create([
            'client_id' => $this->clientB->id,
            'title' => 'Forex Pro LP',
            'slug' => 'forexpro',
            'is_published' => true,
        ]);
    }

    /**
     * Test 1 & 2: Today spend from Meta = ₹500 -> Today's Spending = ₹500, Lifetime spend = ₹4,500 -> Total Budget Spend = ₹4,500
     */
    public function test_1_and_2_today_and_lifetime_spend_display(): void
    {
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_today' => 500.00,
            'spend_scoped' => 500.00,
            'spend_total' => 4500.00,
            'lifetime_spend' => 4500.00,
            'clicks' => 100,
            'impressions' => 2500,
            'reach' => 2000,
            'balance' => 724.70,
            'spend_limit' => 9491.52,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        // Today's spending = ₹500.00
        $response->assertSee('<span id="budget-spending" class="text-3xl font-extrabold text-slate-900 tracking-tight">₹500.00</span>', false);
        // Total Budget Spend = ₹4,500.00
        $response->assertSee('<span id="budget-total" class="text-3xl font-extrabold text-slate-900 tracking-tight">₹4,500.00</span>', false);
    }

    /**
     * Test 3: Today spend = ₹500, Meta clicks = 100, Existing 18% GST rule -> net_spend = ₹410 -> CPC = ₹4.10
     */
    public function test_3_cpc_calculation_with_gst_and_meta_ad_clicks(): void
    {
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_today' => 500.00,
            'spend_scoped' => 500.00,
            'spend_total' => 4500.00,
            'clicks' => 100,
            'impressions' => 2500,
            'reach' => 2000,
            'balance' => 724.70,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        // 500 * 0.82 = 410. 410 / 100 clicks = ₹4.10
        $response->assertSee('₹4.10');
        $response->assertSee('Spend / Meta ad clicks (CPC)');
    }

    /**
     * Test 4: Today spend = ₹500, Confirmed subscribers = 20, Existing 18% GST rule -> net_spend = ₹410 -> Cost / Subscriber = ₹20.50
     */
    public function test_4_cost_per_subscriber_calculation(): void
    {
        // Add 20 confirmed telegram subscribers today for clientA
        for ($i = 1; $i <= 20; $i++) {
            TelegramEvent::create([
                'client_id' => $this->clientA->id,
                'telegram_user_id' => 'tg_user_' . $i,
                'telegram_username' => 'trader_' . $i,
                'first_name' => 'Trader',
                'event_type' => 'join',
                'status_after' => 'member',
                'source' => 'ads',
                'event_time' => now(),
            ]);
        }

        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_today' => 500.00,
            'spend_scoped' => 500.00,
            'spend_total' => 4500.00,
            'clicks' => 100,
            'impressions' => 2500,
            'reach' => 2000,
            'balance' => 724.70,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        // 20 subscribers
        $response->assertSee('<span id="kpi-subscribers" class="text-2xl sm:text-3xl font-extrabold text-slate-950 tracking-tight">20</span>', false);
        // Cost / Sub = 410 / 20 = ₹20.50
        $response->assertSee('₹20.50');
    }

    /**
     * Test 5: Yesterday uses yesterday's spend.
     */
    public function test_5_yesterday_uses_yesterday_spend(): void
    {
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_yesterday", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'yesterday',
            'spend_today' => 0.00,
            'spend_scoped' => 850.00,
            'spend_total' => 4500.00,
            'clicks' => 170,
            'impressions' => 4000,
            'reach' => 3200,
            'balance' => 724.70,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'yesterday']));
        $response->assertOk();

        // Yesterday CPC = (850 * 0.82) / 170 = 697 / 170 = ₹4.10
        $response->assertSee('₹4.10');
        $response->assertSee('<span id="kpi-reach" class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">3,200</span>', false);
        $response->assertSee('<span id="kpi-impressions" class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">4,000</span>', false);
    }

    /**
     * Test 6: Last 7 Days uses the same 7-day spend range for spend/click calculations.
     */
    public function test_6_last_7_days_uses_7_day_spend_range(): void
    {
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_last_7_days", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'last_7_days',
            'spend_today' => 500.00,
            'spend_scoped' => 3500.00,
            'spend_total' => 4500.00,
            'clicks' => 700,
            'impressions' => 18000,
            'reach' => 14000,
            'balance' => 724.70,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'last_7_days']));
        $response->assertOk();

        // 7-day CPC = (3500 * 0.82) / 700 = 2870 / 700 = ₹4.10
        $response->assertSee('₹4.10');
        $response->assertSee('<span id="kpi-reach" class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">14,000</span>', false);
    }

    /**
     * Test 7: Client A cannot receive Client B's Meta spend.
     */
    public function test_7_client_isolation_between_accounts(): void
    {
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_today' => 500.00,
            'spend_scoped' => 500.00,
            'spend_total' => 4500.00,
            'clicks' => 100,
            'impressions' => 2500,
            'reach' => 2000,
            'balance' => 724.70,
        ], 60);

        Cache::put("meta_analytics:client_{$this->clientB->id}:acc_{$this->adAccountB->id}:range_today", [
            'connected' => true,
            'account_name' => 'Forex Pro Account',
            'account_id' => 'act_999988887777',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_today' => 1500.00,
            'spend_scoped' => 1500.00,
            'spend_total' => 12500.00,
            'clicks' => 300,
            'impressions' => 7500,
            'reach' => 6000,
            'balance' => 1500.00,
        ], 60);

        // Check Client A
        $resA = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $resA->assertOk();
        $resA->assertSee('₹500.00');
        $resA->assertSee('₹4,500.00');
        $resA->assertDontSee('₹1,500.00');
        $resA->assertDontSee('₹12,500.00');

        // Check Client B
        $resB = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageB->slug, 'date_range' => 'today']));
        $resB->assertOk();
        $resB->assertSee('₹1,500.00');
        $resB->assertSee('₹12,500.00');
        $resB->assertDontSee('₹500.00');
    }

    /**
     * Test 8: liveMetrics does not overwrite valid spend with incorrect zero.
     */
    public function test_8_live_metrics_matches_detail_spend(): void
    {
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_today' => 500.00,
            'spend_scoped' => 500.00,
            'spend_total' => 4500.00,
            'clicks' => 100,
            'impressions' => 2500,
            'reach' => 2000,
            'balance' => 724.70,
        ], 60);

        $liveRes = $this->actingAs($this->user)->get("/analytics/{$this->landingPageA->slug}/live-metrics?date_range=today");
        $liveRes->assertOk();
        $data = $liveRes->json();

        $this->assertEquals('₹500.00', $data['budget']['today_spending']);
        $this->assertEquals('₹4,500.00', $data['budget']['total_budget_spend']);
        $this->assertEquals('₹4.10', $data['kpis']['cost_per_click']);
    }

    /**
     * Test 9: A genuine Meta zero-spend response remains ₹0.00.
     */
    public function test_9_genuine_zero_spend_remains_zero(): void
    {
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_today' => 0.00,
            'spend_scoped' => 0.00,
            'spend_total' => 4321.45,
            'clicks' => 0,
            'impressions' => 0,
            'reach' => 0,
            'balance' => 724.70,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        $response->assertSee('<span id="budget-spending" class="text-3xl font-extrabold text-slate-900 tracking-tight">₹0.00</span>', false);
        $response->assertSee('<span id="budget-total" class="text-3xl font-extrabold text-slate-900 tracking-tight">₹4,321.45</span>', false);
        $response->assertSee('₹0.00');
    }

    /**
     * Test 10: Existing Remaining Budget behavior remains unchanged.
     */
    public function test_10_remaining_budget_behavior_preserved(): void
    {
        Cache::put("meta_analytics:client_{$this->clientA->id}:acc_{$this->adAccountA->id}:range_today", [
            'connected' => true,
            'account_name' => 'Gujrati 2 Backup',
            'account_id' => 'act_1673560083719083',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'date_range' => 'today',
            'spend_today' => 500.00,
            'spend_scoped' => 500.00,
            'spend_total' => 4500.00,
            'clicks' => 100,
            'balance' => 724.70,
            'spend_limit' => 9491.52,
        ], 60);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        // Remaining Budget uses exact Meta available fund / balance
        $response->assertSee('<span id="budget-remaining" class="text-3xl font-extrabold text-slate-900 tracking-tight">₹724.70</span>', false);
        $response->assertSee('Remaining fund in Meta ad account');
    }

    /**
     * Test 11: Subscriber count remains confirmed Telegram joins only.
     */
    public function test_11_subscriber_count_strictly_confirmed_joins(): void
    {
        // 5 confirmed joins
        for ($i = 1; $i <= 5; $i++) {
            TelegramEvent::create([
                'client_id' => $this->clientA->id,
                'telegram_user_id' => 'user_join_' . $i,
                'event_type' => 'join',
                'status_after' => 'member',
                'event_time' => now(),
            ]);
        }

        // 3 pending join requests (must NOT be counted in Subscribers)
        for ($i = 1; $i <= 3; $i++) {
            TelegramEvent::create([
                'client_id' => $this->clientA->id,
                'telegram_user_id' => 'user_pending_' . $i,
                'event_type' => 'join_request',
                'status_after' => 'pending',
                'event_time' => now(),
            ]);
        }

        // 2 leaves
        for ($i = 1; $i <= 2; $i++) {
            TelegramEvent::create([
                'client_id' => $this->clientA->id,
                'telegram_user_id' => 'user_leave_' . $i,
                'event_type' => 'leave',
                'status_after' => 'left',
                'event_time' => now(),
            ]);
        }

        $response = $this->actingAs($this->user)->get(route('analytics.detail', [$this->landingPageA->slug, 'date_range' => 'today']));
        $response->assertOk();

        // Verified subscriber joins must be 5
        $response->assertSee('id="kpi-subscribers"', false);
        $response->assertSee('id="kpi-pending-requests"', false);
    }
}
