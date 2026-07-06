<?php
// app/Services/Duffel/Transformers/OfferTransformer.php

namespace App\Services\Duffel\Transformers;

class OfferTransformer
{
    /**
     * Transform a collection of Duffel offers
     */
    public function transformCollection(array $offers): array
    {
        return array_map([$this, 'transform'], $offers);
    }

    /**
     * Transform a single Duffel offer into standardized format
     */
    public function transform(array $duffelOffer): array
    {
        // Extract first slice (outbound journey)
        $firstSlice = $duffelOffer['slices'][0] ?? [];
        $firstSegment = $firstSlice['segments'][0] ?? [];
        $lastSegment = end($firstSlice['segments']) ?: $firstSegment;
        
        // Get owner (airline) information
        $owner = $duffelOffer['owner'] ?? [];
        
        return [
            // Identification
            'id' => $duffelOffer['id'] ?? '',
            'offer_request_id' => null, // Not needed in response
            
            // Airline Information
            'airline' => [
                'name' => $owner['name'] ?? 'Unknown Airline',
                'iata_code' => $owner['iata_code'] ?? '',
                'logo_url' => $owner['logo_symbol_url'] ?? $owner['logo_lockup_url'] ?? null,
            ],
            
            // Flight Details
            'flight_number' => $firstSegment['marketing_carrier_flight_number'] ?? 
                              $firstSegment['operating_carrier_flight_number'] ?? '',
            'aircraft' => $firstSegment['aircraft'] ?? null,
            
            // Route Information
            'route' => [
                'origin' => [
                    'iata' => $firstSlice['origin']['iata_code'] ?? '',
                    'city' => $firstSlice['origin']['city_name'] ?? $firstSlice['origin']['name'] ?? '',
                    'airport' => $firstSlice['origin']['name'] ?? '',
                ],
                'destination' => [
                    'iata' => $firstSlice['destination']['iata_code'] ?? '',
                    'city' => $firstSlice['destination']['city_name'] ?? $firstSlice['destination']['name'] ?? '',
                    'airport' => $firstSlice['destination']['name'] ?? '',
                ],
                'departure_time' => $firstSegment['departing_at'] ?? null,
                'arrival_time' => $lastSegment['arriving_at'] ?? null,
                'duration' => $firstSlice['duration'] ?? null,
            ],
            
            // Stops Information
            'stops' => [
                'count' => count($firstSlice['segments'] ?? []) - 1,
                'details' => $this->transformLayovers($firstSlice['segments'] ?? []),
            ],
            
            // Pricing
            'pricing' => [
                'base_amount' => floatval($duffelOffer['base_amount'] ?? $duffelOffer['total_amount'] ?? 0),
                'base_fare' => floatval($duffelOffer['base_amount'] ?? 0),
                'currency' => $duffelOffer['total_currency'] ?? 'USD',
                'tax_amount' => floatval($duffelOffer['tax_amount'] ?? 0),
                'total_amount' => floatval($duffelOffer['total_amount'] ?? 0),
            ],
            
            // Cabin and Class
            'cabin' => [
                'class' => $firstSegment['passengers'][0]['cabin_class_marketing_name'] ?? 
                          ucfirst($firstSegment['passengers'][0]['cabin_class'] ?? 'Economy'),
                'marketing_class' => $firstSegment['passengers'][0]['cabin_class_marketing_name'] ?? null,
                'fare_basis' => $firstSegment['passengers'][0]['fare_basis_code'] ?? null,
            ],
            
            // Baggage Allowance
            'baggage' => $this->transformBaggage($firstSegment),
            
            // Conditions
            'conditions' => [
                'refundable' => $duffelOffer['conditions']['refund_before_departure']['allowed'] ?? false,
                'changeable' => $duffelOffer['conditions']['change_before_departure']['allowed'] ?? false,
            ],
            
            // Segments for detailed view
            'segments' => $this->transformSegments($firstSlice['segments'] ?? []),
            
            // Return slice if round trip
            'return_slice' => isset($duffelOffer['slices'][1]) 
                ? $this->transformSlice($duffelOffer['slices'][1]) 
                : null,
            
            // Expiry
            'expires_at' => $duffelOffer['expires_at'] ?? null,
            
            // Total emissions
            'total_emissions_kg' => $duffelOffer['total_emissions_kg'] ?? null,
        ];
    }

    /**
     * Transform layovers/stops
     */
    private function transformLayovers(array $segments): array
    {
        if (count($segments) <= 1) {
            return [];
        }

        $layovers = [];
        for ($i = 0; $i < count($segments) - 1; $i++) {
            $currentSegment = $segments[$i];
            $nextSegment = $segments[$i + 1];
            
            $layovers[] = [
                'airport' => $currentSegment['destination']['iata_code'] ?? '',
                'airport_name' => $currentSegment['destination']['name'] ?? '',
                'city' => $currentSegment['destination']['city_name'] ?? '',
                'duration' => $this->calculateLayoverDuration(
                    $currentSegment['arriving_at'],
                    $nextSegment['departing_at']
                ),
                'arrival_time' => $currentSegment['arriving_at'],
                'departure_time' => $nextSegment['departing_at'],
            ];
        }

        return $layovers;
    }

    /**
     * Transform segments for detailed view
     */
    private function transformSegments(array $segments): array
    {
        return array_map(function ($segment) {
            $passenger = $segment['passengers'][0] ?? [];
            
            return [
                'id' => $segment['id'] ?? '',
                'flight_number' => $segment['marketing_carrier_flight_number'] ?? 
                                  $segment['operating_carrier_flight_number'] ?? '',
                'airline_name' => $segment['marketing_carrier']['name'] ?? '',
                'airline_iata' => $segment['marketing_carrier']['iata_code'] ?? '',
                'aircraft' => $segment['aircraft'] ?? null,
                'origin' => [
                    'iata' => $segment['origin']['iata_code'] ?? '',
                    'airport' => $segment['origin']['name'] ?? '',
                    'city' => $segment['origin']['city_name'] ?? '',
                    'terminal' => $segment['origin_terminal'] ?? null,
                ],
                'destination' => [
                    'iata' => $segment['destination']['iata_code'] ?? '',
                    'airport' => $segment['destination']['name'] ?? '',
                    'city' => $segment['destination']['city_name'] ?? '',
                    'terminal' => $segment['destination_terminal'] ?? null,
                ],
                'departure_time' => $segment['departing_at'] ?? null,
                'arrival_time' => $segment['arriving_at'] ?? null,
                'duration' => $segment['duration'] ?? null,
                'distance' => $segment['distance'] ?? null,
                'cabin_class' => $passenger['cabin_class_marketing_name'] ?? $passenger['cabin_class'] ?? null,
                'fare_basis' => $passenger['fare_basis_code'] ?? null,
                'baggage' => [
                    'checked' => array_filter($passenger['baggages'] ?? [], fn($b) => $b['type'] === 'checked'),
                    'cabin' => array_filter($passenger['baggages'] ?? [], fn($b) => $b['type'] === 'carry_on'),
                ],
            ];
        }, $segments);
    }

    /**
     * Transform a slice for return journey
     */
    private function transformSlice(array $slice): array
    {
        $firstSegment = $slice['segments'][0] ?? [];
        $lastSegment = end($slice['segments']) ?: $firstSegment;
        
        return [
            'origin' => $slice['origin']['iata_code'] ?? '',
            'destination' => $slice['destination']['iata_code'] ?? '',
            'departure_time' => $firstSegment['departing_at'] ?? null,
            'arrival_time' => $lastSegment['arriving_at'] ?? null,
            'duration' => $slice['duration'] ?? null,
            'stops' => count($slice['segments']) - 1,
            'segments' => $this->transformSegments($slice['segments']),
        ];
    }

    /**
     * Transform baggage information
     */
    private function transformBaggage(array $segment): array
    {
        $passenger = $segment['passengers'][0] ?? [];
        $baggages = $passenger['baggages'] ?? [];
        
        $checked = [];
        $cabin = [];
        
        foreach ($baggages as $bag) {
            if ($bag['type'] === 'checked') {
                $checked[] = $bag;
            } elseif ($bag['type'] === 'carry_on') {
                $cabin[] = $bag;
            }
        }
        
        return [
            'checked' => $checked,
            'cabin' => $cabin,
        ];
    }

    /**
     * Calculate duration between two datetime strings
     */
    private function calculateLayoverDuration(string $arrival, string $departure): ?string
    {
        try {
            $arrivalTime = new \DateTime($arrival);
            $departureTime = new \DateTime($departure);
            $interval = $arrivalTime->diff($departureTime);
            
            $hours = $interval->h + ($interval->days * 24);
            $minutes = $interval->i;
            
            if ($hours > 0) {
                return "{$hours}h {$minutes}m";
            }
            return "{$minutes}m";
        } catch (\Exception $e) {
            return null;
        }
    }
}