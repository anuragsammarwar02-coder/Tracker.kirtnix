<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Client;
use App\Models\Campaign;
use App\Models\LandingPage;
use App\Models\Cta;
use App\Models\TelegramBot;
use App\Models\TelegramEvent;
use App\Models\CtaClick;
use App\Models\LandingPageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_super_admin_can_login_with_seeded_credentials(): void
    {
        $response = $this->post(route('login.submit'), [
            'email' => 'admin@kirtnix.agency',
            'password' => 'Kirtnix@2026!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_guest_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_public_landing_page_renders_dynamically_and_records_view(): void
    {
        $landingPage = LandingPage::where('slug', 'forex-focus-tg')->first();
        $this->assertNotNull($landingPage);

        $initialViewCount = LandingPageView::where('landing_page_id', $landingPage->id)->count();

        $response = $this->get(route('public.landing_page', 'forex-focus-tg'));

        $response->assertStatus(200);
        $response->assertSee('FOREX FOCUS');
        $response->assertSee('Understand the Forex Markets');
        $response->assertSee('data-kx-cta', false);
        $response->assertSee('1130260856232291'); // Meta pixel
        $response->assertCookie('kx_visitor_id');

        $newViewCount = LandingPageView::where('landing_page_id', $landingPage->id)->count();
        $this->assertEquals($initialViewCount + 1, $newViewCount);
    }

    public function test_cta_redirect_records_click_and_launches_direct_telegram(): void
    {
        $cta = Cta::where('tracking_token', 'kx_ff_hero')->first();
        $this->assertNotNull($cta);

        $initialClickCount = CtaClick::where('cta_id', $cta->id)->count();

        $response = $this->get(route('public.cta_redirect', 'kx_ff_hero'));

        $response->assertStatus(200);
        $response->assertSee('Connecting to Telegram...');
        $response->assertSee('tg://join?invite=sncMUjBZ9a41ZDll');

        $newClickCount = CtaClick::where('cta_id', $cta->id)->count();
        $this->assertEquals($initialClickCount + 1, $newClickCount);
    }

    public function test_telegram_webhook_logs_real_join_event(): void
    {
        $bot = TelegramBot::first();
        $this->assertNotNull($bot);

        $payload = [
            'update_id' => 12345678,
            'chat_member' => [
                'chat' => [
                    'id' => -1002194829104,
                    'title' => 'Forex Focus Global Community',
                    'type' => 'channel',
                ],
                'from' => [
                    'id' => 987654321,
                    'is_bot' => false,
                    'first_name' => 'Rahul',
                    'last_name' => 'Sharma',
                    'username' => 'rahul_trader',
                ],
                'date' => time(),
                'old_chat_member' => [
                    'user' => ['id' => 987654321, 'first_name' => 'Rahul'],
                    'status' => 'left',
                ],
                'new_chat_member' => [
                    'user' => ['id' => 987654321, 'first_name' => 'Rahul'],
                    'status' => 'member',
                ],
                'invite_link' => [
                    'invite_link' => 'https://t.me/+sncMUjBZ9a41ZDll',
                ]
            ]
        ];

        $response = $this->postJson(route('api.telegram.webhook', $bot->webhook_secret), $payload);

        $response->assertStatus(200);
        $response->assertJson(['ok' => true, 'processed' => true]);

        $this->assertDatabaseHas('telegram_events', [
            'telegram_user_id' => '987654321',
            'telegram_username' => 'rahul_trader',
            'event_type' => 'join',
        ]);
    }

    public function test_authenticated_user_can_create_new_client(): void
    {
        $admin = User::where('email', 'admin@kirtnix.agency')->first();

        $response = $this->actingAs($admin)->post(route('clients.store'), [
            'company_name' => 'Crypto Momentum Alpha',
            'client_name' => 'Sarah Connor',
            'email' => 'sarah@cryptomomentum.com',
            'phone' => '+15550001111',
            'status' => 'active',
            'notes' => 'Crypto breakout channel',
        ]);

        $this->assertDatabaseHas('clients', [
            'company_name' => 'Crypto Momentum Alpha',
            'client_name' => 'Sarah Connor',
        ]);
    }
}
