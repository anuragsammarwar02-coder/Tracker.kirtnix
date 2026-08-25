<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversion_token',
        'client_id',
        'landing_page_id',
        'campaign_id',
        'telegram_bot_id',
        'telegram_channel_id',
        'telegram_event_id',
        'tracking_session_id',
        'telegram_invite_id',
        'cta_click_id',
        'visitor_id',
        'telegram_user_id',
        'telegram_username',
        'first_name',
        'last_name',
        'event_type',
        'status',
        'source',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'fbclid',
        'fbc',
        'fbp',
        'device',
        'browser',
        'os',
        'country',
        'ip_hash',
        'meta_event_id',
        'meta_capi_status',
        'meta_capi_response',
        'meta_sent_at',
        'meta_retries',
        'event_time',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'meta_sent_at' => 'datetime',
        'meta_retries' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function bot()
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function channel()
    {
        return $this->belongsTo(TelegramChannel::class, 'telegram_channel_id');
    }

    public function event()
    {
        return $this->belongsTo(TelegramEvent::class, 'telegram_event_id');
    }

    public function trackingSession()
    {
        return $this->belongsTo(TrackingSession::class);
    }

    public function invite()
    {
        return $this->belongsTo(TelegramInvite::class, 'telegram_invite_id');
    }

    public function ctaClick()
    {
        return $this->belongsTo(CtaClick::class);
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->first_name) {
            return trim("{$this->first_name} {$this->last_name}");
        }
        if ($this->telegram_username) {
            return "@{$this->telegram_username}";
        }
        return "User #{$this->telegram_user_id}";
    }
}
