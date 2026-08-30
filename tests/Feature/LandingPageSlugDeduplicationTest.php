<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\LandingPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageSlugDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;
    protected Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->client = Client::create([
            'client_name' => 'Kirtnix Global',
            'company_name' => 'Kirtnix Global',
            'contact_name' => 'Anurag Sammarwar',
            'email' => 'global@kirtnix.in',
            'status' => 'active',
            'kx_code' => 'KX-GLOBAL-1',
        ]);
        $this->campaign = Campaign::create([
            'client_id' => $this->client->id,
            'name' => 'Meta Scaling Q3',
            'slug' => 'meta-scaling-q3',
            'utm_source' => 'meta',
            'utm_campaign' => 'q3_scale',
            'status' => 'active',
        ]);
    }

    public function test_creating_multiple_pages_with_same_slug_auto_deduplicates(): void
    {
        // 1. Create first page with slug 'kirtnix'
        $res1 = $this->actingAs($this->user)->post(route('landing-pages.store'), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Kirtnix Landing Page 1',
            'slug' => 'kirtnix',
            'brand_name' => 'Kirtnix Brand',
            'telegram_destination' => 'https://t.me/kirtnix_vip',
            'template_type' => 'visual_builder',
        ]);
        $res1->assertRedirect();
        $page1 = LandingPage::where('title', 'Kirtnix Landing Page 1')->first();
        $this->assertNotNull($page1);
        $this->assertEquals('kirtnix', $page1->slug);

        // 2. Create second page with exact same slug 'kirtnix'
        $res2 = $this->actingAs($this->user)->post(route('landing-pages.store'), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Kirtnix Landing Page 2',
            'slug' => 'kirtnix',
            'brand_name' => 'Kirtnix Brand',
            'telegram_destination' => 'https://t.me/kirtnix_vip',
            'template_type' => 'visual_builder',
        ]);
        $res2->assertRedirect();
        $page2 = LandingPage::where('title', 'Kirtnix Landing Page 2')->first();
        $this->assertNotNull($page2);
        $this->assertEquals('kirtnix-2', $page2->slug);

        // 3. Create third page with exact same slug 'kirtnix'
        $res3 = $this->actingAs($this->user)->post(route('landing-pages.store'), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Kirtnix Landing Page 3',
            'slug' => 'kirtnix',
            'brand_name' => 'Kirtnix Brand',
            'telegram_destination' => 'https://t.me/kirtnix_vip',
            'template_type' => 'visual_builder',
        ]);
        $res3->assertRedirect();
        $page3 = LandingPage::where('title', 'Kirtnix Landing Page 3')->first();
        $this->assertNotNull($page3);
        $this->assertEquals('kirtnix-3', $page3->slug);

        // 4. Create fourth page with 'Kirtnix' (capitalized and with spaces) -> auto sanitized to 'kirtnix-4'
        $res4 = $this->actingAs($this->user)->post(route('landing-pages.store'), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Kirtnix Landing Page 4',
            'slug' => '  Kirtnix  ',
            'brand_name' => 'Kirtnix Brand',
            'telegram_destination' => 'https://t.me/kirtnix_vip',
            'template_type' => 'visual_builder',
        ]);
        $res4->assertRedirect();
        $page4 = LandingPage::where('title', 'Kirtnix Landing Page 4')->first();
        $this->assertNotNull($page4);
        $this->assertEquals('kirtnix-4', $page4->slug);

        // Verify all 4 pages are active and uniquely accessible at /lp/{slug}
        $this->get('/lp/' . $page1->slug)->assertStatus(200);
        $this->get('/lp/' . $page2->slug)->assertStatus(200);
        $this->get('/lp/' . $page3->slug)->assertStatus(200);
        $this->get('/lp/' . $page4->slug)->assertStatus(200);
    }

    public function test_empty_or_whitespace_slug_generates_from_title(): void
    {
        $res = $this->actingAs($this->user)->post(route('landing-pages.store'), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'VIP Trading Signals Official',
            'slug' => '',
            'brand_name' => 'VIP Official',
            'telegram_destination' => 'https://t.me/kirtnix_vip',
            'template_type' => 'visual_builder',
        ]);

        $res->assertRedirect();
        $page = LandingPage::where('title', 'VIP Trading Signals Official')->first();
        $this->assertNotNull($page);
        $this->assertEquals('vip-trading-signals-official', $page->slug);
    }

    public function test_edit_allows_own_slug_and_resolves_conflict_with_others(): void
    {
        $pageA = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Alpha Page',
            'slug' => 'alpha-page',
            'page_source' => 'native',
            'template_type' => 'visual_builder',
            'brand_name' => 'Alpha Brand',
            'primary_cta_text' => 'Join Telegram',
            'telegram_destination' => 'https://t.me/alpha',
            'tracking_token' => 'kx_alpha_123',
            'is_active' => true,
        ]);

        $pageB = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Beta Page',
            'slug' => 'beta-page',
            'page_source' => 'native',
            'template_type' => 'visual_builder',
            'brand_name' => 'Beta Brand',
            'primary_cta_text' => 'Join Telegram',
            'telegram_destination' => 'https://t.me/beta',
            'tracking_token' => 'kx_beta_123',
            'is_active' => true,
        ]);

        // Edit pageA keeping its own slug 'alpha-page' -> should NOT suffix to -2
        $resA = $this->actingAs($this->user)->put(route('landing-pages.update', $pageA), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Alpha Page Updated',
            'slug' => 'alpha-page',
            'brand_name' => 'Alpha Brand',
            'telegram_destination' => 'https://t.me/alpha',
            'template_type' => 'visual_builder',
        ]);
        $resA->assertRedirect();
        $pageA->refresh();
        $this->assertEquals('alpha-page', $pageA->slug);

        // Edit pageB trying to take pageA's slug 'alpha-page' -> should auto-resolve to 'alpha-page-2'
        $resB = $this->actingAs($this->user)->put(route('landing-pages.update', $pageB), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Beta Page Renamed',
            'slug' => 'alpha-page',
            'brand_name' => 'Beta Brand',
            'telegram_destination' => 'https://t.me/beta',
            'template_type' => 'visual_builder',
        ]);
        $resB->assertRedirect();
        $pageB->refresh();
        $this->assertEquals('alpha-page-2', $pageB->slug);
    }
}
