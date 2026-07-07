<?php
// app/Http/Resources/BookingResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $selectedFlight = $this->selectedFlight;
        $offerData = $selectedFlight->offer_json ?? [];
        $returnFlight = $this->extractReturnFlightSafe($offerData, $selectedFlight);

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
            
            'additional_passengers' => $this->additional_passengers ?? [],
            
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
            
            'baggage' => [
                'checked_count' => $this->checked_baggage_count ?? 0,
                'hand_luggage_count' => $this->hand_luggage_count ?? 0,
            ],
            
            'assistance' => [
                'wheelchair' => $this->wheelchair_required ?? 'none',
                'priority_pass' => (bool) ($this->priority_pass ?? false),
            ],
            
            'flight' => [
                'airline' => [
                    'name' => $selectedFlight->airline_name ?? 'N/A',
                    'code' => $selectedFlight->airline_code ?? 'N/A',
                    'logo' => $selectedFlight->airline_logo ?? null,
                ],
                'flight' => [
                    'number' => $selectedFlight->flight_number ?? 'N/A',
                    'cabin_class' => $selectedFlight->cabin_class ?? 'N/A',
                    'aircraft' => $selectedFlight->aircraft ?? null,
                    'terminal' => $selectedFlight->terminal ?? null,
                    'fare_brand' => $selectedFlight->fare_brand ?? null,
                ],
                'route' => [
                    'origin' => [
                        'iata' => $selectedFlight->origin_iata ?? 'N/A',
                        'city' => $selectedFlight->origin_city ?? 'N/A',
                        'airport' => $selectedFlight->origin_airport ?? 'N/A',
                    ],
                    'destination' => [
                        'iata' => $selectedFlight->destination_iata ?? 'N/A',
                        'city' => $selectedFlight->destination_city ?? 'N/A',
                        'airport' => $selectedFlight->destination_airport ?? 'N/A',
                    ],
                ],
                'schedule' => [
                    'departure' => $selectedFlight->departure_datetime?->format('Y-m-d H:i:s'),
                    'arrival' => $selectedFlight->arrival_datetime?->format('Y-m-d H:i:s'),
                    'duration' => $selectedFlight->duration ?? 'N/A',
                    'stops' => $selectedFlight->stops ?? 0,
                ],
                'pricing' => [
                    'base_price' => number_format($selectedFlight->base_price, 2, '.', ''),
                    'service_charge' => number_format($selectedFlight->service_charge, 2, '.', ''),
                    'total_price' => number_format($selectedFlight->total_price, 2, '.', ''),
                    'currency' => $selectedFlight->currency ?? 'USD',
                ],
                'return_flight' => $returnFlight,
                'is_round_trip' => !is_null($returnFlight),
            ],
            
            'status' => [
                'booking' => $this->booking_status,
                'ticket' => $this->ticket_status,
            ],
            
            'remarks' => $this->remarks,
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    /**
     * Safely extract return flight data
     */
    private function extractReturnFlightSafe(?array $offerData, $selectedFlight): ?array
    {
        if (!$offerData) return null;

        $returnSlice = null;

        if (isset($offerData['return_slice']) && !empty($offerData['return_slice']) && is_array($offerData['return_slice'])) {
            $returnSlice = $offerData['return_slice'];
        } elseif (isset($offerData['slices']) && is_array($offerData['slices']) && count($offerData['slices']) > 1) {
            $returnSlice = $offerData['slices'][1];
        }

        if (!$returnSlice || !is_array($returnSlice)) return null;

        $segments = $returnSlice['segments'] ?? [];
        if (!is_array($segments)) $segments = [];
        
        $firstSegment = !empty($segments) ? ($segments[0] ?? []) : [];
        $lastSegment = !empty($segments) ? (end($segments) ?: $firstSegment) : $firstSegment;

        if (!is_array($firstSegment)) $firstSegment = [];
        if (!is_array($lastSegment)) $lastSegment = [];

        return [
            'origin' => $this->safeAirport(
                $returnSlice['origin'] ?? [],
                $firstSegment['origin'] ?? [],
                $selectedFlight->destination_iata ?? '???',
                $selectedFlight->destination_city ?? '',
                $selectedFlight->destination_airport ?? ''
            ),
            'destination' => $this->safeAirport(
                $returnSlice['destination'] ?? [],
                $lastSegment['destination'] ?? [],
                $selectedFlight->origin_iata ?? '???',
                $selectedFlight->origin_city ?? '',
                $selectedFlight->origin_airport ?? ''
            ),
            'departure_time' => $returnSlice['departure_time'] ?? $firstSegment['departing_at'] ?? null,
            'arrival_time' => $returnSlice['arrival_time'] ?? $lastSegment['arriving_at'] ?? null,
            'duration' => $returnSlice['duration'] ?? '',
            'stops' => (int)($returnSlice['stops'] ?? (count($segments) - 1)),
            'flight_number' => $returnSlice['flight_number'] ?? $firstSegment['marketing_carrier_flight_number'] ?? $selectedFlight->flight_number,
            'segments' => $segments,
        ];
    }

    /**
     * Safe airport extraction
     */
    private function safeAirport($sliceData, $segmentData, $fallbackIata, $fallbackCity, $fallbackAirport): array
    {
        if (!is_array($sliceData)) $sliceData = [];
        if (!is_array($segmentData)) $segmentData = [];

        return [
            'iata' => strtoupper(
                ($sliceData['iata'] ?? $sliceData['iata_code'] ?? $segmentData['iata'] ?? $segmentData['iata_code'] ?? $fallbackIata) ?: '???'
            ),
            'city' => $sliceData['city'] ?? $sliceData['city_name'] ?? $segmentData['city'] ?? $segmentData['city_name'] ?? $fallbackCity ?? '',
            'airport' => $sliceData['airport'] ?? $sliceData['name'] ?? $segmentData['airport'] ?? $segmentData['name'] ?? $fallbackAirport ?? '',
        ];
    }
}