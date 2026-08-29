<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\LandingPage;
use App\Models\MetaBusiness;
use App\Models\MetaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicClientAnalyticsSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $clientChannel;
    protected Client $kirtnixDigital;
    protected LandingPage $lpChannel;
    protected LandingPage $lpDigital;
    protected AdAccount $adAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'admin@kirtnix.in']);

        $metaConnection = MetaConnection::create([
            'access_token' => 'mock_token',
            'token_type' => 'Bearer',
            'status' => 'active',
        ]);

        $metaBusiness = MetaBusiness::create([
            'meta_connection_id' => $metaConnection->id,
            'business_id' => 'biz_security_test',
            'name' => 'Kirtnix Agency',
        ]);

        $this->adAccount = AdAccount::create([
            'meta_business_id' => $metaBusiness->id,
            'account_id' => 'act_clientchannel_999',
            'name' => 'Client Channel Ad Account',
            'currency' => 'INR',
            'status' => 'ACTIVE',
            'spend_limit' => 50000.00,
            'lifetime_spend' => 12000.00,
        ]);

        // Client 1: clientchannel
        $this->clientChannel = Client::create([
            'kx_code' => 'clientchannel',
            'company_name' => 'Client Channel Corp',
            'client_name' => 'Channel Owner',
            'ad_account_id' => $this->adAccount->id,
            'status' => 'active',
        ]);

        $this->lpChannel = LandingPage::create([
            'client_id' => $this->clientChannel->id,
            'title' => 'Client Channel Page',
            'slug' => 'clientchannel',
            'is_published' => true,
            'is_active' => true,
        ]);

        // Client 2: kirtnix-digital
        $this->kirtnixDigital = Client::create([
            'kx_code' => 'kirtnix-digital',
            'company_name' => 'Kirtnix Digital',
            'client_name' => 'Anurag Sammarwar',
            'ad_account_id' => $this->adAccount->id,
            'status' => 'active',
        ]);

        $this->lpDigital = LandingPage::create([
            'client_id' => $this->kirtnixDigital->id,
            'title' => 'Kirtnix Digital Landing',
            'slug' => 'kirtnix-digital',
            'is_published' => true,
            'is_active' => true,
        ]);

        Campaign::create([
            'client_id' => $this->clientChannel->id,
            'ad_account_id' => $this->adAccount->id,
            'campaign_id' => 'cmp_clientchannel_1',
            'name' => 'Channel Growth Campaign',
            'slug' => 'channel-growth-campaign',
            'spend' => 500.00,
            'reach' => 1500,
            'impressions' => 3000,
            'status' => 'active',
        ]);
    }

    /**
     * Requirement: GET /analytics/detail/clientchannel opens WITHOUT login (200 OK)
     */
    public function test_clientchannel_url_is_accessible_without_login(): void
    {
        $response = $this->get('/analytics/detail/clientchannel');
        $response->assertStatus(200);
        $response->assertSee('Client Channel');
        $response->assertDontSee('Landing pages</a>', false);
    }

    /**
     * Requirement: GET /analytics/detail/kirtnix-digital opens WITHOUT login (200 OK)
     */
    public function test_kirtnix_digital_url_is_accessible_without_login(): void
    {
        $response = $this->get('/analytics/detail/kirtnix-digital');
        $response->assertStatus(200);
        $response->assertSee('Kirtnix Digital');
        $response->assertDontSee('Landing pages</a>', false);
    }

    /**
     * Requirement: Any newly created valid client URL works WITHOUT login (fully dynamic)
     */
    public function test_another_valid_client_slug_is_accessible_without_login(): void
    {
        $newClient = Client::create([
            'kx_code' => 'another-valid-client',
            'company_name' => 'Apex Forex Pro',
            'client_name' => 'Rohit Verma',
            'status' => 'active',
        ]);

        // Access via slugified company name
        $resBySlug = $this->get('/analytics/detail/apex-forex-pro');
        $resBySlug->assertStatus(200);
        $resBySlug->assertSee('Apex Forex Pro');

        // Access via kx_code
        $resByCode = $this->get('/analytics/detail/another-valid-client');
        $resByCode->assertStatus(200);
        $resByCode->assertSee('Apex Forex Pro');
    }

    /**
     * Requirement: Query parameters such as date_range work seamlessly without login
     */
    public function test_valid_client_url_accepts_date_range_without_login(): void
    {
        $response = $this->get('/analytics/detail/kirtnix-digital?date_range=last_7_days');
        $response->assertStatus(200);
        $response->assertSee('Kirtnix Digital');
    }

    /**
     * Requirement: GET /analytics/detail/kirtnix-digital/live-metrics works without login
     */
    public function test_kirtnix_digital_live_metrics_is_accessible_without_login(): void
    {
        $response = $this->get('/analytics/detail/kirtnix-digital/live-metrics');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ok',
            'kpis' => [
                'reach',
                'impressions',
                'lp_views',
                'unique_visitors',
                'tg_clicks',
                'cost_per_click',
                'conversion_rate',
            ],
            'timestamp',
        ]);
        $this->assertTrue($response->json('ok'));
    }

    /**
     * Requirement: GET /analytics/detail/clientchannel/live-metrics works without login
     */
    public function test_clientchannel_live_metrics_is_accessible_without_login(): void
    {
        $response = $this->get('/analytics/detail/clientchannel/live-metrics');
        $response->assertStatus(200);
        $this->assertTrue($response->json('ok'));
    }

    /**
     * Requirement: GET /analytics/detail/non-existent-client MUST NOT expose analytics data
     * (unauthenticated user is redirected to login)
     */
    public function test_invalid_slug_non_existent_client_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/non-existent-client');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement: Invalid slug on live-metrics endpoint returns 404
     */
    public function test_invalid_slug_live_metrics_returns_404(): void
    {
        $response = $this->get('/analytics/detail/non-existent-client/live-metrics');
        $response->assertStatus(404);
        $response->assertJson(['ok' => false, 'error' => 'Client not found.']);
    }

    /**
     * Requirement: Only GET requests are permitted publicly; POST requests are rejected
     */
    public function test_post_request_to_client_analytics_is_not_allowed_publicly(): void
    {
        $res1 = $this->post('/analytics/detail/kirtnix-digital', []);
        $this->assertNotEquals(200, $res1->getStatusCode());

        $res2 = $this->post('/analytics/detail/clientchannel', []);
        $this->assertNotEquals(200, $res2->getStatusCode());
    }

    /**
     * Requirement: Root URL https://tracker.kirtnix.in redirects unauthenticated users to login
     */
    public function test_root_url_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement: https://tracker.kirtnix.in/analytics redirects unauthenticated users to login
     */
    public function test_analytics_base_url_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement: https://tracker.kirtnix.in/analytics/detail redirects unauthenticated users to login
     */
    public function test_analytics_detail_base_url_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement: Trailing slash variation /analytics/detail/ redirects unauthenticated users to login
     */
    public function test_analytics_detail_trailing_slash_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement: /analytics/detail/kirtnix-digital/anything redirects unauthenticated users to login
     */
    public function test_kirtnix_digital_subpath_anything_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/kirtnix-digital/anything');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement: /analytics/detail/clientchannel/anything redirects unauthenticated users to login
     */
    public function test_clientchannel_subpath_anything_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/clientchannel/anything');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement: Deep nested arbitrary subpaths redirect unauthenticated users to login
     */
    public function test_arbitrary_nested_subpaths_redirect_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/kirtnix-digital/foo/bar/test');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement: Authenticated administrators retain full access to all routes
     */
    public function test_authenticated_admin_retains_full_access(): void
    {
        // 1. Root redirects to dashboard for authenticated admin
        $resRoot = $this->actingAs($this->user)->get('/');
        $resRoot->assertRedirect(route('dashboard'));

        // 2. Dashboard is fully accessible
        $resDash = $this->actingAs($this->user)->get('/dashboard');
        $resDash->assertStatus(200);

        // 3. In-App Analytics index is accessible
        $resAnalytics = $this->actingAs($this->user)->get('/analytics');
        $resAnalytics->assertStatus(200);

        // 4. Analytics detail for clientchannel is accessible
        $resDetail = $this->actingAs($this->user)->get('/analytics/detail/clientchannel');
        $resDetail->assertStatus(200);

        // 5. Analytics detail for kirtnix-digital is accessible
        $resDetail2 = $this->actingAs($this->user)->get('/analytics/detail/kirtnix-digital');
        $resDetail2->assertStatus(200);

        // 6. Landing pages admin navigation is visible for authenticated admin
        $resDetail->assertSee('Landing pages');
    }

    /**
     * Requirement: Top 3 Budget KPI boxes match exact business requirements
     * 1. Today's Spending matches today's actual Meta spend (never lifetime fallback)
     * 2. Total Budget Spend matches lifetime actual Meta spend since account creation
     * 3. Remaining Budget matches Meta Billing reported budget, and never invents a fake calculation
     */
    public function test_top_three_budget_kpi_boxes_render_exact_required_meanings_and_sources(): void
    {
        $this->adAccount->update([
            'client_id' => $this->clientChannel->id,
            'lifetime_spend' => 1728.62,
            'spend_limit' => 15000.00,
        ]);

        $cacheKey = "meta_analytics:client_{$this->clientChannel->id}:acc_{$this->adAccount->id}:range_lifetime";
        $fallbackKey = "meta_analytics:client_{$this->clientChannel->id}:acc_{$this->adAccount->id}";
        $mockMetrics = [
            'connected' => true,
            'account_name' => 'Client Channel Ad Account',
            'account_id' => $this->adAccount->account_id,
            'business_name' => 'Kirtnix Agency',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'status' => 'Active',
            'timezone' => 'Asia/Kolkata',
            'last_sync' => 'Just now',
            'date_range' => 'lifetime',
            'spend_scoped' => 1728.62,
            'spend_total' => 1728.62,
            'lifetime_spend' => 1728.62,
            'spend_today' => 500.00,
            'clicks' => 40,
            'impressions' => 1000,
            'reach' => 800,
            'leads' => 5,
            'spend_limit' => 15000.00,
            'balance' => 0.00,
            'campaigns_count' => 1,
        ];
        \Illuminate\Support\Facades\Cache::put($cacheKey, $mockMetrics, 60);
        \Illuminate\Support\Facades\Cache::put($fallbackKey, $mockMetrics, 60);

        $res = $this->get('/analytics/detail/clientchannel?date_range=lifetime');
        $res->assertStatus(200);

        // 1. Box 1: Today's Spending shows 500.00 (NOT the 1728.62 lifetime spend)
        $res->assertSee("Today's Spending", false);
        $res->assertSee('₹500.00');
        $res->assertSee('Actual spending for today');

        // 2. Box 2: Total Budget Spend shows 1,728.62
        $res->assertSee('Total Budget Spend');
        $res->assertSee('₹1,728.62');
        $res->assertSee('Actual spending since account creation');

        // 3. Box 3: Remaining Budget shows 15000 - 1728.62 = 13,271.38 (from Meta spend limit)
        $res->assertSee('Remaining Budget');
        $res->assertSee('₹13,271.38');
    }

    /**
     * Requirement: No fake remaining budget is shown when Meta has no spend limit or balance
     */
    public function test_no_fake_remaining_budget_shown_when_meta_has_no_limit_or_balance(): void
    {
        $this->adAccount->update([
            'client_id' => $this->clientChannel->id,
            'lifetime_spend' => 1728.62,
            'spend_limit' => 0.00,
            'balance' => 0.00,
        ]);

        $cacheKey = "meta_analytics:client_{$this->clientChannel->id}:acc_{$this->adAccount->id}:range_lifetime";
        $fallbackKey = "meta_analytics:client_{$this->clientChannel->id}:acc_{$this->adAccount->id}";
        $mockMetrics = [
            'connected' => true,
            'account_name' => 'Client Channel Ad Account',
            'account_id' => $this->adAccount->account_id,
            'business_name' => 'Kirtnix Agency',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'status' => 'Active',
            'timezone' => 'Asia/Kolkata',
            'last_sync' => 'Just now',
            'date_range' => 'lifetime',
            'spend_scoped' => 1728.62,
            'spend_total' => 1728.62,
            'lifetime_spend' => 1728.62,
            'spend_today' => 0.00,
            'clicks' => 0,
            'impressions' => 0,
            'reach' => 0,
            'spend_limit' => 0.00,
            'balance' => 0.00,
            'campaigns_count' => 1,
        ];
        \Illuminate\Support\Facades\Cache::put($cacheKey, $mockMetrics, 60);
        \Illuminate\Support\Facades\Cache::put($fallbackKey, $mockMetrics, 60);

        $res = $this->get('/analytics/detail/clientchannel?date_range=lifetime');
        $res->assertStatus(200);

        // Must display "No limit set" and NOT a fake calculated number
        $res->assertSee('No limit set');
        $res->assertSee('No spend limit set in Meta billing');
    }
}
