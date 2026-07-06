<?php
// app/Http/Resources/FlightOfferResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'airline' => [
                'name' => $this->airline_name,
                'iata' => $this->airline_iata,
                'logo' => $this->offer_data['airline']['logo_url'] ?? null,
            ],
            'flight_number' => $this->flight_number,
            'route' => [
                'origin' => [
                    'iata' => $this->origin_iata,
                    'airport' => $this->originAirport->airport_name ?? '',
                    'city' => $this->originAirport->city ?? '',
                ],
                'destination' => [
                    'iata' => $this->destination_iata,
                    'airport' => $this->destinationAirport->airport_name ?? '',
                    'city' => $this->destinationAirport->city ?? '',
                ],
            ],
            'schedule' => [
                'departure' => $this->departure_time?->format('Y-m-d H:i:s'),
                'arrival' => $this->arrival_time?->format('Y-m-d H:i:s'),
                'duration' => $this->duration,
            ],
            'pricing' => [
                'base_fare' => $this->base_price,
                'service_charge' => $this->service_charge,
                'grand_total' => $this->grand_total,
                'currency' => $this->currency,
            ],
            'details' => [
                'stops' => $this->stops,
                'cabin_class' => $this->cabin_class,
                'baggage' => $this->offer_data['baggage'] ?? null,
                'segments' => $this->offer_data['segments'] ?? [],
            ],
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
        ];
    }
}