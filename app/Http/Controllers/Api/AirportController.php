<?php
// app/Http/Controllers/Api/AirportController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AirportSearchRequest;
use App\Http\Resources\AirportResource;
use App\Services\AirportService;

class AirportController extends Controller
{
    public function __construct(
        private readonly AirportService $airportService
    ) {}

    /**
     * Search airports for autocomplete
     */
    public function search(AirportSearchRequest $request)
    {
        $airports = $this->airportService->search($request->validated('q'));
        
        return AirportResource::collection($airports);
    }
}