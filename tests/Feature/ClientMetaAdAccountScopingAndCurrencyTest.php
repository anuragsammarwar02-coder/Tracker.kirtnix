<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\MetaBusiness;
use App\Models\MetaConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientMetaAdAccountScopingAndCurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MetaConnection $connection;
    protected MetaBusiness $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'super_admin']);

        $this->connection = MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_test_scoped',
            'facebook_name' => 'Kirtnix Meta Admin',
            'access_token' => 'EAAG_test_scoped_token',
            'status' => 'active',
        ]);

        $this->business = MetaBusiness::create([
            'meta_connection_id' => $this->connection->id,
            'business_id' => 'biz_test_scoped',
            'name' => 'Kirtnix Agency Business',
        ]);
    }

    public function test_inr_client_displays_rupee_symbol_and_scoped_spend(): void
    {
        // 1. Create INR Ad Account & Client
        $inrAccount = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'meta_business_id' => $this->business->id,
            'account_id' => 'act_inr_111',
            'name' => 'INR Trading Account',
            'currency' => 'INR',
            'status' => 'Active',
            'lifetime_spend' => 15400.50,
            'is_active' => true,
        ]);

        $clientInr = Client::create([
            'company_name' => 'Gujarat Trading Corp',
            'client_name' => 'Rajesh Patel',
            'kx_code' => 'KX-INR',
            'status' => 'active',
            'ad_account_id' => $inrAccount->id,
            'meta_ads_connected' => true,
        ]);
        $inrAccount->update(['client_id' => $clientInr->id]);

        $campaignInr = Campaign::create([
            'client_id' => $clientInr->id,
            'ad_account_id' => $inrAccount->id,
            'campaign_id' => 'cmp_inr_1',
            'name' => 'BankNifty Scalper INR',
            'slug' => 'banknifty-scalper-inr',
            'spend' => 12500.00,
            'reach' => 45000,
            'impressions' => 60000,
            'status' => 'active',
        ]);

        // 2. Assert model accessors
        $this->assertEquals('₹', $inrAccount->currency_symbol);
        $this->assertEquals('INR', $clientInr->currency);
        $this->assertEquals('₹', $clientInr->currency_symbol);

        // 3. Test Dashboard scoped to INR client
        $response = $this->actingAs($this->user)->get(route('dashboard', ['client_id' => $clientInr->id]));
        $response->assertOk();
        $response->assertSee('₹12,500.00');
        $response->assertSee('Gujarat Trading Corp');

        // 4. Test Analytics scoped to INR client
        $response = $this->actingAs($this->user)->get(route('analytics.index', ['client_id' => $clientInr->id]));
        $response->assertOk();
        $response->assertSee('₹12,500.00');
        $response->assertSee('BankNifty Scalper INR');
    }

    public function test_usd_client_displays_dollar_symbol_and_scoped_spend(): void
    {
        // 1. Create USD Ad Account & Client
        $usdAccount = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'meta_business_id' => $this->business->id,
            'account_id' => 'act_usd_222',
            'name' => 'Global Forex USD Account',
            'currency' => 'USD',
            'status' => 'Active',
            'lifetime_spend' => 450.00,
            'is_active' => true,
        ]);

        $clientUsd = Client::create([
            'company_name' => 'Forex Alpha Global',
            'client_name' => 'John Doe',
            'kx_code' => 'KX-USD',
            'status' => 'active',
            'ad_account_id' => $usdAccount->id,
            'meta_ads_connected' => true,
        ]);
        $usdAccount->update(['client_id' => $clientUsd->id]);

        $campaignUsd = Campaign::create([
            'client_id' => $clientUsd->id,
            'ad_account_id' => $usdAccount->id,
            'campaign_id' => 'cmp_usd_1',
            'name' => 'US30 Gold Scalp USD',
            'slug' => 'us30-gold-scalp-usd',
            'spend' => 350.00,
            'reach' => 12000,
            'impressions' => 18000,
            'status' => 'active',
        ]);

        // 2. Assert model accessors
        $this->assertEquals('$', $usdAccount->currency_symbol);
        $this->assertEquals('USD', $clientUsd->currency);
        $this->assertEquals('$', $clientUsd->currency_symbol);

        // 3. Test Dashboard scoped to USD client
        $response = $this->actingAs($this->user)->get(route('dashboard', ['client_id' => $clientUsd->id]));
        $response->assertOk();
        $response->assertSee('$350.00');
        $response->assertSee('Forex Alpha Global');

        // 4. Test Analytics scoped to USD client
        $response = $this->actingAs($this->user)->get(route('analytics.index', ['client_id' => $clientUsd->id]));
        $response->assertOk();
        $response->assertSee('$350.00');
        $response->assertSee('US30 Gold Scalp USD');
    }

    public function test_all_clients_dashboard_scopes_properly_across_multiple_clients(): void
    {
        // 1. Create Active Client A with INR account
        $accountA = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'account_id' => 'act_a_1',
            'name' => 'Client A Account',
            'currency' => 'INR',
            'lifetime_spend' => 2000.00,
            'is_active' => true,
        ]);
        $clientA = Client::create([
            'company_name' => 'Active Client A',
            'client_name' => 'Owner A',
            'kx_code' => 'KX-A',
            'status' => 'active',
            'ad_account_id' => $accountA->id,
        ]);
        $accountA->update(['client_id' => $clientA->id]);

        Campaign::create([
            'client_id' => $clientA->id,
            'ad_account_id' => $accountA->id,
            'campaign_id' => 'cmp_a',
            'name' => 'Campaign A',
            'slug' => 'campaign-a',
            'spend' => 2000.00,
            'status' => 'active',
        ]);
        // 2. Create Active Client B with USD account
        $accountB = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'account_id' => 'act_b_1',
            'name' => 'Client B Account',
            'currency' => 'USD',
            'lifetime_spend' => 350.00,
            'is_active' => true,
        ]);
        $clientB = Client::create([
            'company_name' => 'Active Client B',
            'client_name' => 'Owner B',
            'kx_code' => 'KX-B',
            'status' => 'active',
            'ad_account_id' => $accountB->id,
        ]);
        $accountB->update(['client_id' => $clientB->id]);

        Campaign::create([
            'client_id' => $clientB->id,
            'ad_account_id' => $accountB->id,
            'campaign_id' => 'cmp_b',
            'name' => 'Campaign B',
            'slug' => 'campaign-b',
            'spend' => 350.00,
            'status' => 'active',
        ]);

        // 3. Client A specific dashboard view (INR)
        $responseA = $this->actingAs($this->user)->get(route('dashboard', ['client_id' => $clientA->id]));
        $responseA->assertOk();
        $responseA->assertSee('₹2,000.00');

        // 4. Client B specific dashboard view (USD)
        $responseB = $this->actingAs($this->user)->get(route('dashboard', ['client_id' => $clientB->id]));
        $responseB->assertOk();
        $responseB->assertSee('$350.00');
    }

    public function test_analytics_detail_page_renders_dynamic_campaigns_and_never_hardcoded_dummy_data(): void
    {
        $adAccount = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'account_id' => 'act_real_999',
            'name' => 'Kirtnix Digital Ad Account',
            'currency' => 'INR',
            'lifetime_spend' => 8420.00,
            'is_active' => true,
        ]);

        $client = Client::create([
            'company_name' => 'Kirtnix Digital Media',
            'client_name' => 'Kirti',
            'kx_code' => 'KX-KD',
            'status' => 'active',
            'ad_account_id' => $adAccount->id,
        ]);
        $adAccount->update(['client_id' => $client->id]);

        $lp = \App\Models\LandingPage::create([
            'client_id' => $client->id,
            'title' => 'Kirtnix Digital LP',
            'slug' => 'kirtnix-digital',
            'is_published' => true,
            'is_active' => true,
        ]);

        Campaign::create([
            'client_id' => $client->id,
            'ad_account_id' => $adAccount->id,
            'campaign_id' => 'cmp_real_lead_1',
            'name' => 'Real Active Lead Campaign',
            'slug' => 'real-active-lead-campaign',
            'spend' => 8420.00,
            'reach' => 15000,
            'impressions' => 22000,
            'status' => 'active',
            'outcome' => 'Subscribers',
            'objective' => 'OUTCOME_LEADS',
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', 'kirtnix-digital'));
        $response->assertOk();
        // Must see real campaign name
        $response->assertSee('Real Active Lead Campaign');
        $response->assertSee('Kirtnix Digital Ad Account');
        $response->assertSee('₹8,420.00');
        // Must NOT see hardcoded dummy campaigns
        $response->assertDontSee('GJ001');
        $response->assertDontSee('GJ002');
        $response->assertDontSee('GJ003');
        $response->assertDontSee('GJ004');
        $response->assertDontSee('Pagelike ad');
    }

    public function test_analytics_detail_page_renders_empty_state_when_ad_account_has_no_campaigns(): void
    {
        $adAccount = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'account_id' => 'act_empty_888',
            'name' => 'Empty Fresh Account',
            'currency' => 'USD',
            'lifetime_spend' => 0.00,
            'is_active' => true,
        ]);

        $client = Client::create([
            'company_name' => 'Fresh Client Corp',
            'client_name' => 'Alex',
            'kx_code' => 'KX-FC',
            'status' => 'active',
            'ad_account_id' => $adAccount->id,
        ]);
        $adAccount->update(['client_id' => $client->id]);

        $lp = \App\Models\LandingPage::create([
            'client_id' => $client->id,
            'title' => 'Fresh Client LP',
            'slug' => 'fresh-client-lp',
            'is_published' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('analytics.detail', 'fresh-client-lp'));
        $response->assertOk();
        $response->assertSee('No campaigns found for this Meta Ad Account');
        $response->assertDontSee('GJ001');
    }

    public function test_live_metrics_endpoint_returns_json_and_calculates_cost_per_click(): void
    {
        $adAccount = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'account_id' => 'act_live_999',
            'name' => 'Live Polling Account',
            'currency' => 'INR',
            'lifetime_spend' => 1000.00,
            'is_active' => true,
        ]);

        $client = Client::create([
            'company_name' => 'Live Polling Client',
            'client_name' => 'Live Tester',
            'kx_code' => 'KX-LIVE',
            'status' => 'active',
            'ad_account_id' => $adAccount->id,
        ]);
        $adAccount->update(['client_id' => $client->id]);

        $lp = \App\Models\LandingPage::create([
            'client_id' => $client->id,
            'title' => 'Live LP',
            'slug' => 'live-lp-test',
            'is_published' => true,
            'is_active' => true,
        ]);

        Campaign::create([
            'client_id' => $client->id,
            'ad_account_id' => $adAccount->id,
            'campaign_id' => 'cmp_live_1',
            'name' => 'Live Campaign',
            'slug' => 'live-campaign',
            'spend' => 1000.00,
            'reach' => 5000,
            'impressions' => 10000,
            'status' => 'active',
        ]);

        \App\Models\Cta::create([
            'landing_page_id' => $lp->id,
            'client_id' => $client->id,
            'button_text' => 'Join Now',
            'tracking_token' => 'kx_live_btn',
            'telegram_destination' => 'https://t.me/live_channel',
        ]);

        // Record 10 views and 2 CTA clicks using public tracking APIs
        for ($i = 0; $i < 10; $i++) {
            $res = $this->postJson(route('api.track.view'), [
                'landing_page_id' => $lp->id,
                'visitor_id' => 'vis_live_' . $i,
            ]);
            $res->assertOk();
        }
        for ($i = 0; $i < 2; $i++) {
            $res = $this->postJson(route('api.track.click'), [
                'landing_page_id' => $lp->id,
                'visitor_id' => 'vis_live_' . $i,
                'destination_url' => 'https://t.me/live_channel',
            ]);
            $res->assertOk();
        }

        $response = $this->actingAs($this->user)->get("/analytics/{$lp->slug}/live-metrics");
        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'kpis' => [
                'lp_views' => '10',
                'tg_clicks' => '2',
                'cost_per_click' => '₹500.00',
            ],
        ]);
    }

    public function test_business_name_comes_authoritatively_from_meta_business_relation_and_not_arbitrary_fallback(): void
    {
        $realBusiness = MetaBusiness::create([
            'meta_connection_id' => $this->connection->id,
            'business_id' => 'biz_kirtnix_official_bm',
            'name' => 'Kirtnix Performance Media BM',
        ]);

        $adAccount = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'meta_business_id' => $realBusiness->id,
            'account_id' => 'act_kirtnix_official_01',
            'name' => 'Kirtnix Official',
            'currency' => 'INR',
            'lifetime_spend' => 0.00,
            'is_active' => true,
        ]);

        $client = Client::create([
            'company_name' => 'Kirtnix Enterprise',
            'client_name' => 'Anurag',
            'kx_code' => 'KX-KIRTNIX',
            'status' => 'active',
            'ad_account_id' => $adAccount->id,
        ]);
        $adAccount->update(['client_id' => $client->id]);

        $metrics = app(\App\Services\MetaSyncService::class)->getAdAccountMetrics($adAccount, true);

        $this->assertEquals('Kirtnix Performance Media BM', $metrics['business_name']);
        $this->assertNotEquals('Arabika Kofi', $metrics['business_name']);

        $response = $this->actingAs($this->user)->get(route('clients.show', $client->id));
        $response->assertOk();
        $response->assertSee('Kirtnix Performance Media BM');
        $response->assertDontSee('Arabika Kofi');
    }

    public function test_ad_account_without_business_displays_not_available_from_meta_and_never_arabika_kofi(): void
    {
        $adAccount = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'meta_business_id' => null, // Explicitly no business assigned
            'account_id' => 'act_personal_solo_02',
            'name' => 'Solo Direct Ad Account',
            'currency' => 'INR',
            'lifetime_spend' => 0.00,
            'is_active' => true,
        ]);

        $client = Client::create([
            'company_name' => 'Personal Client',
            'client_name' => 'Rahul',
            'kx_code' => 'KX-PERSONAL',
            'status' => 'active',
            'ad_account_id' => $adAccount->id,
        ]);
        $adAccount->update(['client_id' => $client->id]);

        $metrics = app(\App\Services\MetaSyncService::class)->getAdAccountMetrics($adAccount, true);

        $this->assertNull($metrics['business_name']);
        $this->assertNotEquals('Arabika Kofi', $metrics['business_name']);

        $response = $this->actingAs($this->user)->get(route('clients.show', $client->id));
        $response->assertOk();
        $response->assertSee('Not available from Meta');
        $response->assertDontSee('Arabika Kofi');
    }

    public function test_newly_connected_ad_account_defaults_to_zero_lifetime_spend_and_never_dummy_23491(): void
    {
        $newAccount = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'account_id' => 'act_1152299379439717',
            'name' => 'Kirtnix Official',
            'currency' => 'INR',
            'is_active' => true,
        ]);

        // Verify database default is 0.00 and NEVER 23491.00
        $this->assertEquals(0.00, (float) $newAccount->fresh()->lifetime_spend);
        $this->assertEquals(0.00, (float) $newAccount->fresh()->spend_limit);
        $this->assertEquals(0.00, (float) $newAccount->fresh()->balance);

        $client = Client::create([
            'company_name' => 'Kirtnix Fresh',
            'client_name' => 'Anurag',
            'kx_code' => 'KX-FRESH',
            'status' => 'active',
            'ad_account_id' => $newAccount->id,
        ]);
        $newAccount->update(['client_id' => $client->id]);

        $metrics = app(\App\Services\MetaSyncService::class)->getAdAccountMetrics($newAccount, true);

        $this->assertEquals(0.00, $metrics['spend_total']);
        $this->assertEquals(0.00, $metrics['lifetime_spend']);
        $this->assertEquals(0.00, $metrics['spend_today']);

        $response = $this->actingAs($this->user)->get(route('clients.show', $client->id));
        $response->assertOk();
        // Lifetime spend must render ₹0, not ₹23,491
        $response->assertSee('₹0');
        $response->assertDontSee('23,491');
        $response->assertDontSee('23491');
    }

    public function test_meta_sync_resolves_and_persists_business_object_from_graph_api_response(): void
    {
        \Illuminate\Support\Facades\Http::fake([
            'https://graph.facebook.com/*/me/adaccounts*' => \Illuminate\Support\Facades\Http::response([
                'data' => [
                    [
                        'id' => 'act_1152299379439717',
                        'account_id' => '1152299379439717',
                        'name' => 'Kirtnix Official',
                        'currency' => 'INR',
                        'account_status' => 1,
                        'amount_spent' => '0',
                        'business' => [
                            'id' => '998877665544',
                            'name' => 'Kirtnix Tech Holding BM',
                            'verification_status' => 'verified',
                        ],
                    ],
                ],
            ]),
        ]);

        $syncService = app(\App\Services\MetaSyncService::class);
        $syncedAccounts = $syncService->syncAdAccounts($this->connection);

        $this->assertCount(1, $syncedAccounts);
        $account = $syncedAccounts[0];

        $this->assertNotNull($account->meta_business_id);
        $business = MetaBusiness::find($account->meta_business_id);
        $this->assertNotNull($business);
        $this->assertEquals('998877665544', $business->business_id);
        $this->assertEquals('Kirtnix Tech Holding BM', $business->name);
        $this->assertEquals(0.00, (float) $account->lifetime_spend);
    }

    public function test_ad_account_lifetime_spend_isolation_between_different_accounts(): void
    {
        $accountA = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'account_id' => 'act_spending_aaa',
            'name' => 'Account A Spending',
            'currency' => 'INR',
            'lifetime_spend' => 45000.00,
            'is_active' => true,
        ]);

        $accountB = AdAccount::create([
            'meta_connection_id' => $this->connection->id,
            'account_id' => 'act_fresh_bbb',
            'name' => 'Account B Fresh',
            'currency' => 'INR',
            'lifetime_spend' => 0.00,
            'is_active' => true,
        ]);

        $clientA = Client::create([
            'company_name' => 'Client A',
            'client_name' => 'Client A',
            'kx_code' => 'KX-AAA',
            'status' => 'active',
            'ad_account_id' => $accountA->id,
        ]);
        $accountA->update(['client_id' => $clientA->id]);

        $clientB = Client::create([
            'company_name' => 'Client B',
            'client_name' => 'Client B',
            'kx_code' => 'KX-BBB',
            'status' => 'active',
            'ad_account_id' => $accountB->id,
        ]);
        $accountB->update(['client_id' => $clientB->id]);

        $metricsA = app(\App\Services\MetaSyncService::class)->getAdAccountMetrics($accountA, true);
        $metricsB = app(\App\Services\MetaSyncService::class)->getAdAccountMetrics($accountB, true);

        $this->assertEquals(45000.00, $metricsA['spend_total']);
        $this->assertEquals(0.00, $metricsB['spend_total']);

        $resA = $this->actingAs($this->user)->get(route('clients.show', $clientA->id));
        $resA->assertSee('₹45,000');

        $resB = $this->actingAs($this->user)->get(route('clients.show', $clientB->id));
        $resB->assertSee('₹0');
        $resB->assertDontSee('45,000');
    }
}

