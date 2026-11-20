<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'campaign_id',
        'title',
        'slug',
        'template_type',
        'theme',
        'brand_name',
        'brand_tagline',
        'brand_logo_url',
        'badge_text',
        'hero_heading',
        'hero_subheading',
        'hero_video_url',
        'hero_image_url',
        'features_json',
        'blocks_json',
        'about_heading',
        'about_text',
        'disclaimer_text',
        'footer_text',
        'primary_cta_text',
        'secondary_cta_text',
        'telegram_destination',
        'telegram_channel_username',
        'meta_pixel_id',
        'meta_access_token',
        'meta_test_event_code',
        'gtm_id',
        'custom_head_code',
        'page_source',
        'external_url',
        'vercel_project_name',
        'tracking_token',
        'deployment_status',
        'html_content',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'features_json' => 'array',
            'blocks_json' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Generate default visual blocks for new Software Builder pages
     */
    public static function getDefaultBlocks(?string $brandName = null, ?string $telegramUrl = null, string $preset = 'classic_vip'): array
    {
        if ($preset === 'trader_pro') {
            return self::getTraderProBlocks($brandName, $telegramUrl);
        }

        $brand = $brandName ?: 'VIP TRADING COMMUNITY';
        $tg = $telegramUrl ?: 'https://t.me/kirtnix';

        return [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'live_traders_badge' => '🟢 6,547 Traders Online Now',
                'rating_text' => '4.9/5 (2,340 Reviews)',
                'badge' => '⚡ 100% FREE VIP ACCESS',
                'heading' => 'Join ' . $brand . ' On Telegram',
                'subheading' => 'Get daily high-accuracy trading signals, educational market setups, and real-time community updates.',
                'button_text' => 'Join Free Telegram Channel →',
                'button_subtitle' => 'Free instant access • No payment required',
                'telegram_url' => $tg,
                'bg_style' => 'dark_gradient',
                'padding' => 'py-10 sm:py-14',
                'alignment' => 'center',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'stats',
                'stats' => [
                    ['value' => '50K+', 'label' => 'Members', 'icon' => '👥'],
                    ['value' => '5+ Years', 'label' => 'Experience', 'icon' => '🏆'],
                    ['value' => '95%', 'label' => 'Accuracy', 'icon' => '🎯'],
                ],
                'padding' => 'py-4',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'Why Join Our Community?',
                'subtitle' => 'Everything you need to trade smarter with professional guidance.',
                'cards' => [
                    [
                        'icon' => '📊',
                        'title' => 'Daily Market Setups',
                        'desc' => 'High-probability setups analyzed with strict risk management.',
                    ],
                    [
                        'icon' => '🎯',
                        'title' => 'Clear Entry & Targets',
                        'desc' => 'Every signal includes exact entry point, stop loss, and multiple targets.',
                    ],
                    [
                        'icon' => '⚡',
                        'title' => 'Real-Time Telegram Alerts',
                        'desc' => 'Instant push notifications directly to your phone so you never miss a move.',
                    ],
                ],
                'padding' => 'py-6',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => 'Ready to Level Up Your Trading?',
                'subheading' => 'Tap below to gain instant access to our official Telegram channel.',
                'button_text' => 'Join Free Telegram Channel →',
                'button_subtitle' => 'Available on Telegram App & Web',
                'telegram_url' => $tg,
                'padding' => 'py-6',
                'alignment' => 'center',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'faq',
                'title' => 'Frequently Asked Questions',
                'faqs' => [
                    [
                        'q' => 'Is this Telegram channel really free?',
                        'a' => 'Yes! The main educational setups and community discussions are completely free to join.',
                    ],
                    [
                        'q' => 'How do I join after clicking the button?',
                        'a' => 'Clicking the button will open the Telegram app directly to our official channel where you tap "Join".',
                    ],
                    [
                        'q' => 'Do I need prior trading experience?',
                        'a' => 'Not at all. We provide comprehensive breakdowns suitable for both beginners and experienced traders.',
                    ],
                ],
                'padding' => 'py-6',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'disclaimer',
                'title' => 'Important Risk Disclaimer',
                'text' => 'Trading financial markets involves substantial risk of loss and is not suitable for all investors. All setups, analysis, and information shared are for educational purposes only and do not constitute financial advice.',
                'padding' => 'py-4',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'brand_name' => $brand,
                'copyright' => '© ' . date('Y') . ' ' . $brand . '. All rights reserved.',
                'managed_by' => '⚡ Ads Managed by AK GrowthX Media',
                'managed_by_url' => '',
                'padding' => 'py-6',
            ]
        ];
    }

    /**
     * Generate high-converting Trader Pro visual blocks (Matches Screenshot Design)
     */
    public static function getTraderProBlocks(?string $brandName = null, ?string $telegramUrl = null, ?string $logoUrl = null): array
    {
        $brand = $brandName ?: 'मराठी : TRADER ABHI';
        $tg = $telegramUrl ?: 'https://t.me/kirtnix';
        $logo = $logoUrl ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=200&auto=format&fit=crop&q=80';

        return [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'logo_url' => $logo,
                'avatar_style' => 'circle',
                'brand_name' => $brand,
                'heading' => $brand,
                'subheading' => 'Get exclusive updates, tips, and community access.',
                'button_text' => 'Join Free on Telegram',
                'button_subtitle' => '100% Free · No card required · Leave anytime',
                'consent_text' => 'By continuing, you agree your information may be used for marketing purposes.',
                'button_style' => 'pill',
                'live_traders_badge' => '',
                'rating_text' => '',
                'badge' => '',
                'telegram_url' => $tg,
                'alignment' => 'center',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'stats',
                'layout_style' => 'compact_card',
                'stats' => [
                    ['value' => '11K+', 'label' => 'MEMBERS'],
                    ['value' => 'Daily', 'label' => 'MARKET UPDATES'],
                    ['value' => 'Free', 'label' => 'ALWAYS'],
                ],
                'padding' => 'py-2',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'What you get inside',
                'subtitle' => '',
                'grid_columns' => 2,
                'cards' => [
                    [
                        'icon' => '✓',
                        'title' => 'Daily market analysis',
                        'desc' => 'Clear, jargon-free breakdowns every day.',
                    ],
                    [
                        'icon' => '✓',
                        'title' => 'Price-action setups',
                        'desc' => 'Educational chart studies and key levels.',
                    ],
                    [
                        'icon' => '✓',
                        'title' => 'Risk-management lessons',
                        'desc' => 'Learn position sizing and protecting capital.',
                    ],
                    [
                        'icon' => '✓',
                        'title' => 'Beginner friendly',
                        'desc' => 'Start from basics, ask questions, learn at your pace.',
                    ],
                ],
                'padding' => 'py-4',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => '',
                'subheading' => '',
                'button_text' => 'Join Free on Telegram',
                'button_subtitle' => '',
                'button_style' => 'pill',
                'telegram_url' => $tg,
                'padding' => 'py-4',
                'alignment' => 'center',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'disclaimer',
                'show_mini_brand' => true,
                'brand_name' => $brand,
                'title' => '',
                'text' => "For educational purposes only. Trading and investing in financial markets involves risk, including possible loss of capital. Past performance is not indicative of future results. This is not financial advice.\n\nNexgroww Agency is an independent advertising-technology provider — not affiliated with, endorsing, or responsible for this channel, its operator, or any content, offers, or outcomes shown. This site is also not affiliated with, endorsed by, or part of Meta Platforms, Inc. / Facebook™, or Telegram.",
                'padding' => 'py-2',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'brand_name' => $brand,
                'copyright' => '',
                'managed_by' => '✈ Ads managed by Nexgroww Agency',
                'managed_by_url' => '',
                'hide_links' => true,
                'padding' => 'py-3',
            ]
        ];
    }

    public static function getThemeConfig(?string $themeId = 'midnight_blue'): array
    {
        $themeId = $themeId ?: 'midnight_blue';
        if ($themeId === 'midnight_blue') {
            return [
                'id' => 'midnight_blue',
                'name' => 'Midnight Navy (Trader Pro Dark)',
                'bg_body' => '#060B14',
                'bg_card' => '#0C1626',
                'border_color' => '#1E2F4D',
                'text_main' => '#FFFFFF',
                'text_muted' => '#94A3B8',
                'cta_bg' => 'from-blue-600 to-blue-500',
                'cta_text' => '#FFFFFF',
            ];
        }

        if ($themeId === 'clean_white') {
            return [
                'id' => 'clean_white',
                'name' => 'Clean White (Trader Pro White)',
                'bg_body' => '#F8FAFC',
                'bg_card' => '#FFFFFF',
                'border_color' => '#E2E8F0',
                'text_main' => '#0F172A',
                'text_muted' => '#64748B',
                'cta_bg' => 'from-blue-600 to-blue-700',
                'cta_text' => '#FFFFFF',
            ];
        }

        if ($themeId === 'minimal_light') {
            return [
                'id' => 'minimal_light',
                'name' => 'Minimal Light (Trading Community)',
                'bg_body' => '#F8FAFC',
                'bg_card' => '#FFFFFF',
                'border_color' => '#E2E8F0',
                'text_main' => '#0F172A',
                'text_muted' => '#64748B',
                'cta_bg' => 'from-blue-600 to-blue-700',
                'cta_text' => '#FFFFFF',
            ];
        }

        return [
            'id' => 'premium_dark',
            'name' => 'Premium Dark',
            'bg_body' => '#0A0B0D',
            'bg_card' => '#12141A',
            'border_color' => '#2A2E3A',
            'text_main' => '#FFFFFF',
            'text_muted' => '#94A3B8',
            'cta_bg' => 'from-blue-500 to-blue-600',
            'cta_text' => '#FFFFFF',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function ctas(): HasMany
    {
        return $this->hasMany(Cta::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(LandingPageView::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CtaClick::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    public function telegramInvites(): HasMany
    {
        return $this->hasMany(TelegramInvite::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return url('/lp/' . $this->slug);
    }

    /**
     * Generate a globally unique, sanitized slug for a landing page.
     * If the desired slug already exists (excluding $ignoreId), appends -2, -3, etc.
     */
    public static function generateUniqueSlug(?string $desiredSlug, ?int $ignoreId = null, ?string $fallbackTitle = null): string
    {
        $raw = trim($desiredSlug ?? '');
        if ($raw === '' && !empty($fallbackTitle)) {
            $raw = trim($fallbackTitle);
        }

        // Sanitize: lowercase, spaces/special chars to hyphen, strip duplicate hyphens
        $base = \Illuminate\Support\Str::slug($raw);
        if ($base === '') {
            $base = 'lp-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
        }

        // Check if base slug is available (excluding ignoreId if updating)
        $query = static::withTrashed()->whereRaw('LOWER(slug) = ?', [strtolower($base)]);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if (!$query->exists()) {
            return $base;
        }

        // If taken, find next available suffix: base-2, base-3, etc.
        $counter = 2;
        while (true) {
            $candidate = "{$base}-{$counter}";
            $subQuery = static::withTrashed()->whereRaw('LOWER(slug) = ?', [strtolower($candidate)]);
            if ($ignoreId !== null) {
                $subQuery->where('id', '!=', $ignoreId);
            }

            if (!$subQuery->exists()) {
                return $candidate;
            }

            $counter++;
            if ($counter > 1000) {
                return $base . '-' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(6));
            }
        }
    }
}
