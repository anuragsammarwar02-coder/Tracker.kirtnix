<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'ad_account_id',
        'campaign_id',
        'name',
        'slug',
        'description',
        'outcome',
        'objective',
        'optimization_goal',
        'optimization_event',
        'billing_event',
        'conversion_location',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'status',
        'budget',
        'active_daily_budget',
        'spend',
        'reach',
        'impressions',
        'subscribers',
        'cost_per_subscriber',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'active_daily_budget' => 'decimal:2',
            'spend' => 'decimal:2',
            'cost_per_subscriber' => 'decimal:2',
            'reach' => 'integer',
            'impressions' => 'integer',
            'subscribers' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function adAccount(): BelongsTo
    {
        return $this->belongsTo(AdAccount::class);
    }

    public function insights(): HasMany
    {
        return $this->hasMany(CampaignInsight::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
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
}
