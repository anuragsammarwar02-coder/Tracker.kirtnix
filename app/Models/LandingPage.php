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
            'is_active' => 'boolean',
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
}
