<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesSnapshot extends Model
{
    protected $fillable = [
        'event_id', 'section_id', 'sold', 'available', 'revenue',
        'velocity_hourly', 'velocity_daily', 'sellthrough_pct',
        'days_to_event', 'captured_at',
    ];

    protected $casts = [
        'captured_at' => 'datetime',
        'revenue' => 'decimal:2',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
}
