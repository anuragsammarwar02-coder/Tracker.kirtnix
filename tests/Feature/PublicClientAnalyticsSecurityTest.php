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
    protected Client $client;
    protected LandingPage $landingPage;
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

        $this->client = Client::create([
            'kx_code' => 'KX-CLIENTCHANNEL',
            'company_name' => 'Client Channel Corp',
            'client_name' => 'Channel Owner',
            'ad_account_id' => $this->adAccount->id,
            'status' => 'active',
        ]);

        $this->landingPage = LandingPage::create([
            'client_id' => $this->client->id,
            'title' => 'Client Channel Page',
            'slug' => 'clientchannel',
            'is_published' => true,
            'is_active' => true,
        ]);

        Campaign::create([
            'client_id' => $this->client->id,
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
     * Requirement 1 & 7: Exact allowlisted URL /analytics/detail/clientchannel opens WITHOUT login (200 OK)
     */
    public function test_exact_clientchannel_url_is_accessible_without_login(): void
    {
        $response = $this->get('/analytics/detail/clientchannel');
        $response->assertStatus(200);
        $response->assertSee('Client Channel');
        $response->assertSee('/analytics/detail/clientchannel');
        // Verify internal agency admin navigation is hidden from unauthenticated guests
        $response->assertDontSee('Landing pages</a>', false);
    }

    /**
     * Requirement 1: Query parameters such as date_range work seamlessly on the public client page
     */
    public function test_clientchannel_url_accepts_date_range_without_login(): void
    {
        $response = $this->get('/analytics/detail/clientchannel?date_range=last_7_days');
        $response->assertStatus(200);
        $response->assertSee('Client Channel');
    }

    /**
     * Requirement 6: Live metrics real-time polling endpoint works for clientchannel without login
     */
    public function test_clientchannel_live_metrics_is_accessible_without_login(): void
    {
        $response = $this->get('/analytics/detail/clientchannel/live-metrics');
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
     * Requirement 2: Only GET requests are permitted publicly; POST requests are rejected
     */
    public function test_post_request_to_clientchannel_is_not_allowed_publicly(): void
    {
        $response = $this->post('/analytics/detail/clientchannel', []);
        $this->assertNotEquals(200, $response->getStatusCode());
    }

    /**
     * Requirement 4 & 7: Root URL https://tracker.kirtnix.in redirects unauthenticated users to login
     */
    public function test_root_url_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement 3 & 7: https://tracker.kirtnix.in/analytics redirects unauthenticated users to login
     */
    public function test_analytics_base_url_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement 3 & 7: https://tracker.kirtnix.in/analytics/detail redirects unauthenticated users to login
     */
    public function test_analytics_detail_base_url_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement 5: Trailing slash variation /analytics/detail/ redirects unauthenticated users to login
     */
    public function test_analytics_detail_trailing_slash_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement 5 & 7: /analytics/detail/clientchannel/anything redirects unauthenticated users to login
     */
    public function test_clientchannel_subpath_anything_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/clientchannel/anything');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement 5: Arbitrary nested subpaths under clientchannel redirect unauthenticated users to login
     */
    public function test_clientchannel_deep_subpaths_redirect_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/clientchannel/foo/bar');
        $response->assertRedirect(route('login'));
    }

    /**
     * Requirement 3: Non-allowlisted analytics detail slugs redirect unauthenticated users to login
     */
    public function test_other_analytics_slugs_redirect_unauthenticated_to_login(): void
    {
        $response = $this->get('/analytics/detail/other-private-client');
        $response->assertRedirect(route('login'));

        $response2 = $this->get('/analytics/other-private-client');
        $response2->assertRedirect(route('login'));
    }

    /**
     * Requirement 8: Authenticated administrators retain full access to all routes
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

        // 5. Landing pages admin navigation is visible for authenticated admin
        $resDetail->assertSee('Landing pages');
    }
}
