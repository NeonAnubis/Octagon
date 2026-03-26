<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'external_id', 'source', 'type', 'name',
        'status', 'start_date', 'end_date', 'budget', 'targeting',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'targeting' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function metrics(): HasMany
    {
        return $this->hasMany(CampaignMetric::class);
    }

    public function getTotalSpendAttribute(): float
    {
        return $this->metrics()->sum('spend');
    }

    public function getTotalConversionsAttribute(): int
    {
        return $this->metrics()->sum('conversions');
    }
}
