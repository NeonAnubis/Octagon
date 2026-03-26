<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticketmaster_id', 'name', 'slug', 'description', 'venue', 'city',
        'state', 'country', 'market', 'event_date', 'on_sale_date',
        'total_capacity', 'total_sold', 'total_revenue', 'fight_card_tier',
        'fight_card', 'status', 'image_url',
    ];

    protected $casts = [
        'event_date' => 'datetime',
        'on_sale_date' => 'datetime',
        'fight_card' => 'array',
        'total_revenue' => 'decimal:2',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function salesSnapshots(): HasMany
    {
        return $this->hasMany(SalesSnapshot::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function forecasts(): HasMany
    {
        return $this->hasMany(Forecast::class);
    }

    public function getSellThroughPercentageAttribute(): float
    {
        if ($this->total_capacity === 0) return 0;
        return round(($this->total_sold / $this->total_capacity) * 100, 1);
    }

    public function getDaysToEventAttribute(): int
    {
        return max(0, (int) now()->diffInDays($this->event_date, false));
    }
}
