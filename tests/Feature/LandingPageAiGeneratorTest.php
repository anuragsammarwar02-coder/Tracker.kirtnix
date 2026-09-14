<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Client;
use App\Models\Campaign;
use App\Models\LandingPage;
use App\Models\Cta;
use App\Services\LandingPageAiService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LandingPageAiGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'admin@kirtnix.in',
        ]);

        $this->client = Client::create([
            'company_name' => 'Kirtnix Performance Agency',
            'client_name' => 'Anurag',
            'email' => 'anurag@kirtnix.in',
            'status' => 'active',
            'kx_code' => 'KX-TEST-001',
        ]);
    }

    /**
     * 1. Regression Test: Landing Pages index page contains NO raw JS leaking into visible HTML
     */
    public function test_landing_pages_index_does_not_contain_raw_javascript_leaks(): void
    {
        LandingPage::create([
            'client_id' => $this->client->id,
            'title' => 'Alpha Scalping VIP',
            'slug' => 'alpha-scalping-vip',
            'template_type' => 'visual_builder',
            'brand_name' => 'Alpha Scalpers',
            'telegram_destination' => 'https://t.me/alphascalp',
            'page_source' => 'native',
            'tracking_token' => 'kx_token_12345',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('landing-pages.index'));

        $response->assertStatus(200);
        // Ensure no premature closing of div attribute with raw JS code dumped
        $response->assertDontSee("< + '/script>'", false);
        $response->assertDontSee("< + '/script>';", false);
        $response->assertDontSee("data-kx-lp=\"' + this.modalToken", false);
        $response->assertSee('Landing pages');
        $response->assertSee('Alpha Scalping VIP');
    }

    /**
     * 2. AI generation endpoint requires authentication
     */
    public function test_ai_generation_endpoint_requires_authentication(): void
    {
        $response = $this->postJson(route('landing-pages.generate_ai'), [
            'prompt' => 'Create a digital marketing agency landing page',
        ]);

        $response->assertStatus(401);
    }

    /**
     * 3. AI generation validates prompt input
     */
    public function test_ai_generation_validates_prompt(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('landing-pages.generate_ai'), [
            'prompt' => '',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['prompt']);
    }

    /**
     * 4. AI generation outputs valid structured block data
     */
    public function test_ai_generates_structured_blocks_for_agency(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('landing-pages.generate_ai'), [
            'prompt' => 'Create a high-converting landing page for a digital marketing agency targeting small business owners. Goal is Telegram VIP leads.',
            'brand_name' => 'Growth Peak Media',
            'industry' => 'Marketing Agency',
            'language' => 'English',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $data = $response->json('data');
        $this->assertNotEmpty($data['blocks']);
        $this->assertEquals('Growth Peak Media', $data['brand_name']);
        $this->assertIsArray($data['blocks']);

        $types = array_column($data['blocks'], 'type');
        $this->assertContains('hero', $types);
        $this->assertContains('features_grid', $types);
        $this->assertContains('cta_button', $types);
    }

    /**
     * 5. AI generation supports Hinglish copy
     */
    public function test_ai_generates_hinglish_copy(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('landing-pages.generate_ai'), [
            'prompt' => 'Daily trading signals ke liye free Telegram channel join karein in Hinglish',
            'brand_name' => 'STOXK Pro',
            'language' => 'Hinglish',
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data['blocks']);

        $hero = collect($data['blocks'])->firstWhere('type', 'hero');
        $this->assertNotNull($hero);
        $this->assertStringContainsString('Telegram', $hero['heading'] ?? '');
    }

    /**
     * 6. AI service strips any malicious scripts, iframes, or PHP injections
     */
    public function test_ai_sanitizer_removes_dangerous_code(): void
    {
        $service = new LandingPageAiService();

        $maliciousData = [
            'title' => 'Safe Title <script>alert("hack")</script>',
            'brand_name' => 'Safe Brand <?php echo "evil"; ?>',
            'slug' => 'safe-slug',
            'blocks' => [
                [
                    'id' => 'block_1',
                    'type' => 'hero',
                    'heading' => 'Win <script>stealCookies()</script> Today',
                    'subheading' => 'Click <iframe src="evil.com"></iframe> now @php eval("die"); @endphp',
                    'button_text' => 'Join Now',
                ],
                [
                    'id' => 'block_2',
                    'type' => 'unsupported_dangerous_type',
                    'payload' => 'rm -rf /',
                ]
            ]
        ];

        $cleaned = $service->validateAndSanitize($maliciousData);

        $this->assertStringNotContainsString('<script>', $cleaned['title']);
        $this->assertStringNotContainsString('alert', $cleaned['title']);
        $this->assertStringNotContainsString('<?php', $cleaned['brand_name']);

        $hero = $cleaned['blocks'][0];
        $this->assertStringNotContainsString('<script>', $hero['heading']);
        $this->assertStringNotContainsString('stealCookies', $hero['heading']);
        $this->assertStringNotContainsString('<iframe>', $hero['subheading']);
        $this->assertStringNotContainsString('@php', $hero['subheading']);

        // Unsupported block type should be discarded
        $types = array_column($cleaned['blocks'], 'type');
        $this->assertNotContains('unsupported_dangerous_type', $types);
    }

    /**
     * 7. AI-generated page can be saved as draft and published in Software Builder
     */
    public function test_ai_generated_page_can_be_saved_and_published(): void
    {
        $service = new LandingPageAiService();
        $generated = $service->generate('Create a trading community landing page for AlphaScalp VIP', [
            'brand_name' => 'AlphaScalp VIP',
            'telegram_destination' => 'https://t.me/alphascalp',
        ]);

        // 1. Save Draft
        $storeResponse = $this->actingAs($this->user)->post(route('landing-pages.store'), [
            'client_id' => $this->client->id,
            'title' => $generated['title'],
            'slug' => 'alphascalp-vip-unique',
            'brand_name' => $generated['brand_name'],
            'telegram_destination' => $generated['telegram_destination'],
            'template_type' => 'visual_builder',
            'is_active' => '0',
            'blocks_json' => json_encode($generated['blocks']),
        ]);

        $storeResponse->assertRedirect();
        $lp = LandingPage::where('slug', 'alphascalp-vip-unique')->first();
        $this->assertNotNull($lp);
        $this->assertFalse($lp->is_active);
        $this->assertCount(count($generated['blocks']), $lp->blocks_json);

        // 2. Publish
        $updateResponse = $this->actingAs($this->user)->put(route('landing-pages.update', $lp), [
            'client_id' => $this->client->id,
            'title' => $lp->title,
            'slug' => $lp->slug,
            'brand_name' => $lp->brand_name,
            'telegram_destination' => $lp->telegram_destination,
            'template_type' => 'visual_builder',
            'is_active' => '1',
            'blocks_json' => json_encode($lp->blocks_json),
        ]);

        $updateResponse->assertRedirect();
        $lp->refresh();
        $this->assertTrue($lp->is_active);
    }

    /**
     * 8. Public landing page renders software tracking without fake conversions
     */
    public function test_public_landing_page_renders_with_application_tracking(): void
    {
        $service = new LandingPageAiService();
        $generated = $service->generate('Marketing Agency page', [
            'brand_name' => 'Apex Agency',
            'telegram_destination' => 'https://t.me/apexagency',
        ]);

        $lp = LandingPage::create([
            'client_id' => $this->client->id,
            'title' => 'Apex Agency Funnel',
            'slug' => 'apex-agency-funnel',
            'template_type' => 'visual_builder',
            'brand_name' => 'Apex Agency',
            'telegram_destination' => 'https://t.me/apexagency',
            'meta_pixel_id' => '1018611380802707',
            'page_source' => 'native',
            'tracking_token' => 'kx_apex_token',
            'blocks_json' => $generated['blocks'],
            'is_active' => true,
        ]);

        Cta::create([
            'landing_page_id' => $lp->id,
            'client_id' => $this->client->id,
            'button_type' => 'primary',
            'button_text' => 'Join VIP Telegram',
            'telegram_destination' => 'https://t.me/apexagency',
            'tracking_token' => 'cta_apex_token',
        ]);

        $response = $this->get('/lp/' . $lp->slug);

        $response->assertStatus(200);
        $response->assertSee('Apex Agency');
        $response->assertSee('data-kx-cta="1"', false);
        $response->assertSee('1018611380802707'); // Software Meta Pixel initialized
        $response->assertSee('/api/public/kx.js?lp=kx_apex_token', false); // Software tracking script
    }
}
