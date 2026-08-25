<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'invite_link',
        'invite_name',
        'tracking_session_id',
        'landing_page_id',
        'client_id',
        'telegram_bot_id',
        'telegram_channel_id',
        'visitor_id',
        'is_single_use',
        'creates_join_request',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'is_single_use' => 'boolean',
        'creates_join_request' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function trackingSession()
    {
        return $this->belongsTo(TrackingSession::class);
    }

    public function landingPage()
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function bot()
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function channel()
    {
        return $this->belongsTo(TelegramChannel::class, 'telegram_channel_id');
    }

    public function conversions()
    {
        return $this->hasMany(Conversion::class);
    }
}
