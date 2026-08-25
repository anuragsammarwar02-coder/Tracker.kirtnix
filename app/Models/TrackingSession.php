<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'visitor_id',
        'client_id',
        'landing_page_id',
        'campaign_id',
        'ip_hash',
        'user_agent',
        'device_type',
        'browser',
        'os',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'fbclid',
        'gclid',
        'fbc',
        'fbp',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(LandingPageView::class);
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(CtaClick::class);
    }
}
