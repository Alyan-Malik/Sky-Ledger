<?php
// app/Http/Controllers/Api/FlightSearchController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FlightSearchRequest;
use App\Http\Resources\FlightSearchResource;
use App\Services\FlightSearchService;
use Illuminate\Http\JsonResponse;

class FlightSearchController extends Controller
{
    public function __construct(
        private readonly FlightSearchService $flightSearchService
    ) {}

    /**
     * Store a flight search
     */
    public function store(FlightSearchRequest $request): JsonResponse
    {
        $flightSearch = $this->flightSearchService->saveSearch(
            $request->validatedForStorage()
        );

        // Load relationships for response
        $flightSearch->load(['originAirport', 'destinationAirport']);

        return response()->json([
            'message' => 'Flight search saved successfully.',
            'data' => new FlightSearchResource($flightSearch),
        ], 201);
    }

    /**
     * Get recent searches for authenticated user
     */
    public function index()
    {
        $searches = $this->flightSearchService->getRecentSearches(auth()->id());

        return FlightSearchResource::collection($searches);
    }
}