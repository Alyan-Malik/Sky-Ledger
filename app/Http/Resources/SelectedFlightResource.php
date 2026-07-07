<?php
// app/Http/Resources/SelectedFlightResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SelectedFlightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $offerData = $this->offer_json ?? [];
        $returnFlight = $this->extractReturnFlight($offerData);

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
            
            'pricing' => [
                'base_price' => number_format($this->base_price, 2, '.', ''),
                'service_charge' => number_format($this->service_charge, 2, '.', ''),
                'total_price' => number_format($this->total_price, 2, '.', ''),
                'currency' => $this->currency,
            ],
            
            'return_flight' => $returnFlight,
            'is_round_trip' => !is_null($returnFlight),
            
            'passenger_counts' => $this->passenger_counts,
            'baggage' => $this->baggage,
            'status' => $this->status,
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }

    /**
     * Extract return flight data from offer_json
     */
    private function extractReturnFlight(?array $offerData): ?array
    {
        if (!$offerData) return null;

        $returnSlice = null;

        // Check return_slice first
        if (isset($offerData['return_slice']) && !empty($offerData['return_slice'])) {
            $returnSlice = $offerData['return_slice'];
        } 
        // Check slices array for round trip
        elseif (isset($offerData['slices']) && is_array($offerData['slices']) && count($offerData['slices']) > 1) {
            $returnSlice = $offerData['slices'][1];
        }

        if (!$returnSlice || !is_array($returnSlice)) return null;

        // Extract segments safely
        $segments = $returnSlice['segments'] ?? [];
        if (!is_array($segments)) $segments = [];
        
        $firstSegment = !empty($segments) ? ($segments[0] ?? []) : [];
        $lastSegment = !empty($segments) ? (end($segments) ?: $firstSegment) : $firstSegment;

        // Get origin data - handle both array and string cases
        $originData = $returnSlice['origin'] ?? [];
        if (!is_array($originData)) {
            $originData = [];
        }
        $originSegmentData = $firstSegment['origin'] ?? [];
        if (!is_array($originSegmentData)) {
            $originSegmentData = [];
        }

        // Get destination data
        $destData = $returnSlice['destination'] ?? [];
        if (!is_array($destData)) {
            $destData = [];
        }
        $destSegmentData = $lastSegment['destination'] ?? [];
        if (!is_array($destSegmentData)) {
            $destSegmentData = [];
        }

        // Extract origin - return flight origin = outbound destination
        $origin = $this->extractAirportDataSafe(
            $originData,
            $originSegmentData,
            $this->destination_iata ?? '???',
            $this->destination_city ?? '',
            $this->destination_airport ?? ''
        );

        // Extract destination - return flight destination = outbound origin
        $destination = $this->extractAirportDataSafe(
            $destData,
            $destSegmentData,
            $this->origin_iata ?? '???',
            $this->origin_city ?? '',
            $this->origin_airport ?? ''
        );

        return [
            'origin' => $origin,
            'destination' => $destination,
            'departure_time' => $returnSlice['departure_time'] 
                ?? $firstSegment['departing_at'] 
                ?? null,
            'arrival_time' => $returnSlice['arrival_time'] 
                ?? $lastSegment['arriving_at'] 
                ?? null,
            'duration' => $returnSlice['duration'] ?? '',
            'stops' => $returnSlice['stops'] ?? (count($segments) - 1),
            'flight_number' => $returnSlice['flight_number'] 
                ?? $firstSegment['marketing_carrier_flight_number'] 
                ?? $firstSegment['operating_carrier_flight_number'] 
                ?? $this->flight_number,
            'segments' => $segments,
        ];
    }

    /**
     * Safely extract airport data handling mixed types
     */
    private function extractAirportDataSafe(
        $sliceData,
        $segmentData,
        string $fallbackIata,
        string $fallbackCity,
        string $fallbackAirport
    ): array {
        // Ensure we're working with arrays
        if (!is_array($sliceData)) $sliceData = [];
        if (!is_array($segmentData)) $segmentData = [];

        $iata = $sliceData['iata'] 
            ?? $sliceData['iata_code'] 
            ?? $segmentData['iata'] 
            ?? $segmentData['iata_code'] 
            ?? $fallbackIata 
            ?? '???';
            
        $city = $sliceData['city'] 
            ?? $sliceData['city_name'] 
            ?? $segmentData['city'] 
            ?? $segmentData['city_name'] 
            ?? $fallbackCity 
            ?? '';
            
        $airport = $sliceData['airport'] 
            ?? $sliceData['name'] 
            ?? $segmentData['airport'] 
            ?? $segmentData['name'] 
            ?? $fallbackAirport 
            ?? '';

        return [
            'iata' => is_string($iata) ? strtoupper($iata) : '???',
            'city' => is_string($city) ? $city : '',
            'airport' => is_string($airport) ? $airport : '',
        ];
    }
}