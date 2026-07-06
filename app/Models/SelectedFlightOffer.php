<?php
// app/Models/SelectedFlightOffer.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelectedFlightOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'flight_search_id',
        'duffel_offer_id',
        'airline_name',
        'airline_iata',
        'flight_number',
        'origin_iata',
        'destination_iata',
        'departure_time',
        'arrival_time',
        'duration',
        'stops',
        'cabin_class',
        'base_price',
        'currency',
        'service_charge',
        'grand_total',
        'offer_data',
        'passenger_details',
        'booking_info',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'departure_time' => 'datetime',
        'arrival_time' => 'datetime',
        'base_price' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'offer_data' => 'array',
        'passenger_details' => 'array',
        'booking_info' => 'array',
        'expires_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'selected',
    ];

    public function flightSearch(): BelongsTo
    {
        return $this->belongsTo(FlightSearch::class);
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
     * Scope for active offers (not expired)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'selected')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}