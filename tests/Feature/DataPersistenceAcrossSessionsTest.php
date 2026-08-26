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
}
