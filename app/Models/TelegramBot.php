<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TelegramBot extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'name',
        'username',
        'bot_token',
        'channel_id',
        'channel_title',
        'channel_username',
        'webhook_secret',
        'webhook_url',
        'is_webhook_active',
        'last_webhook_ping_at',
        'is_active',
    ];

    protected $hidden = [
        'bot_token',
    ];

    protected function casts(): array
    {
        return [
            'is_webhook_active' => 'boolean',
            'is_active' => 'boolean',
            'last_webhook_ping_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::creating(function ($bot) {
            if (empty($bot->webhook_secret)) {
                $bot->webhook_secret = Str::random(32);
            }
            if (empty($bot->webhook_url)) {
                $bot->webhook_url = url('/api/telegram/webhook/' . $bot->webhook_secret);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function channels(): HasMany
    {
        return $this->hasMany(TelegramChannel::class, 'telegram_bot_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TelegramEvent::class, 'telegram_bot_id');
    }

    public function getBotIdAttribute(): string
    {
        if (str_contains((string) $this->bot_token, ':')) {
            return explode(':', (string) $this->bot_token)[0];
        }
        return '8956518773';
    }
}
