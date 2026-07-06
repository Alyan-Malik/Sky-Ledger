<?php
// app/Http/Resources/AirportResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AirportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'iata' => $this->iata_code,
            'airport_name' => $this->airport_name,
            'city' => $this->city,
            'country' => $this->country,
            'full_name' => $this->full_name,
            'location' => $this->location,
        ];
    }
}