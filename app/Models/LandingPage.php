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
     * Generate default high-converting visual blocks for new Software Builder pages
     */
    public static function getDefaultBlocks(?string $brandName = null, ?string $telegramUrl = null): array
    {
        $brand = $brandName ?: 'VIP TRADING COMMUNITY';
        $tg = $telegramUrl ?: 'https://t.me/kirtnix';

        return [
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'hero',
                'badge' => '⚡ 100% FREE VIP COMMUNITY',
                'heading' => 'Join ' . $brand . ' On Telegram',
                'subheading' => 'Get daily high-accuracy trading signals, educational market setups, and real-time community updates.',
                'button_text' => 'Join Free Telegram Channel',
                'button_subtitle' => 'Free instant access • No payment required',
                'telegram_url' => $tg,
                'bg_style' => 'dark_gradient',
                'padding' => 'py-12 sm:py-16',
                'alignment' => 'center',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'features_grid',
                'title' => 'Why Join Our Community?',
                'subtitle' => 'Everything you need to trade smarter with professional guidance.',
                'cards' => [
                    [
                        'icon' => '📈',
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
                'padding' => 'py-10',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'cta_button',
                'heading' => 'Ready to Level Up Your Trading?',
                'subheading' => 'Tap below to gain instant access to our official Telegram channel.',
                'button_text' => 'Enter Telegram Channel Now',
                'button_subtitle' => 'Available on Telegram App & Web',
                'telegram_url' => $tg,
                'padding' => 'py-10',
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
                'padding' => 'py-10',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'disclaimer',
                'title' => 'Important Risk Disclaimer',
                'text' => 'Trading financial markets involves substantial risk of loss and is not suitable for all investors. All setups, analysis, and information shared are for educational purposes only and do not constitute financial advice.',
                'padding' => 'py-6',
            ],
            [
                'id' => 'block_' . substr(md5(uniqid()), 0, 8),
                'type' => 'footer',
                'brand_name' => $brand,
                'copyright' => '© ' . date('Y') . ' ' . $brand . '. All rights reserved.',
                'padding' => 'py-8',
            ]
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
