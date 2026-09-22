<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\MetaBusiness;
use App\Models\MetaConnection;
use App\Models\Setting;
use App\Models\User;
use App\Services\MetaSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaMultiAccountIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'email' => 'admin@kirtnix.in',
            'role' => 'super_admin',
        ]);
    }

    public function test_can_connect_multiple_independent_facebook_accounts(): void
    {
        Http::fake(function (ClientRequest $request) {
            $url = $request->url();
            $token = $request['access_token'] ?? '';

            if (str_contains($url, '/me/businesses')) {
                if ($token === 'EAAB_TOKEN_B') {
                    return Http::response(['data' => [
                        ['id' => 'biz_bbb_02', 'name' => 'Partner Portfolio B', 'verification_status' => 'verified']
                    ]], 200);
                }
                return Http::response(['data' => [
                    ['id' => 'biz_aaa_01', 'name' => 'Agency Portfolio A', 'verification_status' => 'verified']
                ]], 200);
            }

            if (str_contains($url, '/me/adaccounts')) {
                if ($token === 'EAAB_TOKEN_B') {
                    return Http::response(['data' => [
                        ['id' => 'act_333333333', 'account_id' => '333333333', 'name' => 'Partner Ad Account B1', 'currency' => 'INR', 'account_status' => 1, 'amount_spent' => 800000],
                    ]], 200);
                }
                return Http::response(['data' => [
                    ['id' => 'act_111111111', 'account_id' => '111111111', 'name' => 'Client Ad Account A1', 'currency' => 'INR', 'account_status' => 1, 'amount_spent' => 500000],
                    ['id' => 'act_222222222', 'account_id' => '222222222', 'name' => 'Client Ad Account A2', 'currency' => 'USD', 'account_status' => 1, 'amount_spent' => 200000],
                ]], 200);
            }

            if (str_ends_with($url, '/me') || str_contains($url, '/me?')) {
                if ($token === 'EAAB_TOKEN_B') {
                    return Http::response([
                        'id' => '200000000000002',
                        'name' => 'Partner Facebook (Profile B)',
                        'email' => 'partner.b@example.com',
                    ], 200);
                }
                return Http::response([
                    'id' => '100000000000001',
                    'name' => 'Anurag Sammarwar (Profile A)',
                    'email' => 'anurag.a@example.com',
                ], 200);
            }

            return Http::response(['data' => []], 200);
        });

        $service = app(MetaSyncService::class);

        // 1. Connect Facebook Account A
        $connA = $service->connectAccessToken('EAAB_TOKEN_A', $this->user->id);

        $this->assertEquals('100000000000001', $connA->facebook_user_id);
        $this->assertEquals('Anurag Sammarwar (Profile A)', $connA->facebook_name);
        $this->assertDatabaseHas('meta_connections', ['facebook_user_id' => '100000000000001']);
        $this->assertDatabaseHas('ad_accounts', ['account_id' => 'act_111111111']);
        $this->assertDatabaseHas('ad_accounts', ['account_id' => 'act_222222222']);

        // 2. Connect Facebook Account B
        $connB = $service->connectAccessToken('EAAB_TOKEN_B', $this->user->id);

        // 3. Verify BOTH connections exist simultaneously
        $this->assertEquals(2, MetaConnection::count());
        $this->assertDatabaseHas('meta_connections', ['facebook_user_id' => '100000000000001']);
        $this->assertDatabaseHas('meta_connections', ['facebook_user_id' => '200000000000002']);

        // 4. Verify ad accounts from both connections are present
        $this->assertDatabaseHas('ad_accounts', ['account_id' => 'act_111111111', 'meta_connection_id' => $connA->id]);
        $this->assertDatabaseHas('ad_accounts', ['account_id' => 'act_222222222', 'meta_connection_id' => $connA->id]);
        $this->assertDatabaseHas('ad_accounts', ['account_id' => 'act_333333333', 'meta_connection_id' => $connB->id]);

        // 5. Settings page renders both Facebook profiles and all ad accounts
        $response = $this->actingAs($this->user)->get(route('settings.index', ['tab' => 'meta']));
        $response->assertStatus(200);
        $response->assertSee('Anurag Sammarwar (Profile A)');
        $response->assertSee('Partner Facebook (Profile B)');
        $response->assertSee('Client Ad Account A1');
        $response->assertSee('Partner Ad Account B1');
    }

    public function test_disconnecting_account_a_leaves_account_b_fully_intact(): void
    {
        $connA = MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_111',
            'facebook_name' => 'Account A User',
            'access_token' => 'TOKEN_A',
            'status' => 'active',
        ]);
        $accA = AdAccount::create([
            'meta_connection_id' => $connA->id,
            'account_id' => 'act_111',
            'name' => 'Ad Account A',
            'currency' => 'INR',
            'status' => 'Active',
            'is_active' => true,
        ]);

        $connB = MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_222',
            'facebook_name' => 'Account B User',
            'access_token' => 'TOKEN_B',
            'status' => 'active',
        ]);
        $accB = AdAccount::create([
            'meta_connection_id' => $connB->id,
            'account_id' => 'act_222',
            'name' => 'Ad Account B',
            'currency' => 'INR',
            'status' => 'Active',
            'is_active' => true,
        ]);

        $this->assertEquals(2, MetaConnection::count());
        $this->assertEquals(2, AdAccount::count());

        // Disconnect Account A specifically
        $response = $this->actingAs($this->user)->delete(route('meta.connections.destroy', $connA->id));
        $response->assertRedirect(route('settings.index', ['tab' => 'meta']));
        $response->assertSessionHas('info');

        // Connection A is gone, Connection B and Account B remain fully intact
        $this->assertDatabaseMissing('meta_connections', ['id' => $connA->id]);
        $this->assertDatabaseMissing('ad_accounts', ['id' => $accA->id]);

        $this->assertDatabaseHas('meta_connections', ['id' => $connB->id, 'facebook_name' => 'Account B User']);
        $this->assertDatabaseHas('ad_accounts', ['id' => $accB->id, 'account_id' => 'act_222']);
    }

    public function test_ad_account_deduplication_across_shared_access(): void
    {
        Http::fake(function (ClientRequest $request) {
            $url = $request->url();
            $token = $request['access_token'] ?? '';

            if (str_contains($url, '/me/adaccounts')) {
                if ($token === 'TOKEN_B') {
                    return Http::response(['data' => [
                        ['id' => 'act_999999999', 'account_id' => '999999999', 'name' => 'Shared Brand Ad Account (Updated)', 'currency' => 'INR', 'account_status' => 1, 'amount_spent' => 120000],
                    ]], 200);
                }
                return Http::response(['data' => [
                    ['id' => 'act_999999999', 'account_id' => '999999999', 'name' => 'Shared Brand Ad Account', 'currency' => 'INR', 'account_status' => 1, 'amount_spent' => 100000],
                ]], 200);
            }
            if (str_contains($url, '/v19.0/me')) {
                return Http::response(['id' => 'fb_shared_1', 'name' => 'Account A'], 200);
            }
            return Http::response(['data' => []], 200);
        });

        $connA = MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_shared_1',
            'facebook_name' => 'Account A',
            'access_token' => 'TOKEN_A',
            'status' => 'active',
        ]);

        $service = app(MetaSyncService::class);
        $service->syncAdAccounts($connA);

        $this->assertEquals(1, AdAccount::where('account_id', 'act_999999999')->count());

        // Account B also has access to the same Shared Ad Account
        $connB = MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_shared_2',
            'facebook_name' => 'Account B',
            'access_token' => 'TOKEN_B',
            'status' => 'active',
        ]);

        $service->syncAdAccounts($connB);

        // Deduplication check: EXACTLY 1 logical record exists for act_999999999
        $this->assertEquals(1, AdAccount::where('account_id', 'act_999999999')->count());
        $account = AdAccount::where('account_id', 'act_999999999')->first();
        $this->assertEquals('Shared Brand Ad Account (Updated)', $account->name);
        $this->assertEquals(1200.00, $account->lifetime_spend);
    }

    public function test_live_test_connection_endpoint_validates_token(): void
    {
        Http::fake([
            'https://graph.facebook.com/*/me/businesses*' => Http::response([
                'data' => [
                    ['id' => 'biz_001', 'name' => 'Main Agency BM', 'verification_status' => 'verified'],
                ],
            ], 200),
            'https://graph.facebook.com/*/me/adaccounts*' => Http::response([
                'data' => [
                    ['id' => 'act_101', 'account_id' => '101', 'name' => 'Agency Live Ads', 'currency' => 'INR', 'account_status' => 1, 'amount_spent' => 50000],
                ],
            ], 200),
            'https://graph.facebook.com/*/me*' => Http::response([
                'id' => '100099887766',
                'name' => 'Kirtnix Verified Agency',
                'email' => 'agency@kirtnix.in',
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('meta.test_connection'), [
            'access_token' => 'EAAB_VALID_LIVE_TOKEN',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'valid' => true,
            'user_id' => '100099887766',
            'name' => 'Kirtnix Verified Agency',
            'businesses_count' => 1,
            'ad_accounts_count' => 1,
        ]);
    }

    public function test_select_connection_sets_active_connection(): void
    {
        $connA = MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_select_1',
            'facebook_name' => 'Profile 1',
            'access_token' => 'TOKEN_1',
            'status' => 'active',
        ]);
        $connB = MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_select_2',
            'facebook_name' => 'Profile 2',
            'access_token' => 'TOKEN_2',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->post(route('meta.select_connection', $connB->id));
        $response->assertRedirect();

        $this->assertEquals((string) $connB->id, Setting::get('active_meta_connection_id'));
    }

    public function test_ad_account_sync_traverses_multiple_pages_of_pagination(): void
    {
        $conn = MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_page_user',
            'facebook_name' => 'Paginated Agency Account',
            'access_token' => 'PAGE_TOKEN',
            'status' => 'active',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/me/adaccounts?*page=2*' => Http::response([
                'data' => [
                    ['id' => 'act_100000000002', 'account_id' => '100000000002', 'name' => 'Page 2 Ad Account', 'currency' => 'INR', 'account_status' => 1, 'amount_spent' => 20000],
                ],
                'paging' => ['next' => null],
            ], 200),
            'https://graph.facebook.com/*/me/adaccounts*' => Http::response([
                'data' => [
                    ['id' => 'act_100000000001', 'account_id' => '100000000001', 'name' => 'Page 1 Ad Account', 'currency' => 'INR', 'account_status' => 1, 'amount_spent' => 10000],
                ],
                'paging' => [
                    'next' => 'https://graph.facebook.com/v20.0/me/adaccounts?access_token=PAGE_TOKEN&page=2',
                ],
            ], 200),
            'https://graph.facebook.com/*/me/assigned_ad_accounts*' => Http::response(['data' => []], 200),
            'https://graph.facebook.com/*/me/businesses*' => Http::response(['data' => []], 200),
            'https://graph.facebook.com/*/me/assigned_businesses*' => Http::response(['data' => []], 200),
        ]);

        $service = app(MetaSyncService::class);
        $accounts = $service->syncAdAccounts($conn);

        $accountIds = collect($accounts)->pluck('account_id')->toArray();
        $this->assertContains('act_100000000001', $accountIds);
        $this->assertContains('act_100000000002', $accountIds);
        $this->assertEquals(2, AdAccount::where('meta_connection_id', $conn->id)->count());
    }

    public function test_quick_fetch_endpoint_fetches_and_persists_single_ad_account(): void
    {
        MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_quick_user',
            'facebook_name' => 'Quick Fetch Profile',
            'access_token' => 'QUICK_TOKEN',
            'status' => 'active',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/act_1049354661209537/campaigns*' => Http::response(['data' => []], 200),
            'https://graph.facebook.com/*/act_1049354661209537*' => Http::response([
                'id' => 'act_1049354661209537',
                'account_id' => '1049354661209537',
                'name' => 'AK001 - Radhika Account',
                'currency' => 'INR',
                'account_status' => 1,
                'spend_cap' => 100000,
                'balance' => 0,
                'amount_spent' => 500000,
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->postJson(route('meta.ad_accounts.quick_fetch'), [
            'account_id' => '1049354661209537',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'ad_account' => [
                'name' => 'AK001 - Radhika Account',
                'account_id' => 'act_1049354661209537',
                'currency' => 'INR',
            ],
        ]);

        $this->assertDatabaseHas('ad_accounts', [
            'account_id' => 'act_1049354661209537',
            'name' => 'AK001 - Radhika Account',
        ]);
    }

    public function test_client_creation_with_custom_ad_account_id_fetches_and_links(): void
    {
        MetaConnection::create([
            'user_id' => $this->user->id,
            'facebook_user_id' => 'fb_client_link_user',
            'facebook_name' => 'Link Profile',
            'access_token' => 'LINK_TOKEN',
            'status' => 'active',
        ]);

        Http::fake([
            'https://graph.facebook.com/*/act_9988776655/campaigns*' => Http::response(['data' => []], 200),
            'https://graph.facebook.com/*/act_9988776655*' => Http::response([
                'id' => 'act_9988776655',
                'account_id' => '9988776655',
                'name' => 'Brand Custom Ad Account',
                'currency' => 'INR',
                'account_status' => 1,
                'amount_spent' => 350000,
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->post(route('clients.store'), [
            'company_name' => 'Test Custom Link Client',
            'client_name' => 'Rajesh Sharma',
            'category' => 'Stock Market & Options Trading',
            'custom_ad_account_id' => '9988776655',
            'status' => 'active',
        ]);

        $response->assertRedirect();

        $client = \App\Models\Client::where('company_name', 'Test Custom Link Client')->first();
        $this->assertNotNull($client);
        $this->assertNotNull($client->ad_account_id);

        $adAcc = AdAccount::find($client->ad_account_id);
        $this->assertEquals('act_9988776655', $adAcc->account_id);
        $this->assertEquals('Brand Custom Ad Account', $adAcc->name);
    }
}
