<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignMetric extends Model
{
    protected $fillable = [
        'campaign_id', 'event_id', 'date', 'spend', 'impressions', 'clicks',
        'conversions', 'revenue_attributed', 'cpm', 'cpc', 'cpa', 'roas',
        'ctr', 'reach', 'engagement', 'sends', 'opens', 'unique_clicks',
        'open_rate', 'click_rate',
    ];

    protected $casts = [
        'date' => 'date',
        'spend' => 'decimal:2',
        'revenue_attributed' => 'decimal:2',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
