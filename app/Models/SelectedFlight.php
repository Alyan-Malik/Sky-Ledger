<?php
// app/Models/SelectedFlight.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SelectedFlight extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'flight_search_id',
        'duffel_offer_id',
        'provider',
        'airline_name',
        'airline_code',
        'airline_logo',
        'flight_number',
        'origin_airport',
        'origin_city',
        'origin_iata',
        'destination_airport',
        'destination_city',
        'destination_iata',
        'departure_datetime',
        'arrival_datetime',
        'duration',
        'stops',
        'cabin_class',
        'fare_brand',
        'aircraft',
        'terminal',
        'baggage',
        'currency',
        'base_price',
        'service_charge',
        'total_price',
        'offer_json',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'departure_datetime' => 'datetime',
        'arrival_datetime' => 'datetime',
        'expires_at' => 'datetime',
        'baggage' => 'array',
        'offer_json' => 'array',
        'base_price' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total_price' => 'decimal:2',
        'stops' => 'integer',
    ];

    protected $attributes = [
        'status' => 'active',
        'provider' => 'duffel',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function flightSearch(): BelongsTo
    {
        return $this->belongsTo(FlightSearch::class);
    }

    public function booking(): HasOne
    {
        return $this->hasOne(Booking::class, 'selected_flight_id');
    }

    public function originAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'origin_iata', 'iata_code');
    }

    public function destinationAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'destination_iata', 'iata_code');
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && !$this->isExpired();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }


    public function getPassengerCountsAttribute(): array
{
    $search = $this->flightSearch;
    
    if ($search) {
        return [
            'adults' => $search->adults ?? 1,
            'children' => $search->children ?? 0,
            'infants' => $search->infants ?? 0,
        ];
    }
    
    // Fallback from offer_json
    $offerData = $this->offer_json ?? [];
    $passengers = $offerData['passengers'] ?? [];
    
    $adults = 0;
    $children = 0;
    $infants = 0;
    
    foreach ($passengers as $p) {
        if (($p['type'] ?? '') === 'adult') $adults++;
        if (($p['type'] ?? '') === 'child') $children++;
        if (($p['type'] ?? '') === 'infant_without_seat') $infants++;
    }
    
    return [
        'adults' => max($adults, 1),
        'children' => $children,
        'infants' => $infants,
    ];
}
}