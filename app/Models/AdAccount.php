<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_business_id',
        'meta_connection_id',
        'client_id',
        'account_id',
        'name',
        'currency',
        'status',
        'spend_limit',
        'balance',
        'lifetime_spend',
        'active_daily_budget',
        'payment_method',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'spend_limit' => 'decimal:2',
        'balance' => 'decimal:2',
        'lifetime_spend' => 'decimal:2',
        'active_daily_budget' => 'decimal:2',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function metaBusiness(): BelongsTo
    {
        return $this->belongsTo(MetaBusiness::class);
    }

    public function metaConnection(): BelongsTo
    {
        return $this->belongsTo(MetaConnection::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function getCurrencySymbolAttribute(): string
    {
        return $this->currency === 'INR' ? '₹' : '$';
    }
}
