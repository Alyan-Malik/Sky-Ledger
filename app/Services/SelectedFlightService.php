<?php
// app/Services/SelectedFlightService.php

namespace App\Services;

use App\Models\SelectedFlight;
use App\Models\FlightSearch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SelectedFlightService
{
    public function __construct(
        private readonly PriceCalculationService $priceCalculator
    ) {}

    /**
     * Save selected flight from Duffel offer
     */
    public function saveSelectedFlight(array $data, int $flightSearchId, int $userId): SelectedFlight
    {
        return DB::transaction(function () use ($data, $flightSearchId, $userId) {
            // Verify flight search belongs to user
            $flightSearch = FlightSearch::where('id', $flightSearchId)
                ->where('user_id', $userId)
                ->first();

            if (!$flightSearch) {
                throw new \Exception('Flight search not found.');
            }

            // Log the incoming pricing data for debugging
            Log::info('SelectedFlightService - Pricing data received', [
                'base_price' => $data['base_price'] ?? 'NOT SET',
                'service_charge' => $data['service_charge'] ?? 'NOT SET',
                'grand_total' => $data['grand_total'] ?? 'NOT SET',
                'currency' => $data['currency'] ?? 'NOT SET',
            ]);

            // USE THE EXACT VALUES from the frontend - do NOT recalculate
            // The frontend already calculated these correctly including taxes
            $basePrice = round(floatval($data['base_price'] ?? 0), 2);
            $serviceCharge = round(floatval($data['service_charge'] ?? 0), 2);
            $totalPrice = round(floatval($data['grand_total'] ?? 0), 2);
            $currency = $data['currency'] ?? 'USD';

            // Log what we're saving
            Log::info('SelectedFlightService - Saving pricing', [
                'base_price_to_save' => $basePrice,
                'service_charge_to_save' => $serviceCharge,
                'total_price_to_save' => $totalPrice,
            ]);

            // Create selected flight with EXACT values from frontend
            $selectedFlight = SelectedFlight::create([
                'user_id' => $userId,
                'flight_search_id' => $flightSearchId,
                'duffel_offer_id' => $data['offer_id'] ?? 'unknown',
                'provider' => 'duffel',
                
                // Airline
                'airline_name' => $data['airline_name'] ?? 'Unknown Airline',
                'airline_code' => $data['airline_iata'] ?? 'ZZ',
                'airline_logo' => $data['airline_logo'] ?? null,
                'flight_number' => $data['flight_number'] ?? '0000',
                
                // Route
                'origin_airport' => $data['origin_airport'] ?? 'Unknown Airport',
                'origin_city' => $data['origin_city'] ?? 'Unknown City',
                'origin_iata' => $data['origin_iata'] ?? 'XXX',
                'destination_airport' => $data['destination_airport'] ?? 'Unknown Airport',
                'destination_city' => $data['destination_city'] ?? 'Unknown City',
                'destination_iata' => $data['destination_iata'] ?? 'XXX',
                
                // Schedule
                'departure_datetime' => $data['departure_time'] ?? now(),
                'arrival_datetime' => $data['arrival_time'] ?? now(),
                'duration' => $data['duration'] ?? 'PT0M',
                'stops' => $data['stops'] ?? 0,
                
                // Details
                'cabin_class' => $data['cabin_class'] ?? 'Economy',
                'fare_brand' => $data['fare_brand'] ?? null,
                'aircraft' => $data['aircraft'] ?? null,
                'terminal' => $data['terminal'] ?? null,
                'baggage' => $data['baggage'] ?? null,
                
                // Pricing - USE EXACT VALUES FROM FRONTEND
                'currency' => $currency,
                'base_price' => $basePrice,
                'service_charge' => $serviceCharge,
                'total_price' => $totalPrice,
                
                // Complete snapshot
                'offer_json' => $data['offer_data'] ?? $data,
                
                // Expiry (24 hours from now)
                'expires_at' => now()->addHours(24),
            ]);

            Log::info('Selected flight saved successfully', [
                'selected_flight_id' => $selectedFlight->id,
                'base_price' => $selectedFlight->base_price,
                'service_charge' => $selectedFlight->service_charge,
                'total_price' => $selectedFlight->total_price,
            ]);

            return $selectedFlight;
        });
    }

    /**
     * Get active selected flight
     */
    public function getActiveSelectedFlight(int $id, int $userId): SelectedFlight
    {
        $selectedFlight = SelectedFlight::where('id', $id)
            ->where('user_id', $userId)
            ->with(['originAirport', 'destinationAirport'])
            ->first();

        if (!$selectedFlight) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Selected flight not found.');
        }

        if ($selectedFlight->isExpired()) {
            throw new \Exception('This flight offer has expired. Please search again.');
        }

        if ($selectedFlight->booking) {
            throw new \Exception('A booking already exists for this flight.');
        }

        return $selectedFlight;
    }
}