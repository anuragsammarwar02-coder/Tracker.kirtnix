<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Client;
use App\Models\Cta;
use App\Models\LandingPage;
use App\Models\TelegramBot;
use App\Models\TelegramChannel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftwareBuilderVisualEditorTest extends TestCase
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
            'client_name' => 'Kirtnix Signals Pro',
            'company_name' => 'Kirtnix Signals Pro',
            'contact_name' => 'Anurag Sammarwar',
            'email' => 'signals@kirtnix.in',
            'status' => 'active',
            'kx_code' => 'KX-TEST-PRO',
        ]);
        $this->campaign = Campaign::create([
            'client_id' => $this->client->id,
            'name' => 'Signals Ad Campaign Q3',
            'slug' => 'signals-ad-campaign-q3',
            'utm_source' => 'meta',
            'utm_campaign' => 'vip_forex_q3',
            'status' => 'active',
        ]);
    }

    public function test_visual_builder_create_page_renders_builder_ui(): void
    {
        $response = $this->actingAs($this->user)->get(route('landing-pages.create'));

        $response->assertStatus(200);
        $response->assertViewIs('landing_pages.builder');
        $response->assertSee('Software Landing Page Builder');
        $response->assertSee('Hero Header');
        $response->assertSee('Feature Cards');
        $response->assertSee('Stats Counters');
        $response->assertSee('CTA Button');
        $response->assertSee('Meta Pixel ID');
        $response->assertSee('Premium Dark');
        $response->assertSee('Clean Light');
    }

    public function test_can_store_and_render_minimal_light_theme_landing_page(): void
    {
        $blocks = [
            [
                'id' => 'hero_1',
                'type' => 'hero',
                'live_traders_badge' => '🟢 6,547 Traders Online Now',
                'rating_text' => '4.9/5 (2,340 Reviews)',
                'badge' => '⚡ 100% FREE VIP ACCESS',
                'heading' => 'Gujarati Trader VIP Community',
                'subheading' => 'Get daily high-accuracy trading signals on Telegram.',
                'button_text' => 'Join Free Telegram Channel →',
                'button_subtitle' => 'Free instant access • No payment required',
            ],
            [
                'id' => 'stats_1',
                'type' => 'stats',
                'stats' => [
                    ['value' => '50K+', 'label' => 'Members'],
                    ['value' => '5+ Years', 'label' => 'Experience'],
                    ['value' => '95%', 'label' => 'Accuracy'],
                ]
            ],
            [
                'id' => 'features_1',
                'type' => 'features_grid',
                'title' => 'Community Highlights',
                'cards' => [
                    ['icon' => '📊', 'title' => 'Daily Nifty Levels', 'desc' => 'Key market breakdown'],
                    ['icon' => '🎯', 'title' => 'Educational Analysis', 'desc' => 'Strict risk management']
                ]
            ],
            [
                'id' => 'cta_1',
                'type' => 'cta_button',
                'heading' => 'Join 50,000+ Active Traders',
                'button_text' => 'Join Free Telegram Channel →'
            ],
            [
                'id' => 'disclaimer_1',
                'type' => 'disclaimer',
                'title' => 'Risk Disclaimer',
                'text' => 'Trading involves risk of loss. For educational purposes only.'
            ],
            [
                'id' => 'footer_1',
                'type' => 'footer',
                'managed_by' => '⚡ Ads Managed by Kirtnix Media'
            ]
        ];

        $response = $this->actingAs($this->user)->post(route('landing-pages.store'), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Gujarati Trader Light',
            'slug' => 'gujarati-trader-light',
            'brand_name' => 'Gujarati Trader',
            'template_type' => 'visual_builder',
            'theme' => 'minimal_light',
            'telegram_destination' => 'https://t.me/gujaratitrader',
            'blocks_json' => json_encode($blocks),
            'is_active' => '1',
        ]);

        $response->assertRedirect();

        $page = LandingPage::where('slug', 'gujarati-trader-light')->first();
        $this->assertNotNull($page);
        $this->assertEquals('minimal_light', $page->theme);

        // Render public page
        $publicRes = $this->get(route('public.landing_page', $page->slug));
        $publicRes->assertStatus(200);
        $publicRes->assertViewIs('templates.visual_builder');
        
        $html = $publicRes->getContent();
        $publicRes->assertSee('Gujarati Trader VIP Community');
        $publicRes->assertSee('6,547 Traders Online Now');
        $publicRes->assertSee('4.9/5 (2,340 Reviews)');
        $publicRes->assertSee('50K+');
        $publicRes->assertSee('Daily Nifty Levels');
        $publicRes->assertSee('⚡ Ads Managed by Kirtnix Media');
        $this->assertStringContainsString('bg-[#F4F6F9]', $html);
        $this->assertStringContainsString('overflow-y: auto', $html);
    }

    public function test_can_render_extremely_long_landing_page_without_clipping(): void
    {
        $longBlocks = [];
        $longBlocks[] = [
            'id' => 'hero_0',
            'type' => 'hero',
            'heading' => 'Section 1: Hero Announcement',
            'button_text' => 'Join Channel'
        ];
        $longBlocks[] = [
            'id' => 'stats_1',
            'type' => 'stats',
            'stats' => [['value' => '100K', 'label' => 'Subscribers']]
        ];

        for ($i = 2; $i <= 10; $i++) {
            $longBlocks[] = [
                'id' => "block_{$i}",
                'type' => 'heading_text',
                'heading' => "Section {$i}: Feature Breakdown",
                'text' => "Detailed explanation of feature {$i} with long descriptive text for test."
            ];
        }

        $longBlocks[] = [
            'id' => 'faq_11',
            'type' => 'faq',
            'title' => 'Section 11: FAQ',
            'faqs' => [['q' => 'Question 11?', 'a' => 'Answer 11']]
        ];
        $longBlocks[] = [
            'id' => 'cta_12',
            'type' => 'cta_button',
            'heading' => 'Section 12: Final CTA',
            'button_text' => 'Join Now'
        ];
        $longBlocks[] = [
            'id' => 'footer_13',
            'type' => 'footer',
            'copyright' => 'Section 13: Footer Copyright'
        ];

        $page = LandingPage::create([
            'client_id' => $this->client->id,
            'title' => 'Long Test Page 13 Sections',
            'slug' => 'long-test-page-13',
            'brand_name' => 'Long Page Brand',
            'page_source' => 'native',
            'template_type' => 'visual_builder',
            'theme' => 'premium_dark',
            'telegram_destination' => 'https://t.me/kirtnix',
            'blocks_json' => $longBlocks,
            'is_active' => true,
        ]);

        Cta::create([
            'landing_page_id' => $page->id,
            'client_id' => $this->client->id,
            'name' => 'Primary Hero CTA',
            'button_text' => 'Join Channel',
            'button_type' => 'primary',
            'tracking_token' => 'kx_long_cta',
            'telegram_destination' => 'https://t.me/kirtnix',
            'direct_protocol' => 'auto',
            'is_active' => true,
        ]);

        $res = $this->get(route('public.landing_page', $page->slug));
        $res->assertStatus(200);

        // Verify top hero and bottom footer both rendered in HTML
        $res->assertSee('Section 1: Hero Announcement');
        $res->assertSee('Section 6: Feature Breakdown');
        $res->assertSee('Section 11: FAQ');
        $res->assertSee('Section 12: Final CTA');
        $res->assertSee('Section 13: Footer Copyright');
    }

    public function test_can_store_visual_builder_page_with_blocks_json(): void
    {
        $blocks = [
            [
                'id' => 'block_hero_1',
                'type' => 'hero',
                'badge' => '⚡ 100% VERIFIED',
                'heading' => 'High Accuracy Forex Community',
                'subheading' => 'Daily verified setups and real-time market signals.',
                'button_text' => 'Join VIP Signals Free',
                'button_subtitle' => 'Zero upfront payment • Telegram only',
            ],
            [
                'id' => 'block_features_1',
                'type' => 'features_grid',
                'title' => 'Why Join Us',
                'cards' => [
                    ['icon' => '📈', 'title' => '90% Win Rate', 'desc' => 'Verified past performance'],
                    ['icon' => '🎯', 'title' => 'Precise Stop Loss', 'desc' => 'Strict 1:3 risk reward ratio'],
                ]
            ],
            [
                'id' => 'block_faq_1',
                'type' => 'faq',
                'title' => 'Common Questions',
                'faqs' => [
                    ['q' => 'Is this really free?', 'a' => 'Yes, 100% free channel access.']
                ]
            ]
        ];

        $response = $this->actingAs($this->user)->post(route('landing-pages.store'), [
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Forex VIP Signals',
            'slug' => 'forex-vip-signals-pro',
            'brand_name' => 'Forex VIP Signals',
            'telegram_destination' => 'https://t.me/kirtnix_vip',
            'meta_pixel_id' => '987654321012345',
            'meta_access_token' => 'SECRET_CAPI_TOKEN_SHOULD_NOT_LEAK',
            'template_type' => 'visual_builder',
            'blocks_json' => json_encode($blocks),
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        
        $landingPage = LandingPage::where('slug', 'forex-vip-signals-pro')->first();
        $this->assertNotNull($landingPage);
        $this->assertEquals('native', $landingPage->page_source);
        $this->assertEquals('High Accuracy Forex Community', $landingPage->hero_heading);
        $this->assertEquals('Join VIP Signals Free', $landingPage->primary_cta_text);
        $this->assertIsArray($landingPage->blocks_json);
        $this->assertCount(3, $landingPage->blocks_json);

        // Verify primary CTA created automatically
        $primaryCta = $landingPage->ctas()->where('button_type', 'primary')->first();
        $this->assertNotNull($primaryCta);
        $this->assertEquals('Join VIP Signals Free', $primaryCta->button_text);
        $this->assertEquals('https://t.me/kirtnix_vip', $primaryCta->telegram_destination);
    }

    public function test_can_edit_and_update_visual_builder_page_blocks(): void
    {
        $page = LandingPage::create([
            'client_id' => $this->client->id,
            'title' => 'Original Title',
            'slug' => 'original-slug',
            'brand_name' => 'Original Brand',
            'page_source' => 'native',
            'template_type' => 'visual_builder',
            'telegram_destination' => 'https://t.me/old_link',
            'blocks_json' => [
                ['id' => '1', 'type' => 'hero', 'heading' => 'Old Heading', 'button_text' => 'Old Button']
            ],
            'is_active' => true,
        ]);

        Cta::create([
            'landing_page_id' => $page->id,
            'client_id' => $this->client->id,
            'name' => 'Primary Hero CTA',
            'button_text' => 'Old Button',
            'button_type' => 'primary',
            'tracking_token' => 'kx_old_cta',
            'telegram_destination' => 'https://t.me/old_link',
            'direct_protocol' => 'auto',
            'is_active' => true,
        ]);

        // Access edit view
        $editResponse = $this->actingAs($this->user)->get(route('landing-pages.edit', $page));
        $editResponse->assertStatus(200);
        $editResponse->assertViewIs('landing_pages.builder');

        // Update with reordered and modified blocks
        $updatedBlocks = [
            ['id' => '1', 'type' => 'hero', 'heading' => 'New Awesome Heading', 'button_text' => 'New Button Text'],
            ['id' => '2', 'type' => 'disclaimer', 'title' => 'Updated Disclaimer', 'text' => 'Risk info here.']
        ];

        $updateResponse = $this->actingAs($this->user)->put(route('landing-pages.update', $page), [
            'client_id' => $this->client->id,
            'title' => 'Updated Page Title',
            'slug' => 'updated-slug',
            'brand_name' => 'Updated Brand',
            'telegram_destination' => 'https://t.me/new_channel',
            'meta_pixel_id' => '112233445566778',
            'template_type' => 'visual_builder',
            'blocks_json' => json_encode($updatedBlocks),
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect();
        $page->refresh();

        $this->assertEquals('Updated Page Title', $page->title);
        $this->assertEquals('updated-slug', $page->slug);
        $this->assertEquals('New Awesome Heading', $page->hero_heading);
        $this->assertEquals('New Button Text', $page->primary_cta_text);
        $this->assertEquals('https://t.me/new_channel', $page->telegram_destination);
        $this->assertCount(2, $page->blocks_json);

        // Verify CTA synchronized
        $cta = $page->ctas()->where('button_type', 'primary')->first();
        $this->assertEquals('New Button Text', $cta->button_text);
        $this->assertEquals('https://t.me/new_channel', $cta->telegram_destination);
    }

    public function test_public_landing_page_renders_visual_builder_without_raw_js_leaks(): void
    {
        $blocks = [
            [
                'id' => 'hero',
                'type' => 'hero',
                'badge' => '🔥 VIP FREE PASS',
                'heading' => 'Trade Like A Professional',
                'subheading' => 'Instant access to institutional forex setups.',
                'button_text' => 'Join Telegram Now',
                'button_subtitle' => 'Free telegram channel entry',
            ],
            [
                'id' => 'faq',
                'type' => 'faq',
                'title' => 'Helpful FAQ',
                'faqs' => [
                    ['q' => 'What broker do I need?', 'a' => 'You can use any broker of your choice.']
                ]
            ]
        ];

        $page = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Trade Like A Pro',
            'slug' => 'trade-pro-live',
            'brand_name' => 'Pro Traders Hub',
            'page_source' => 'native',
            'template_type' => 'visual_builder',
            'telegram_destination' => 'https://t.me/trade_pro_hub',
            'meta_pixel_id' => '777888999000111',
            'meta_access_token' => 'SUPER_CONFIDENTIAL_TOKEN_XYZ_123',
            'blocks_json' => $blocks,
            'is_active' => true,
        ]);

        $cta = Cta::create([
            'landing_page_id' => $page->id,
            'client_id' => $this->client->id,
            'name' => 'Primary Hero CTA',
            'button_text' => 'Join Telegram Now',
            'button_type' => 'primary',
            'tracking_token' => 'kx_pro_cta_token',
            'telegram_destination' => 'https://t.me/trade_pro_hub',
            'direct_protocol' => 'auto',
            'is_active' => true,
        ]);

        // Anonymous public visitor loads page
        $response = $this->get(route('public.landing_page', $page->slug));

        $response->assertStatus(200);
        $response->assertViewIs('templates.visual_builder');

        $html = $response->getContent();

        // 1. Check content rendered
        $response->assertSee('Trade Like A Professional');
        $response->assertSee('Instant access to institutional forex setups.');
        $response->assertSee('Join Telegram Now');
        $response->assertSee('What broker do I need?');

        // 2. Check tracked CTA redirect
        $expectedCtaUrl = route('public.cta_redirect', $cta->tracking_token);
        $this->assertStringContainsString($expectedCtaUrl, $html);
        $this->assertStringContainsString('data-kx-cta="1"', $html);

        // 3. Check Meta Pixel automatically injected into head
        $this->assertStringContainsString('fbq(\'init\', \'777888999000111\');', $html);
        $this->assertStringContainsString('fbq(\'track\', \'PageView\'', $html);

        // 4. CRITICAL SECURITY: Verify Meta Access Token NEVER appears in HTML
        $this->assertStringNotContainsString('SUPER_CONFIDENTIAL_TOKEN_XYZ_123', $html);

        // 5. CRITICAL FIX: Verify NO raw JavaScript leaks into page text
        $this->assertStringNotContainsString('navigator.clipboard.writeText', $html);
        $this->assertStringNotContainsString("copyScript()", $html);
        $this->assertStringNotContainsString("modalToken", $html);
        $this->assertStringNotContainsString("navigator.clipboard", $html);

        // 6. Check kx.js tracker included before body end
        $this->assertStringContainsString('/api/public/kx.js', $html);
    }

    public function test_cta_click_does_not_fire_subscribe_conversion(): void
    {
        $page = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Attribution Test Page',
            'slug' => 'attribution-test-page',
            'brand_name' => 'Attribution Pro',
            'page_source' => 'native',
            'template_type' => 'visual_builder',
            'telegram_destination' => 'https://t.me/attribution_pro',
            'blocks_json' => [
                ['id' => '1', 'type' => 'hero', 'heading' => 'Join VIP', 'button_text' => 'Join VIP']
            ],
            'is_active' => true,
        ]);

        $cta = Cta::create([
            'landing_page_id' => $page->id,
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'name' => 'Primary Hero CTA',
            'button_text' => 'Join VIP',
            'button_type' => 'primary',
            'tracking_token' => 'kx_attrib_cta',
            'telegram_destination' => 'https://t.me/attribution_pro',
            'direct_protocol' => 'auto',
            'is_active' => true,
        ]);

        // Visitor clicks the CTA redirect
        $response = $this->get(route('public.cta_redirect', $cta->tracking_token));

        $response->assertStatus(200);
        $response->assertSee('Connecting to Telegram...');
        
        // Assert CTA click was recorded
        $this->assertDatabaseHas('cta_clicks', [
            'cta_id' => $cta->id,
            'landing_page_id' => $page->id,
        ]);

        // CRITICAL INVARIANT: CTA click must NOT create a Subscribe conversion
        $this->assertEquals(0, \App\Models\Conversion::where('client_id', $this->client->id)->where('status', 'verified')->count());
    }

    public function test_confirmed_telegram_join_is_the_sole_source_of_subscriber_conversion(): void
    {
        $bot = TelegramBot::create([
            'name' => 'Test Signal Bot',
            'username' => 'test_signal_bot',
            'bot_token' => '123456:ABC-DEF-XYZ',
            'webhook_secret' => 'test_sec_123',
            'is_active' => true,
            'is_webhook_active' => true,
        ]);

        $channel = TelegramChannel::create([
            'telegram_bot_id' => $bot->id,
            'client_id' => $this->client->id,
            'telegram_chat_id' => '-1009988776655',
            'title' => 'VIP Telegram Signals Channel',
            'username' => 'vip_signals_channel',
            'is_bot_admin' => true,
            'is_active' => true,
        ]);

        $payload = [
            'update_id' => 9991234,
            'chat_member' => [
                'chat' => [
                    'id' => -1009988776655,
                    'title' => 'VIP Telegram Signals Channel',
                    'type' => 'channel',
                ],
                'from' => [
                    'id' => 888777666,
                    'is_bot' => false,
                    'first_name' => 'Real',
                    'last_name' => 'Subscriber',
                    'username' => 'realsubscriber',
                ],
                'date' => time(),
                'old_chat_member' => [
                    'status' => 'left',
                ],
                'new_chat_member' => [
                    'status' => 'member',
                ],
            ]
        ];

        $response = $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $payload);
        $response->assertStatus(200);

        // Confirmed Telegram membership created the verified conversion
        $this->assertEquals(1, \App\Models\Conversion::where('client_id', $this->client->id)->where('telegram_user_id', '888777666')->where('status', 'verified')->count());
    }

    public function test_public_landing_pages_contain_anti_inspection_protection_layer(): void
    {
        // 1. Visual Builder Template
        $vbPage = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Visual Builder Test Page',
            'slug' => 'test-vb-anti-inspect',
            'template_type' => 'visual_builder',
            'brand_name' => 'Kirtnix Visual',
            'primary_cta_text' => 'Join VIP',
            'telegram_destination' => 'https://t.me/kirtnix',
            'blocks_json' => [
                ['id' => '1', 'type' => 'hero', 'heading' => 'Visual Builder Hero', 'button_text' => 'Join VIP']
            ],
            'is_active' => true,
        ]);

        $responseVb = $this->get(route('public.landing_page', $vbPage->slug));
        $responseVb->assertStatus(200);
        $responseVb->assertSee('kx-public-protected');
        $responseVb->assertSee('contextmenu');
        $responseVb->assertSee('keydown');
        $responseVb->assertSee('user-select: none');

        // 2. Forex Focus Template
        $ffPage = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Forex Focus Test Page',
            'slug' => 'test-ff-anti-inspect',
            'template_type' => 'forex_focus',
            'brand_name' => 'Kirtnix Forex',
            'primary_cta_text' => 'Join Forex',
            'telegram_destination' => 'https://t.me/kirtnix',
            'blocks_json' => null,
            'is_active' => true,
        ]);

        $responseFf = $this->get(route('public.landing_page', $ffPage->slug));
        $responseFf->assertStatus(200);
        $responseFf->assertSee('kx-public-protected');
        $responseFf->assertSee('contextmenu');
        $responseFf->assertSee('keydown');

        // 3. Gujarati Trader Template
        $gtPage = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Gujarati Trader Test Page',
            'slug' => 'test-gt-anti-inspect',
            'template_type' => 'gujarati_trader',
            'brand_name' => 'Kirtnix Gujarati',
            'primary_cta_text' => 'Join Gujarati',
            'telegram_destination' => 'https://t.me/kirtnix',
            'blocks_json' => null,
            'is_active' => true,
        ]);

        $responseGt = $this->get(route('public.landing_page', $gtPage->slug));
        $responseGt->assertStatus(200);
        $responseGt->assertSee('kx-public-protected');
        $responseGt->assertSee('contextmenu');
        $responseGt->assertSee('keydown');
    }

    public function test_builder_and_admin_pages_do_not_contain_anti_inspection_protection_layer(): void
    {
        $page = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Admin Test Page',
            'slug' => 'admin-test-page',
            'template_type' => 'visual_builder',
            'brand_name' => 'Kirtnix Admin',
            'primary_cta_text' => 'Join VIP',
            'telegram_destination' => 'https://t.me/kirtnix',
            'page_source' => 'native',
            'is_active' => true,
        ]);

        // Landing Page Builder Create page
        $resCreate = $this->actingAs($this->user)->get(route('landing-pages.create'));
        $resCreate->assertStatus(200);
        $resCreate->assertDontSee('kx-public-protected');

        // Landing Page Builder Edit page
        $resEdit = $this->actingAs($this->user)->get(route('landing-pages.edit', $page));
        $resEdit->assertStatus(200);
        $resEdit->assertDontSee('kx-public-protected');

        // Dashboard
        $resDash = $this->actingAs($this->user)->get(route('dashboard'));
        $resDash->assertStatus(200);
        $resDash->assertDontSee('kx-public-protected');

        // Login page (guest)
        auth()->logout();
        $resLogin = $this->get(route('login'));
        $resLogin->assertStatus(200);
        $resLogin->assertDontSee('kx-public-protected');

        // Settings page
        $resSettings = $this->actingAs($this->user)->get(route('settings.index'));
        $resSettings->assertStatus(200);
        $resSettings->assertDontSee('kx-public-protected');
    }

    public function test_public_landing_page_does_not_leak_secret_credentials(): void
    {
        $secretCapiToken = 'EAAG_SUPER_SECRET_CAPI_ACCESS_TOKEN_987654321';
        $secretAppSecret = 'fb_app_secret_super_private_xyz';

        $page = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'Secure Public Landing Page',
            'slug' => 'secure-public-landing-page',
            'template_type' => 'visual_builder',
            'brand_name' => 'Secure Brand',
            'primary_cta_text' => 'Join Community',
            'telegram_destination' => 'https://t.me/kirtnix',
            'meta_pixel_id' => '123456789012345',
            'meta_access_token' => $secretCapiToken,
            'blocks_json' => [
                ['id' => '1', 'type' => 'hero', 'heading' => 'Secure Public Page', 'button_text' => 'Join Community']
            ],
            'is_active' => true,
        ]);

        $response = $this->get(route('public.landing_page', $page->slug));
        $response->assertStatus(200);

        // Pixel ID is public for browser tracking
        $response->assertSee('123456789012345');

        // Secrets must NEVER be present in the public HTML response
        $response->assertDontSee($secretCapiToken);
        $response->assertDontSee($secretAppSecret);
        $response->assertDontSee('meta_access_token');
    }

    public function test_footer_branding_url_is_rendered_and_triggers_external_link(): void
    {
        $blocks = [
            [
                'id' => 'hero_1',
                'type' => 'hero',
                'heading' => 'VIP Community',
                'button_text' => 'Join Telegram',
            ],
            [
                'id' => 'footer_1',
                'type' => 'footer',
                'copyright' => '© 2026 VIP TRADING. All rights reserved.',
                'managed_by' => '⚡ Ads Managed by AK GrowthX Media',
                'managed_by_url' => 'https://akgrowthx.com',
            ],
        ];

        $page = LandingPage::create([
            'client_id' => $this->client->id,
            'campaign_id' => $this->campaign->id,
            'title' => 'AK GrowthX Branded Page',
            'slug' => 'ak-growthx-branded-page',
            'template_type' => 'visual_builder',
            'theme' => 'premium_dark',
            'brand_name' => 'VIP TRADING',
            'telegram_destination' => 'https://t.me/kirtnix',
            'blocks_json' => $blocks,
            'is_active' => true,
        ]);

        $response = $this->get(route('public.landing_page', $page->slug));
        $response->assertStatus(200);
        $response->assertSee('⚡ Ads Managed by AK GrowthX Media');
        $response->assertSee('href="https://akgrowthx.com"', false);
        $response->assertSee('target="_blank"', false);

        // Also check builder UI shows Managed By Link input field
        $resBuilder = $this->actingAs($this->user)->get(route('landing-pages.edit', $page));
        $resBuilder->assertStatus(200);
        $resBuilder->assertSee('Managed By Link / Trigger URL');
    }
}
