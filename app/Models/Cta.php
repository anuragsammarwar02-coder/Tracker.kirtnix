<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cta extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'client_id',
        'campaign_id',
        'name',
        'button_text',
        'button_type',
        'tracking_token',
        'telegram_destination',
        'direct_protocol',
        'click_count',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'click_count' => 'integer',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($cta) {
            if (empty($cta->tracking_token)) {
                $cta->tracking_token = 'kx_' . Str::random(10);
            }
        });
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CtaClick::class);
    }

    public function getTrackableUrlAttribute(): string
    {
        return url('/go/' . $this->tracking_token);
    }
}
