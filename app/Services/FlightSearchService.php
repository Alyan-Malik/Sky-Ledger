<?php
// app/Services/FlightSearchService.php

namespace App\Services;

use App\Models\FlightSearch;
use App\Http\Resources\FlightSearchResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class FlightSearchService
{
    /**
     * Save flight search
     */
    public function saveSearch(array $validatedData): FlightSearch
    {
        return DB::transaction(function () use ($validatedData) {
            return FlightSearch::create($validatedData);
        });
    }

    /**
     * Get user's recent searches
     */
    public function getRecentSearches(int $userId, int $limit = 10): LengthAwarePaginator
    {
        return FlightSearch::with(['originAirport', 'destinationAirport'])
            ->where('user_id', $userId)
            ->latest()
            ->paginate($limit);
    }
}