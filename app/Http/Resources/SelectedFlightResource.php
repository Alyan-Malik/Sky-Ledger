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
            
            // Extract ALL segments from offer_json
            'segments' => $this->extractSegmentsFromOffer($offerData),
            
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
     * Extract all segments from offer data
     */
    private function extractSegmentsFromOffer(?array $offerData): array
    {
        if (!$offerData) return [];

        $segments = [];

        // Try slices[0].segments first (Duffel raw format)
        if (isset($offerData['slices'][0]['segments']) && is_array($offerData['slices'][0]['segments'])) {
            $segments = $offerData['slices'][0]['segments'];
        }
        // Try segments directly (normalized format)
        elseif (isset($offerData['segments']) && is_array($offerData['segments'])) {
            $segments = $offerData['segments'];
        }

        return $this->normalizeSegments($segments);
    }

    /**
     * Normalize segments to consistent format
     */
    private function normalizeSegments(array $segments): array
    {
        return array_values(array_map(function ($seg) {
            if (!is_array($seg)) return null;

            return [
                'id' => $seg['id'] ?? '',
                'flight_number' => $seg['marketing_carrier_flight_number'] 
                    ?? $seg['operating_carrier_flight_number'] 
                    ?? $seg['flight_number'] 
                    ?? '',
                'airline_name' => $seg['marketing_carrier']['name'] 
                    ?? $seg['operating_carrier']['name'] 
                    ?? $seg['airline_name'] 
                    ?? $this->airline_name,
                'airline_iata' => $seg['marketing_carrier']['iata_code'] 
                    ?? $seg['operating_carrier']['iata_code'] 
                    ?? $seg['airline_iata'] 
                    ?? $this->airline_code,
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
                    ?? $this->cabin_class,
                'distance' => $seg['distance'] ?? null,
            ];
        }, $segments));
    }

    /**
     * Extract return flight data from offer_json
     */
    private function extractReturnFlight(?array $offerData): ?array
    {
        if (!$offerData) return null;

        $returnSlice = null;
        $returnSegments = [];

        // Check return_slice first
        if (isset($offerData['return_slice']) && !empty($offerData['return_slice'])) {
            $returnSlice = $offerData['return_slice'];
            $returnSegments = $returnSlice['segments'] ?? [];
        } 
        // Check slices array for round trip
        elseif (isset($offerData['slices']) && is_array($offerData['slices']) && count($offerData['slices']) > 1) {
            $returnSlice = $offerData['slices'][1];
            $returnSegments = $returnSlice['segments'] ?? [];
        }

        if (!$returnSlice) return null;

        $firstSegment = !empty($returnSegments) ? $returnSegments[0] : [];
        $lastSegment = !empty($returnSegments) ? end($returnSegments) : $firstSegment;

        // Extract origin/destination safely
        $originIata = $returnSlice['origin']['iata_code'] 
            ?? $returnSlice['origin']['iata'] 
            ?? $firstSegment['origin']['iata_code'] 
            ?? $this->destination_iata 
            ?? '???';
        $originCity = $returnSlice['origin']['city_name'] 
            ?? $returnSlice['origin']['city'] 
            ?? $firstSegment['origin']['city_name'] 
            ?? $this->destination_city 
            ?? '';
        $originAirport = $returnSlice['origin']['name'] 
            ?? $returnSlice['origin']['airport'] 
            ?? $firstSegment['origin']['name'] 
            ?? $this->destination_airport 
            ?? '';

        $destIata = $returnSlice['destination']['iata_code'] 
            ?? $returnSlice['destination']['iata'] 
            ?? $lastSegment['destination']['iata_code'] 
            ?? $this->origin_iata 
            ?? '???';
        $destCity = $returnSlice['destination']['city_name'] 
            ?? $returnSlice['destination']['city'] 
            ?? $lastSegment['destination']['city_name'] 
            ?? $this->origin_city 
            ?? '';
        $destAirport = $returnSlice['destination']['name'] 
            ?? $returnSlice['destination']['airport'] 
            ?? $lastSegment['destination']['name'] 
            ?? $this->origin_airport 
            ?? '';

        return [
            'origin' => [
                'iata' => strtoupper($originIata),
                'city' => $originCity,
                'airport' => $originAirport,
            ],
            'destination' => [
                'iata' => strtoupper($destIata),
                'city' => $destCity,
                'airport' => $destAirport,
            ],
            'departure_time' => $returnSlice['departure_time'] 
                ?? $firstSegment['departing_at'] 
                ?? null,
            'arrival_time' => $returnSlice['arrival_time'] 
                ?? $lastSegment['arriving_at'] 
                ?? null,
            'duration' => $returnSlice['duration'] ?? '',
            'stops' => $returnSlice['stops'] ?? (count($returnSegments) - 1),
            'flight_number' => $returnSlice['flight_number'] 
                ?? $firstSegment['marketing_carrier_flight_number'] 
                ?? $firstSegment['flight_number'] 
                ?? $this->flight_number,
            'segments' => $this->normalizeSegments($returnSegments),
        ];
    }
}