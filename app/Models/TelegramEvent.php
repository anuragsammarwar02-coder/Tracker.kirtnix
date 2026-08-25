<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_bot_id',
        'telegram_channel_id',
        'update_id',
        'client_id',
        'campaign_id',
        'cta_click_id',
        'telegram_user_id',
        'telegram_username',
        'first_name',
        'last_name',
        'event_type',
        'invite_link',
        'source',
        'country',
        'device',
        'tracking_token',
        'status_before',
        'status_after',
        'raw_payload',
        'event_time',
    ];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'event_time' => 'datetime',
        ];
    }

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(TelegramChannel::class, 'telegram_channel_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function click(): BelongsTo
    {
        return $this->belongsTo(CtaClick::class, 'cta_click_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
        if ($name) {
            return $name;
        }
        if ($this->telegram_username) {
            return '@' . $this->telegram_username;
        }
        return 'Telegram User (' . substr($this->telegram_user_id, -4) . ')';
    }
}
