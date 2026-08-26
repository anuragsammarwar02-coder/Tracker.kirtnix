<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LandingPage;
use App\Models\MetaConnection;
use App\Models\Setting;
use App\Models\User;
use App\Services\VercelService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DataPersistenceAcrossSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@kirtnix.in')->firstOrFail();
    }

    /**
     * TEST 1: Client persistence across logout and login.
     */
    public function test_client_persists_across_logout_and_login_sessions(): void
    {
        // Login
        $this->actingAs($this->admin);

        // Create client
        $kxCode = 'KX-TST-901';
        $client = Client::create([
            'kx_code' => $kxCode,
            'company_name' => 'Acme Performance Corp',
            'client_name' => 'Alice Smith',
            'industry' => 'Trading',
            'status' => 'active',
            'meta_ads_connected' => true,
            'monthly_budget' => 6000.00,
        ]);

        // Verify exists in database
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'kx_code' => $kxCode,
            'company_name' => 'Acme Performance Corp',
        ]);

        // Logout
        $this->post(route('logout'));
        $this->assertGuest();

        // Login again as the same admin account
        $this->post(route('login.submit'), [
            'email' => 'admin@kirtnix.in',
            'password' => 'Kirti#13',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->admin);

        // Confirm client still exists and is accessible
        $response = $this->get(route('clients.index'));
        $response->assertStatus(200);
        $response->assertSee('Acme Performance Corp');
        $response->assertSee($kxCode);
    }

    /**
     * TEST 2: Landing page persistence across logout and login.
     */
    public function test_landing_page_persists_across_logout_and_login_sessions(): void
    {
        $this->actingAs($this->admin);

        $client = Client::firstOrFail();
        $slug = 'scalping-mastery-tg';

        $lp = LandingPage::create([
            'client_id' => $client->id,
            'title' => 'Scalping Mastery Hub',
            'slug' => $slug,
            'template_type' => 'forex_focus',
            'brand_name' => 'Scalping Mastery',
            'hero_heading' => 'High Probability Setup Tracking',
            'primary_cta_text' => 'Join VIP Channel',
            'telegram_destination' => 'https://t.me/scalpingvip',
            'page_source' => 'native',
            'tracking_token' => 'kx_tok_' . Str::random(8),
            'deployment_status' => 'published',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('landing_pages', [
            'id' => $lp->id,
            'slug' => $slug,
        ]);

        // Logout & Login
        $this->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.submit'), [
            'email' => 'admin@kirtnix.in',
            'password' => 'Kirti#13',
        ])->assertRedirect(route('dashboard'));

        // Confirm landing page still exists in index
        $response = $this->get(route('landing-pages.index'));
        $response->assertStatus(200);
        $response->assertSee('Scalping Mastery Hub');
    }

    /**
     * TEST 3: Vercel integration token persistence across logout and login.
     */
    public function test_vercel_integration_persists_across_logout_and_login_sessions(): void
    {
        $this->actingAs($this->admin);

        $vercelService = new VercelService();
        $testToken = 'vercel_token_secret_xyz123';
        $vercelService->setToken($testToken);

        $this->assertEquals($testToken, $vercelService->getToken());
        $this->assertDatabaseHas('settings', [
            'key' => 'vercel_token',
            'value' => $testToken,
        ]);

        // Logout & Login
        $this->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.submit'), [
            'email' => 'admin@kirtnix.in',
            'password' => 'Kirti#13',
        ])->assertRedirect(route('dashboard'));

        // Confirm token is retained
        $this->assertEquals($testToken, (new VercelService())->getToken());

        $importRes = $this->get(route('landing-pages.import', ['tab' => 'vercel']));
        $importRes->assertStatus(200);
        $importRes->assertSee('Vercel Connected');
    }

    /**
     * TEST 4: Meta integration credentials persist across logout and login.
     */
    public function test_meta_integration_persists_across_logout_and_login_sessions(): void
    {
        $this->actingAs($this->admin);

        $metaToken = 'EAAX_production_test_meta_token_789';
        Setting::set('meta_system_user_token', $metaToken, 'meta');

        $metaConn = MetaConnection::create([
            'user_id' => $this->admin->id,
            'facebook_user_id' => 'fb_admin_45678',
            'facebook_name' => 'KirtniX Performance Agency',
            'access_token' => $metaToken,
            'status' => 'active',
            'sync_status' => 'completed',
            'last_sync_at' => now(),
        ]);

        $this->assertDatabaseHas('meta_connections', [
            'id' => $metaConn->id,
            'facebook_name' => 'KirtniX Performance Agency',
        ]);

        // Logout & Login
        $this->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.submit'), [
            'email' => 'admin@kirtnix.in',
            'password' => 'Kirti#13',
        ])->assertRedirect(route('dashboard'));

        // Confirm Meta connection remains connected
        $settingsRes = $this->get(route('settings.index', ['tab' => 'meta']));
        $settingsRes->assertStatus(200);
        $settingsRes->assertSee('Connected');
        $settingsRes->assertSee('KirtniX Performance Agency');
    }

    /**
     * TEST 5: Telegram Bot and Channel persist across logout and login.
     */
    public function test_telegram_bot_and_channel_persist_across_sessions(): void
    {
        $this->actingAs($this->admin);

        $client = Client::create([
            'kx_code' => 'KX-TG-01',
            'company_name' => 'Forex Signal VIP',
            'client_name' => 'Vikash Trader',
            'industry' => 'Trading',
            'status' => 'active',
        ]);

        $bot = \App\Models\TelegramBot::create([
            'client_id' => $client->id,
            'name' => 'KirtniX Test Bot',
            'username' => 'kirtnixtest_bot',
            'bot_token' => '999888777:AAH_test_bot_token',
            'webhook_secret' => 'whsec_test_' . Str::random(16),
            'is_active' => true,
        ]);

        $channel = \App\Models\TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'client_id' => $client->id,
            'telegram_chat_id' => '-1001977904498',
            'title' => 'TRADE WITH VIKASH VIP',
            'username' => 'tradewithvikashvip',
            'type' => 'channel',
            'member_count' => 13711,
            'is_bot_admin' => true,
        ]);

        $this->assertDatabaseHas('telegram_bots', ['id' => $bot->id, 'username' => 'kirtnixtest_bot']);
        $this->assertDatabaseHas('telegram_channels', ['id' => $channel->id, 'telegram_chat_id' => '-1001977904498']);

        // Logout & Login
        $this->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.submit'), [
            'email' => 'admin@kirtnix.in',
            'password' => 'Kirti#13',
        ])->assertRedirect(route('dashboard'));

        // Confirm bot and channel are visible in Telegram tracking screen
        $tgRes = $this->get(route('telegram.index', ['bot_id' => $bot->id]));
        $tgRes->assertStatus(200);
        $tgRes->assertSee('kirtnixtest_bot');
        $tgRes->assertSee('TRADE WITH VIKASH VIP');
    }

    /**
     * TEST 6: Client to Meta Ad Account assignment persists across sessions.
     */
    public function test_client_meta_ad_account_assignment_persists_across_sessions(): void
    {
        $this->actingAs($this->admin);

        $metaConn = MetaConnection::create([
            'user_id' => $this->admin->id,
            'facebook_user_id' => 'fb_admin_45678',
            'facebook_name' => 'KirtniX Performance Agency',
            'access_token' => 'EAAX_test_token',
            'status' => 'active',
        ]);

        $adAccount = \App\Models\AdAccount::create([
            'meta_connection_id' => $metaConn->id,
            'account_id' => 'act_9988776655',
            'name' => 'Agency Main Ad Account',
            'currency' => 'INR',
            'status' => 'ACTIVE',
        ]);

        $client = Client::create([
            'kx_code' => 'KX-META-02',
            'company_name' => 'Delta Scalpers',
            'client_name' => 'John Scalper',
            'industry' => 'Trading',
            'status' => 'active',
            'ad_account_id' => $adAccount->id,
            'meta_ads_connected' => true,
        ]);

        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'ad_account_id' => $adAccount->id,
        ]);

        // Logout & Login
        $this->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.submit'), [
            'email' => 'admin@kirtnix.in',
            'password' => 'Kirti#13',
        ])->assertRedirect(route('dashboard'));

        // Verify assignment is preserved
        $refreshedClient = Client::find($client->id);
        $this->assertEquals($adAccount->id, $refreshedClient->ad_account_id);
        $this->assertEquals('Agency Main Ad Account', $refreshedClient->adAccount?->name);
    }

    /**
     * TEST 7: Manually deleted records STAY deleted and are NEVER auto-recreated.
     */
    public function test_user_deleted_records_stay_deleted_and_are_never_auto_recreated(): void
    {
        $this->actingAs($this->admin);

        // 1. Create a client and landing page
        $client = Client::create([
            'kx_code' => 'KX-DEL-01',
            'company_name' => 'To Be Deleted Corp',
            'client_name' => 'Bob Test',
            'industry' => 'Trading',
            'status' => 'active',
        ]);

        $lp = LandingPage::create([
            'client_id' => $client->id,
            'title' => 'Page To Be Deleted',
            'slug' => 'page-to-be-deleted',
            'template_type' => 'forex_focus',
            'brand_name' => 'To Be Deleted',
            'page_source' => 'native',
            'tracking_token' => 'kx_tok_del_' . Str::random(6),
            'deployment_status' => 'published',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('clients', ['id' => $client->id]);
        $this->assertDatabaseHas('landing_pages', ['id' => $lp->id]);

        // 2. Admin explicitly deletes the landing page and client
        $lp->forceDelete();
        $client->forceDelete();

        $this->assertDatabaseMissing('landing_pages', ['id' => $lp->id]);
        $this->assertDatabaseMissing('clients', ['id' => $client->id]);

        // 3. Logout & Login again
        $this->post(route('logout'));
        $this->assertGuest();

        $this->post(route('login.submit'), [
            'email' => 'admin@kirtnix.in',
            'password' => 'Kirti#13',
        ])->assertRedirect(route('dashboard'));

        // 4. Confirm deleted records are STILL deleted (never recreated)
        $this->assertDatabaseMissing('landing_pages', ['slug' => 'page-to-be-deleted']);
        $this->assertDatabaseMissing('clients', ['kx_code' => 'KX-DEL-01']);
    }

    /**
     * TEST 8: db:backup command creates a verified backup.
     */
    public function test_db_backup_and_persistence_commands(): void
    {
        $this->actingAs($this->admin);

        // Snapshot command
        $this->artisan('db:persistence-check --snapshot')
            ->assertExitCode(0);

        $this->assertFileExists(storage_path('app/backups/persistence-snapshot.json'));

        // Verify command
        $this->artisan('db:persistence-check --verify')
            ->assertExitCode(0);
    }
}

