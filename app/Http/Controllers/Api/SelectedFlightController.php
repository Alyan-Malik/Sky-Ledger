<?php
// app/Http/Controllers/Api/SelectedFlightController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SelectFlightRequest;
use App\Http\Resources\SelectedFlightResource;
use App\Services\SelectedFlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SelectedFlightController extends Controller
{
    public function __construct(
        private readonly SelectedFlightService $selectedFlightService
    ) {}

    /**
     * Select and save a flight
     */
    public function store(SelectFlightRequest $request): JsonResponse
    {
        try {
            $selectedFlight = $this->selectedFlightService->saveSelectedFlight(
                $request->validated(),
                $request->flight_search_id,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Flight selected successfully',
                'data' => new SelectedFlightResource($selectedFlight),
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to select flight', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to select flight. Please try again.',
            ], 500);
        }
    }

    /**
     * Get selected flight details
     */
    public function show(int $id): JsonResponse
    {
        try {
            $selectedFlight = $this->selectedFlightService->getActiveSelectedFlight(
                $id,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'data' => new SelectedFlightResource($selectedFlight),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Selected flight not found.',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}