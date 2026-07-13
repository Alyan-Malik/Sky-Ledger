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
        $returnFlight = $this->extractReturnFlight($offerData, $selectedFlight);

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
                'checked_count' => (int)($this->checked_baggage_count ?? 0),
                'hand_luggage_count' => (int)($this->hand_luggage_count ?? 0),
            ],
            
            'assistance' => [
                'wheelchair' => $this->wheelchair_required ?? 'none',
                'priority_pass' => (bool)($this->priority_pass ?? false),
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
                // Extract segments from offer_json
                'segments' => $this->extractSegments($offerData),
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
     * Extract segments from offer data
     */
    private function extractSegments(?array $offerData): array
    {
        if (!$offerData) return [];

        $segments = [];

        if (isset($offerData['slices'][0]['segments']) && is_array($offerData['slices'][0]['segments'])) {
            $segments = $offerData['slices'][0]['segments'];
        } elseif (isset($offerData['segments']) && is_array($offerData['segments'])) {
            $segments = $offerData['segments'];
        }

        return array_values(array_map(function ($seg) {
            if (!is_array($seg)) return null;
            
            $airline = $this->selectedFlight;
            
            return [
                'id' => $seg['id'] ?? '',
                'flight_number' => $seg['marketing_carrier_flight_number'] 
                    ?? $seg['operating_carrier_flight_number'] 
                    ?? $seg['flight_number'] 
                    ?? '',
                'airline_name' => $seg['marketing_carrier']['name'] 
                    ?? $seg['operating_carrier']['name'] 
                    ?? $seg['airline_name'] 
                    ?? $airline->airline_name,
                'airline_iata' => $seg['marketing_carrier']['iata_code'] 
                    ?? $seg['operating_carrier']['iata_code'] 
                    ?? $seg['airline_iata'] 
                    ?? $airline->airline_code,
                'aircraft' => is_array($seg['aircraft'] ?? null) 
                    ? ($seg['aircraft']['name'] ?? null) 
                    : ($seg['aircraft'] ?? null),
                'origin' => [
                    'iata' => $seg['origin']['iata_code'] ?? $seg['origin']['iata'] ?? '',
                    'airport' => $seg['origin']['name'] ?? $seg['origin']['airport'] ?? '',
                    'city' => $seg['origin']['city_name'] ?? $seg['origin']['city'] ?? '',
                    'terminal' => $seg['origin_terminal'] ?? $seg['origin']['terminal'] ?? null,
                ],
                'destination' => [
                    'iata' => $seg['destination']['iata_code'] ?? $seg['destination']['iata'] ?? '',
                    'airport' => $seg['destination']['name'] ?? $seg['destination']['airport'] ?? '',
                    'city' => $seg['destination']['city_name'] ?? $seg['destination']['city'] ?? '',
                    'terminal' => $seg['destination_terminal'] ?? $seg['destination']['terminal'] ?? null,
                ],
                'departure_time' => $seg['departing_at'] ?? $seg['departure_time'] ?? null,
                'arrival_time' => $seg['arriving_at'] ?? $seg['arrival_time'] ?? null,
                'duration' => $seg['duration'] ?? '',
                'cabin_class' => $seg['passengers'][0]['cabin_class_marketing_name'] 
                    ?? $seg['passengers'][0]['cabin_class'] 
                    ?? $seg['cabin_class'] 
                    ?? $airline->cabin_class,
                'distance' => $seg['distance'] ?? null,
            ];
        }, $segments));
    }

    /**
     * Extract return flight data
     */
    private function extractReturnFlight(?array $offerData, $selectedFlight): ?array
    {
        if (!$offerData) return null;

        $returnSlice = null;
        $returnSegments = [];

        if (isset($offerData['return_slice']) && !empty($offerData['return_slice']) && is_array($offerData['return_slice'])) {
            $returnSlice = $offerData['return_slice'];
            $returnSegments = $returnSlice['segments'] ?? [];
        } elseif (isset($offerData['slices']) && is_array($offerData['slices']) && count($offerData['slices']) > 1) {
            $returnSlice = $offerData['slices'][1];
            $returnSegments = $returnSlice['segments'] ?? [];
        }

        if (!$returnSlice) return null;

        $firstSegment = !empty($returnSegments) ? $returnSegments[0] : [];
        $lastSegment = !empty($returnSegments) ? end($returnSegments) : $firstSegment;

        return [
            'origin' => [
                'iata' => strtoupper(
                    ($returnSlice['origin']['iata_code'] ?? $returnSlice['origin']['iata'] ?? $firstSegment['origin']['iata_code'] ?? $selectedFlight->destination_iata) ?: '???'
                ),
                'city' => $returnSlice['origin']['city_name'] ?? $returnSlice['origin']['city'] ?? $firstSegment['origin']['city_name'] ?? $selectedFlight->destination_city ?? '',
                'airport' => $returnSlice['origin']['name'] ?? $returnSlice['origin']['airport'] ?? $firstSegment['origin']['name'] ?? $selectedFlight->destination_airport ?? '',
            ],
            'destination' => [
                'iata' => strtoupper(
                    ($returnSlice['destination']['iata_code'] ?? $returnSlice['destination']['iata'] ?? $lastSegment['destination']['iata_code'] ?? $selectedFlight->origin_iata) ?: '???'
                ),
                'city' => $returnSlice['destination']['city_name'] ?? $returnSlice['destination']['city'] ?? $lastSegment['destination']['city_name'] ?? $selectedFlight->origin_city ?? '',
                'airport' => $returnSlice['destination']['name'] ?? $returnSlice['destination']['airport'] ?? $lastSegment['destination']['name'] ?? $selectedFlight->origin_airport ?? '',
            ],
            'departure_time' => $returnSlice['departure_time'] ?? $firstSegment['departing_at'] ?? null,
            'arrival_time' => $returnSlice['arrival_time'] ?? $lastSegment['arriving_at'] ?? null,
            'duration' => $returnSlice['duration'] ?? '',
            'stops' => (int)($returnSlice['stops'] ?? (count($returnSegments) - 1)),
            'flight_number' => $returnSlice['flight_number'] ?? $firstSegment['marketing_carrier_flight_number'] ?? $selectedFlight->flight_number,
            'segments' => array_values(array_map(function ($seg) use ($selectedFlight) {
                if (!is_array($seg)) return null;
                return [
                    'id' => $seg['id'] ?? '',
                    'flight_number' => $seg['marketing_carrier_flight_number'] ?? $seg['operating_carrier_flight_number'] ?? $seg['flight_number'] ?? '',
                    'airline_name' => $seg['marketing_carrier']['name'] ?? $seg['operating_carrier']['name'] ?? $seg['airline_name'] ?? $selectedFlight->airline_name,
                    'airline_iata' => $seg['marketing_carrier']['iata_code'] ?? $seg['operating_carrier']['iata_code'] ?? $seg['airline_iata'] ?? $selectedFlight->airline_code,
                    'aircraft' => is_array($seg['aircraft'] ?? null) ? ($seg['aircraft']['name'] ?? null) : ($seg['aircraft'] ?? null),
                    'origin' => [
                        'iata' => $seg['origin']['iata_code'] ?? $seg['origin']['iata'] ?? '',
                        'airport' => $seg['origin']['name'] ?? $seg['origin']['airport'] ?? '',
                        'city' => $seg['origin']['city_name'] ?? $seg['origin']['city'] ?? '',
                        'terminal' => $seg['origin_terminal'] ?? $seg['origin']['terminal'] ?? null,
                    ],
                    'destination' => [
                        'iata' => $seg['destination']['iata_code'] ?? $seg['destination']['iata'] ?? '',
                        'airport' => $seg['destination']['name'] ?? $seg['destination']['airport'] ?? '',
                        'city' => $seg['destination']['city_name'] ?? $seg['destination']['city'] ?? '',
                        'terminal' => $seg['destination_terminal'] ?? $seg['destination']['terminal'] ?? null,
                    ],
                    'departure_time' => $seg['departing_at'] ?? $seg['departure_time'] ?? null,
                    'arrival_time' => $seg['arriving_at'] ?? $seg['arrival_time'] ?? null,
                    'duration' => $seg['duration'] ?? '',
                    'cabin_class' => $seg['passengers'][0]['cabin_class_marketing_name'] ?? $seg['passengers'][0]['cabin_class'] ?? $seg['cabin_class'] ?? $selectedFlight->cabin_class,
                ];
            }, $returnSegments)),
        ];
    }
}