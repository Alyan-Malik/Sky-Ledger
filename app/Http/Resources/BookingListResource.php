<?php
// app/Http/Resources/BookingListResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_id' => $this->booking_id,
            'pnr_number' => $this->pnr_number,
            'eticket_number' => $this->eticket_number,
            
            'passenger' => [
                'first_name' => $this->passenger_first_name,
                'last_name' => $this->passenger_last_name,
                'full_name' => $this->full_name,
            ],
            
            'flight' => [
                'airline_name' => $this->selectedFlight->airline_name ?? 'N/A',
                'airline_code' => $this->selectedFlight->airline_code ?? 'N/A',
                'airline_logo' => $this->selectedFlight->airline_logo ?? null,
                'flight_number' => $this->selectedFlight->flight_number ?? 'N/A',
                'origin_iata' => $this->selectedFlight->origin_iata ?? 'N/A',
                'destination_iata' => $this->selectedFlight->destination_iata ?? 'N/A',
                'departure_datetime' => $this->selectedFlight->departure_datetime?->format('Y-m-d H:i:s'),
                'cabin_class' => $this->selectedFlight->cabin_class ?? 'N/A',
                'total_price' => $this->selectedFlight->total_price ?? 0,
                'currency' => $this->selectedFlight->currency ?? 'USD',
            ],
            
            'status' => [
                'booking' => $this->booking_status,
                'ticket' => $this->ticket_status,
            ],
            
            'created_at' => $this->created_at->format('Y-m-d'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}