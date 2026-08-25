<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_session_id',
        'landing_page_id',
        'client_id',
        'campaign_id',
        'visitor_id',
        'is_unique',
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'is_unique' => 'boolean',
            'viewed_at' => 'datetime',
        ];
    }

    public function trackingSession(): BelongsTo
    {
        return $this->belongsTo(TrackingSession::class);
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
}
