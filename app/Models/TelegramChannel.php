<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TelegramChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_bot_id',
        'client_id',
        'landing_page_id',
        'telegram_chat_id',
        'title',
        'username',
        'type',
        'member_count',
        'is_bot_admin',
        'bot_status',
        'is_active',
        'connected_at',
        'last_synced_at',
    ];

    protected $casts = [
        'is_bot_admin' => 'boolean',
        'is_active' => 'boolean',
        'connected_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    public function bot(): BelongsTo
    {
        return $this->belongsTo(TelegramBot::class, 'telegram_bot_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(TelegramEvent::class, 'telegram_channel_id');
    }
}
