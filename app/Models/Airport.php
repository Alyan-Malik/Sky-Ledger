<?php
// app/Models/Airport.php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    use HasFactory;

    protected $fillable = [
        'iata_code',
        'icao_code',
        'airport_name',
        'city',
        'country',
        'latitude',
        'longitude',
        'timezone',
        'active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'active' => 'boolean',
    ];

    /**
     * Scope for searching airports by term
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $searchTerm = '%' . $term . '%';
        
        return $query->where('active', true)
            ->where(function ($q) use ($searchTerm) {
                $q->where('airport_name', 'like', $searchTerm)
                    ->orWhere('city', 'like', $searchTerm)
                    ->orWhere('country', 'like', $searchTerm)
                    ->orWhere('iata_code', 'like', $searchTerm);
            })
            // FIX: Use orderByRaw for the CASE sorting logic, NOT orWhereRaw
            ->orderByRaw("CASE 
                WHEN iata_code = ? THEN 1
                WHEN iata_code LIKE ? THEN 2
                WHEN city LIKE ? THEN 3
                WHEN airport_name LIKE ? THEN 4
                ELSE 5
            END ASC", [$term, "{$term}%", "{$term}%", "{$term}%"])
            ->orderBy('airport_name', 'asc')
            ->limit(10);
    }

    /**
     * Get formatted full name
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->airport_name} ({$this->iata_code})";
    }

    /**
     * Get location string
     */
    public function getLocationAttribute(): string
    {
        return "{$this->city}, {$this->country}";
    }
}