<?php
// app/Http/Resources/BookingResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
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
                'gender' => $this->gender,
                'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
                'nationality' => $this->nationality,
                'passport_number' => $this->passport_number,
                'passport_expiry' => $this->passport_expiry?->format('Y-m-d'),
                'cnic' => $this->cnic,
            ],
            
            'contact' => [
                'email' => $this->email,
                'phone' => $this->phone,
                'emergency_contact' => $this->emergency_contact,
            ],
            
            'address' => [
                'address' => $this->address,
                'city' => $this->city,
                'country' => $this->country,
                'zip_code' => $this->zip_code,
            ],
            
            'preferences' => [
                'seat_number' => $this->seat_number,
                'meal_preference' => $this->meal_preference,
                'special_assistance' => $this->special_assistance,
            ],
            
            'flight' => new SelectedFlightResource($this->whenLoaded('selectedFlight')),
            
            'status' => [
                'booking' => $this->booking_status,
                'ticket' => $this->ticket_status,
            ],
            
            'remarks' => $this->remarks,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}