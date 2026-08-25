<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoginRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email',
        'ip_address',
        'user_agent',
        'location',
        'device',
        'status',
        'requested_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
