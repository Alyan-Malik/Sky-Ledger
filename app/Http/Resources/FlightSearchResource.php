<?php
// app/Http/Resources/FlightSearchResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'trip_type' => $this->trip_type,
            'origin' => new AirportResource($this->whenLoaded('originAirport')),
            'destination' => new AirportResource($this->whenLoaded('destinationAirport')),
            'departure_date' => $this->departure_date->format('Y-m-d'),
            'return_date' => $this->return_date?->format('Y-m-d'),
            'passengers' => [
                'adults' => $this->adults,
                'children' => $this->children,
                'infants' => $this->infants,
                'total' => $this->adults + $this->children + $this->infants,
            ],
            'cabin_class' => $this->cabin_class,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}