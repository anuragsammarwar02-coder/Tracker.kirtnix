<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetaBusiness extends Model
{
    use HasFactory;

    protected $fillable = [
        'meta_connection_id',
        'business_id',
        'name',
        'verification_status',
    ];

    public function metaConnection(): BelongsTo
    {
        return $this->belongsTo(MetaConnection::class);
    }

    public function adAccounts(): HasMany
    {
        return $this->hasMany(AdAccount::class);
    }
}
