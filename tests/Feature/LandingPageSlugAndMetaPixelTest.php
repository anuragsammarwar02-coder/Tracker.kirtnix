<?php

namespace Tests\Feature;

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

        // 1. Create first landing page
        $this->actingAs($user)->post(route('landing-pages.store_import'), [
            'client_id' => $client->id,
            'title' => 'kirtnix-digital',
            'slug' => 'kirtnix-digital',
            'import_type' => 'vercel',
            'vercel_project_name' => 'kirtnix-digital',
            'production_domain' => 'kirtnix-digital.vercel.app',
            'telegram_destination' => 'https://t.me/+G84Kwpa2V0yYTU1',
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
            'telegram_destination' => 'https://t.me/+G84Kwpa2V0yYTU1',
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

    public function test_native_page_can_also_be_recreated_after_deletion_with_same_slug()
    {
        $user = User::factory()->create();
        $client = Client::create([
            'client_name' => 'Anurag',
            'company_name' => 'Kirtnix Digital',
            'kx_code' => 'KX-001',
            'status' => 'active',
        ]);

        // 1. Create native page
        $this->actingAs($user)->post(route('landing-pages.store'), [
            'client_id' => $client->id,
            'title' => 'Forex Scalper',
            'slug' => 'forex-scalper',
            'template_type' => 'forex_focus',
            'brand_name' => 'Forex Scalper',
            'hero_heading' => 'Win More Trades',
            'primary_cta_text' => 'Join Now',
            'telegram_destination' => 'https://t.me/+test1234',
        ])->assertRedirect();

        $lp = LandingPage::where('slug', 'forex-scalper')->first();
        $this->assertNotNull($lp);

        // 2. Delete it
        $this->actingAs($user)->delete(route('landing-pages.destroy', $lp))->assertRedirect();

        // 3. Re-create native page with same slug
        $response = $this->actingAs($user)->post(route('landing-pages.store'), [
            'client_id' => $client->id,
            'title' => 'Forex Scalper 2',
            'slug' => 'forex-scalper',
            'template_type' => 'forex_focus',
            'brand_name' => 'Forex Scalper',
            'hero_heading' => 'Win More Trades',
            'primary_cta_text' => 'Join Now',
            'telegram_destination' => 'https://t.me/+test1234',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('landing_pages', [
            'slug' => 'forex-scalper',
            'title' => 'Forex Scalper 2',
        ]);
    }
}
