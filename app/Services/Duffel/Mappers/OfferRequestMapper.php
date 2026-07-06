<?php
// app/Services/Duffel/Mappers/OfferRequestMapper.php

namespace App\Services\Duffel\Mappers;

use App\Models\FlightSearch;
use App\Exceptions\DuffelValidationException;

class OfferRequestMapper
{
    /**
     * Map FlightSearch model to Duffel offer request payload
     */
    public function map(FlightSearch $search): array
    {
        $this->validateSearch($search);

        $payload = [
            'slices' => $this->buildSlices($search),
            'passengers' => $this->buildPassengers($search),
            'cabin_class' => $this->mapCabinClass($search->cabin_class),
            'max_connections' => 2,
        ];

        // Add return trip validation
        if ($search->trip_type === 'round_trip' && !$search->return_date) {
            throw new DuffelValidationException(
                'Return date is required for round trip searches.'
            );
        }

        return $payload;
    }

    /**
     * Build slices array for Duffel
     */
    private function buildSlices(FlightSearch $search): array
    {
        $slices = [
            [
                'origin' => $search->origin_iata,
                'destination' => $search->destination_iata,
                'departure_date' => $search->departure_date->format('Y-m-d'),
            ],
        ];

        // Add return slice for round trips
        if ($search->trip_type === 'round_trip' && $search->return_date) {
            $slices[] = [
                'origin' => $search->destination_iata,
                'destination' => $search->origin_iata,
                'departure_date' => $search->return_date->format('Y-m-d'),
            ];
        }

        return $slices;
    }

    /**
     * Build passengers array
     */
    private function buildPassengers(FlightSearch $search): array
    {
        $passengers = [];

        // Add adults
        for ($i = 0; $i < $search->adults; $i++) {
            $passengers[] = ['type' => 'adult'];
        }

        // Add children
        for ($i = 0; $i < $search->children; $i++) {
            $passengers[] = ['type' => 'child'];
        }

        // Add infants
        for ($i = 0; $i < $search->infants; $i++) {
            $passengers[] = ['type' => 'infant_without_seat'];
        }

        return $passengers;
    }

    /**
     * Map cabin class to Duffel format
     */
    private function mapCabinClass(string $cabinClass): string
    {
        return match ($cabinClass) {
            'economy' => 'economy',
            'premium_economy' => 'premium_economy',
            'business' => 'business',
            'first' => 'first',
            default => 'economy',
        };
    }

    /**
     * Validate search before mapping
     */
    private function validateSearch(FlightSearch $search): void
    {
        $errors = [];

        if (!$search->origin_iata || strlen($search->origin_iata) !== 3) {
            $errors[] = 'Invalid origin airport code.';
        }

        if (!$search->destination_iata || strlen($search->destination_iata) !== 3) {
            $errors[] = 'Invalid destination airport code.';
        }

        if ($search->origin_iata === $search->destination_iata) {
            $errors[] = 'Origin and destination cannot be the same.';
        }

        if (!$search->departure_date || $search->departure_date->isPast()) {
            $errors[] = 'Departure date must be in the future.';
        }

        if ($search->adults < 1) {
            $errors[] = 'At least one adult passenger is required.';
        }

        if ($search->infants > $search->adults) {
            $errors[] = 'Number of infants cannot exceed number of adults.';
        }

        if (!empty($errors)) {
            throw new DuffelValidationException(
                'Invalid search parameters.',
                $errors
            );
        }
    }
}