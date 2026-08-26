<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Campaign;
use App\Models\CampaignInsight;
use App\Models\Client;
use App\Models\LandingPage;
use App\Models\MetaBusiness;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientAdAccountAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MetaBusiness $metaBusiness;
    protected AdAccount $adAccountA;
    protected AdAccount $adAccountB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email' => 'admin@kirtnix.in']);

        $metaConnection = \App\Models\MetaConnection::create([
            'access_token' => 'EAAB_test_token',
            'token_type' => 'Bearer',
            'status' => 'active',
        ]);

        $this->metaBusiness = MetaBusiness::create([
            'meta_connection_id' => $metaConnection->id,
            'business_id' => 'biz_123456789',
            'name' => 'Kirtnix Media Agency',
        ]);

        $this->adAccountA = AdAccount::create([
            'meta_business_id' => $this->metaBusiness->id,
            'account_id' => 'act_111111111',
            'name' => 'Client A Ad Account',
            'currency' => 'INR',
            'status' => 'ACTIVE',
            'spend_limit' => 50000.00,
            'lifetime_spend' => 12500.00,
        ]);

        $this->adAccountB = AdAccount::create([
            'meta_business_id' => $this->metaBusiness->id,
            'account_id' => 'act_222222222',
            'name' => 'Client B Ad Account',
            'currency' => 'USD',
            'status' => 'ACTIVE',
            'spend_limit' => 2000.00,
            'lifetime_spend' => 450.00,
        ]);
    }

    public function test_can_create_client_with_assigned_ad_account(): void
    {
        $response = $this->actingAs($this->user)->post(route('clients.store'), [
            'kx_code' => 'KX-101',
            'company_name' => 'Alpha Forex Academy',
            'client_name' => 'Rajesh Sharma',
            'industry' => 'Trading',
            'email' => 'alpha@forex.in',
            'status' => 'active',
            'ad_account_id' => $this->adAccountA->id,
        ]);

        $response->assertRedirect();

        $client = Client::where('kx_code', 'KX-101')->first();
        $this->assertNotNull($client);
        $this->assertEquals($this->adAccountA->id, $client->ad_account_id);
        $this->assertTrue($client->meta_ads_connected);

        // Bi-directional association
        $this->adAccountA->refresh();
        $this->assertEquals($client->id, $this->adAccountA->client_id);
    }

    public function test_can_assign_and_change_ad_account_via_quick_endpoint(): void
    {
        $client = Client::create([
            'kx_code' => 'KX-102',
            'company_name' => 'Beta Wealth Advisory',
            'client_name' => 'Anita Desai',
            'status' => 'active',
        ]);

        $this->assertNull($client->ad_account_id);

        // Assign Account B
        $response = $this->actingAs($this->user)->post(route('clients.assign_ad_account', $client), [
            'ad_account_id' => $this->adAccountB->id,
        ]);

        $response->assertRedirect();
        $client->refresh();
        $this->assertEquals($this->adAccountB->id, $client->ad_account_id);
        $this->assertTrue($client->meta_ads_connected);

        // Reassign to Account A
        $this->actingAs($this->user)->post(route('clients.assign_ad_account', $client), [
            'ad_account_id' => $this->adAccountA->id,
        ]);

        $client->refresh();
        $this->assertEquals($this->adAccountA->id, $client->ad_account_id);

        // Unassign Ad Account
        $this->actingAs($this->user)->post(route('clients.assign_ad_account', $client), [
            'ad_account_id' => '',
        ]);

        $client->refresh();
        $this->assertNull($client->ad_account_id);
        $this->assertFalse($client->meta_ads_connected);
    }

    public function test_client_dashboard_scopes_meta_data_to_assigned_account_only(): void
    {
        $clientA = Client::create([
            'kx_code' => 'KX-103',
            'company_name' => 'Client A Corp',
            'client_name' => 'User A',
            'status' => 'active',
            'ad_account_id' => $this->adAccountA->id,
        ]);

        $clientB = Client::create([
            'kx_code' => 'KX-104',
            'company_name' => 'Client B Corp',
            'client_name' => 'User B',
            'status' => 'active',
            'ad_account_id' => $this->adAccountB->id,
        ]);

        // Create campaigns under Ad Account A
        $campaignA = Campaign::create([
            'client_id' => $clientA->id,
            'ad_account_id' => $this->adAccountA->id,
            'campaign_id' => 'cmp_aaa111',
            'name' => 'Campaign A',
            'slug' => 'campaign-a',
            'status' => 'ACTIVE',
            'spend' => 8500.00,
            'reach' => 45000,
            'impressions' => 78000,
            'clicks' => 3200,
        ]);

        // Create campaigns under Ad Account B
        $campaignB = Campaign::create([
            'client_id' => $clientB->id,
            'ad_account_id' => $this->adAccountB->id,
            'campaign_id' => 'cmp_bbb222',
            'name' => 'Campaign B',
            'slug' => 'campaign-b',
            'status' => 'ACTIVE',
            'spend' => 300.00,
            'reach' => 2100,
            'impressions' => 4300,
            'clicks' => 150,
        ]);

        // Client A Overview
        $responseA = $this->actingAs($this->user)->get(route('clients.show', $clientA));
        $responseA->assertOk();
        $responseA->assertSee('Client A Ad Account');
        $responseA->assertSee('act_111111111');
        $responseA->assertSee('8,500');
        $responseA->assertSee('45,000');
        // Does not show Client B's campaign metrics
        $responseA->assertDontSee('cmp_bbb222');

        // Client B Overview
        $responseB = $this->actingAs($this->user)->get(route('clients.show', $clientB));
        $responseB->assertOk();
        $responseB->assertSee('Client B Ad Account');
        $responseB->assertSee('act_222222222');
        $responseB->assertSee('300');
        $responseB->assertSee('2,100');
        // Does not show Client A's campaign metrics
        $responseB->assertDontSee('cmp_aaa111');
    }
}
