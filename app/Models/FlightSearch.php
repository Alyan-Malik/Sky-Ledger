<?php
// app/Models/FlightSearch.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlightSearch extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'trip_type',
        'origin_iata',
        'destination_iata',
        'departure_date',
        'return_date',
        'adults',
        'children',
        'infants',
        'cabin_class',
        'search_metadata',
    ];

    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'adults' => 'integer',
        'children' => 'integer',
        'infants' => 'integer',
        'search_metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function originAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'origin_iata', 'iata_code');
    }

    public function destinationAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'destination_iata', 'iata_code');
    }

    /**
     * Scope for user's recent searches
     */
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->where('user_id', auth()->id())
            ->latest()
            ->limit($limit);
    }
}