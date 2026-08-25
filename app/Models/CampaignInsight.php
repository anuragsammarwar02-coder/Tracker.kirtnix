<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'date',
        'spend',
        'reach',
        'impressions',
        'clicks',
        'subscribers',
        'cost_per_subscriber',
        'ctr',
        'cpm',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2',
        'cost_per_subscriber' => 'decimal:2',
        'ctr' => 'decimal:2',
        'cpm' => 'decimal:2',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
