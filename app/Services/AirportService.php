<?php

namespace App\Services;

use App\Models\Airport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class AirportService
{
    /**
     * Search airports with safe array caching
     */
    public function search(string $searchTerm): Collection
    {
        $searchTerm = trim(strtolower($searchTerm));
        // Added a structural version prefix to instantly drop dirty old database records
        $cacheKey = 'airport_search_v2_' . md5($searchTerm); 
        
        $airportArrays = Cache::remember($cacheKey, 300, function () use ($searchTerm) {
            // Store as a pure array instead of an Eloquent object
            return Airport::search($searchTerm)->get()->toArray();
        });

        // Hydrate arrays back into a clean, expected Eloquent Collection
        return Airport::hydrate($airportArrays);
    }
}