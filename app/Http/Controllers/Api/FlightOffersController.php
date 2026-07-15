<?php
// app/Http/Controllers/Api/FlightOffersController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
use App\Http\Resources\FlightSearchResource;
use App\Models\FlightSearch;
use App\Services\Duffel\DuffelService;
use App\Exceptions\DuffelException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FlightOffersController extends Controller
{
    public function __construct(
        private readonly DuffelService $duffelService
    ) {}

    /**
     * Search flights and return offers
     */
    public function search(FlightSearchRequest $request): JsonResponse
{
    try {
        // Create flight search record
        $flightSearch = FlightSearch::create(
            $request->validatedForStorage()
        );

        // Load relationships
        $flightSearch->load(['originAirport', 'destinationAirport']);

        // Search flights via Duffel
        $offers = $this->duffelService->searchOffers($flightSearch);

        // Check if offers were found
        if (empty($offers)) {
            return response()->json([
                'success' => true,
                'message' => 'No flights found for the selected criteria.',
                'data' => [
                    'search' => new FlightSearchResource($flightSearch),
                    'offers' => [],
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => count($offers) . ' flights found',
            'data' => [
                'search' => new FlightSearchResource($flightSearch),
                'offers' => $offers,
            ],
        ]);

    } catch (\App\Exceptions\DuffelRateLimitException $e) {
        \Log::warning('Duffel rate limit hit', [
            'message' => $e->getMessage(),
            'retry_after' => $e->getRetryAfter(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => 'Too many requests. Please wait a moment and try again.',
            'retry_after' => $e->getRetryAfter(),
        ], 429);
        
    } catch (\App\Exceptions\DuffelApiException $e) {
        \Log::error('Duffel API error in search', [
            'message' => $e->getMessage(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 502);
        
    } catch (\App\Exceptions\DuffelException $e) {
        \Log::error('Duffel exception in search', [
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'duffel_status' => $e->getDuffelStatusCode(),
        ]);
        
        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], $e->getDuffelStatusCode() ?? 500);
        
    } catch (\Exception $e) {
        \Log::error('Flight search failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to search flights. Please try again later.',
        ], 500);
    }
}

    /**
     * Get offer details
     */
        public function show(string $offerId): JsonResponse
    {
        try {
            $offer = $this->duffelService->getOffer($offerId);

            return response()->json([
                'success' => true,
                'message' => 'Offer details retrieved',
                'data' => $offer,
            ]);
        } catch (DuffelException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getDuffelStatusCode() ?? 500);
        }
    }

    /**
     * Select and save an offer
     */
    public function selectOffer(SelectOfferRequest $request): JsonResponse
    {
        try {
            $selectedOffer = SelectedFlightOffer::create([
                'flight_search_id' => $request->flight_search_id,
                'duffel_offer_id' => $request->offer_id,
                'airline_name' => $request->airline_name,
                'airline_iata' => $request->airline_iata,
                'flight_number' => $request->flight_number,
                'origin_iata' => $request->origin_iata,
                'destination_iata' => $request->destination_iata,
                'departure_time' => $request->departure_time,
                'arrival_time' => $request->arrival_time,
                'duration' => $request->duration,
                'stops' => $request->stops,
                'cabin_class' => $request->cabin_class,
                'base_price' => $request->base_price,
                'currency' => $request->currency,
                'service_charge' => $request->service_charge,
                'grand_total' => $request->grand_total,
                'offer_data' => $request->offer_data,
                'expires_at' => now()->addHours(24),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Flight selected successfully',
                'data' => new SelectedOfferResource($selectedOffer),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to select offer', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to select flight. Please try again.',
            ], 500);
        }
    }

    /**
     * Refresh offer pricing
     */
       public function refreshOffer(string $offerId): JsonResponse
    {
        try {
            $offer = $this->duffelService->getOffer($offerId);

            return response()->json([
                'success' => true,
                'message' => 'Offer refreshed',
                'data' => $offer,
            ]);
        } catch (DuffelException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getDuffelStatusCode() ?? 500);
        }
    }
}