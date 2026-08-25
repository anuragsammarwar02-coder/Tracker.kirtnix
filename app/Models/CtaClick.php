<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CtaClick extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_session_id',
        'cta_id',
        'landing_page_id',
        'client_id',
        'campaign_id',
        'tracking_token',
        'visitor_id',
        'is_unique',
        'destination_url',
        'meta_event_id',
        'meta_capi_status',
        'meta_capi_response',
        'clicked_at',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
            'clicked_at' => 'datetime',
        ];
    }

    public function trackingSession(): BelongsTo
    {
        return $this->belongsTo(TrackingSession::class);
    }

    public function cta(): BelongsTo
    {
        return $this->belongsTo(Cta::class);
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

    public function telegramEvents(): HasMany
    {
        return $this->hasMany(TelegramEvent::class);
    }
}
