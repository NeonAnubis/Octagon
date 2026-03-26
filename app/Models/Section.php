<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id', 'name', 'price_tier', 'base_price', 'current_price',
        'capacity', 'sold', 'held', 'revenue', 'status',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'revenue' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(SalesSnapshot::class);
    }

    public function getSellThroughPercentageAttribute(): float
    {
        if ($this->capacity === 0) return 0;
        return round(($this->sold / $this->capacity) * 100, 1);
    }
}
