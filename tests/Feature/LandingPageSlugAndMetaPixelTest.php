<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use App\Models\Client;
use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageSlugAndMetaPixelTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_recreate_or_reimport_landing_page_after_deletion_with_same_slug()
    {
        $user = User::factory()->create();
        $client = Client::create([
            'client_name' => 'Anurag',
            'company_name' => 'Kirtnix Digital',
            'kx_code' => 'KX-001',
            'status' => 'active',
        ]);

        // 1. Create first landing page without manual telegram link
        $this->actingAs($user)->post(route('landing-pages.store_import'), [
            'client_id' => $client->id,
            'title' => 'kirtnix-digital',
            'slug' => 'kirtnix-digital',
            'import_type' => 'vercel',
            'vercel_project_name' => 'kirtnix-digital',
            'production_domain' => 'kirtnix-digital.vercel.app',
            'meta_pixel_id' => '1130260856232291',
            'meta_access_token' => 'EAAB_test_token',
        ])->assertRedirect();

        $lp = LandingPage::where('slug', 'kirtnix-digital')->first();
        $this->assertNotNull($lp);
        $this->assertEquals('1130260856232291', $lp->meta_pixel_id);
        $this->assertEquals('EAAB_test_token', $lp->meta_access_token);

        // 2. Delete the landing page
        $this->actingAs($user)->delete(route('landing-pages.destroy', $lp))
            ->assertRedirect(route('landing-pages.index'));

        // 3. Re-import landing page with the EXACT same slug
        $response = $this->actingAs($user)->post(route('landing-pages.store_import'), [
            'client_id' => $client->id,
            'title' => 'kirtnix-digital',
            'slug' => 'kirtnix-digital',
            'import_type' => 'vercel',
            'vercel_project_name' => 'kirtnix-digital',
            'production_domain' => 'kirtnix-digital.vercel.app',
            'meta_pixel_id' => '9988776655443322',
            'meta_access_token' => 'EAAB_new_token',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $newLp = LandingPage::where('slug', 'kirtnix-digital')->first();
        $this->assertNotNull($newLp);
        $this->assertEquals('9988776655443322', $newLp->meta_pixel_id);
        $this->assertEquals('EAAB_new_token', $newLp->meta_access_token);
    }

    public function test_can_update_meta_config_directly_on_imported_page()
    {
        $user = User::factory()->create();
        $client = Client::create([
            'client_name' => 'Anurag',
            'company_name' => 'Kirtnix Digital',
            'kx_code' => 'KX-001',
            'status' => 'active',
        ]);

        $lp = LandingPage::create([
            'client_id' => $client->id,
            'title' => 'kirtnix-digital',
            'slug' => 'kirtnix-digital',
            'page_source' => 'vercel',
            'template_type' => 'custom',
            'brand_name' => 'Kirtnix Digital',
            'primary_cta_text' => 'Join Now',
            'telegram_destination' => 'https://t.me/kirtnix',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('landing-pages.update_meta_config', $lp), [
            'meta_pixel_id' => '1018611380802707',
            'meta_access_token' => 'EAAB_test_system_user_token_12345',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $lp->refresh();
        $this->assertEquals('1018611380802707', $lp->meta_pixel_id);
        $this->assertEquals('EAAB_test_system_user_token_12345', $lp->meta_access_token);
    }

    public function test_can_store_and_delete_ad_account_without_fake_fixtures()
    {
        $user = User::factory()->create();

        // 1. Add ad account
        $this->actingAs($user)->post(route('meta.ad_accounts.store'), [
            'name' => 'My Custom Ad Account',
            'account_id' => '1018611380802707',
            'currency' => 'INR',
            'status' => 'Active',
        ])->assertRedirect();

        $acc = AdAccount::where('account_id', 'act_1018611380802707')->first();
        $this->assertNotNull($acc);
        $this->assertEquals('My Custom Ad Account', $acc->name);

        // 2. Delete ad account
        $this->actingAs($user)->delete(route('meta.ad_accounts.destroy', $acc))
            ->assertRedirect();

        $this->assertDatabaseMissing('ad_accounts', [
            'account_id' => 'act_1018611380802707',
        ]);
    }
}
