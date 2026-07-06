<?php
// app/Http/Resources/SelectedFlightResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SelectedFlightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'duffel_offer_id' => $this->duffel_offer_id,
            
            'airline' => [
                'name' => $this->airline_name,
                'code' => $this->airline_code,
                'logo' => $this->airline_logo,
            ],
            
            'flight' => [
                'number' => $this->flight_number,
                'aircraft' => $this->aircraft,
                'terminal' => $this->terminal,
                'cabin_class' => $this->cabin_class,
                'fare_brand' => $this->fare_brand,
            ],
            
            'route' => [
                'origin' => [
                    'iata' => $this->origin_iata,
                    'airport' => $this->origin_airport,
                    'city' => $this->origin_city,
                ],
                'destination' => [
                    'iata' => $this->destination_iata,
                    'airport' => $this->destination_airport,
                    'city' => $this->destination_city,
                ],
            ],
            
            'schedule' => [
                'departure' => $this->departure_datetime?->format('Y-m-d H:i:s'),
                'arrival' => $this->arrival_datetime?->format('Y-m-d H:i:s'),
                'duration' => $this->duration,
                'stops' => $this->stops,
            ],
            
            // Return EXACT pricing as stored
            'pricing' => [
                'base_price' => number_format($this->base_price, 2, '.', ''),
                'service_charge' => number_format($this->service_charge, 2, '.', ''),
                'total_price' => number_format($this->total_price, 2, '.', ''),
                'currency' => $this->currency,
            ],
            
            'baggage' => $this->baggage,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}