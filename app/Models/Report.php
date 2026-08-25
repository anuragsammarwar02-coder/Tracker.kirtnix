<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'user_id',
        'title',
        'date_range',
        'spend',
        'reach',
        'views',
        'joins',
        'exits',
        'cost_per_join',
        'conversion_rate',
        'ai_summary',
        'ai_observations',
        'ai_recommendations',
        'ai_issues',
        'ai_next_actions',
        'status',
    ];

    protected $casts = [
        'spend' => 'decimal:2',
        'cost_per_join' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
