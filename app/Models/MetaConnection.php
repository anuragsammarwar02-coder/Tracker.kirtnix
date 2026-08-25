<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetaConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'facebook_user_id',
        'facebook_name',
        'access_token',
        'token_type',
        'expires_at',
        'status',
        'last_sync_at',
        'sync_status',
        'error_message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
    ];

    protected $hidden = [
        'access_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(MetaBusiness::class);
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }
}
