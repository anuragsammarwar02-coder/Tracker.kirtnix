<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kx_code',
        'company_name',
        'client_name',
        'industry',
        'email',
        'phone',
        'logo_path',
        'avatar_url',
        'status',
        'meta_ads_connected',
        'monthly_budget',
        'notes',
        'timezone',
    ];

    protected $casts = [
        'meta_ads_connected' => 'boolean',
        'monthly_budget' => 'decimal:2',
    ];

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    public function ctas(): HasMany
    {
        return $this->hasMany(Cta::class);
    }

    public function telegramBots(): HasMany
    {
        return $this->hasMany(TelegramBot::class);
    }

    public function telegramChannels(): HasMany
    {
        return $this->hasMany(TelegramChannel::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(LandingPageView::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CtaClick::class);
    }

    public function telegramEvents(): HasMany
    {
        return $this->hasMany(TelegramEvent::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(Conversion::class);
    }

    public function telegramInvites(): HasMany
    {
        return $this->hasMany(TelegramInvite::class);
    }

    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return str_starts_with($this->logo_path, 'http') ? $this->logo_path : asset('storage/' . $this->logo_path);
        }
        return asset('assets/branding/kirtnix-logo-dark-icon.png');
    }
}
