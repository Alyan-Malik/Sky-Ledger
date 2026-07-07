<?php
// app/Models/Booking.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'selected_flight_id',
        'created_by',
        'booking_id',
        'pnr_number',
        'eticket_number',
        
        // Primary Passenger
        'passenger_first_name',
        'passenger_last_name',
        'gender',
        'date_of_birth',
        'nationality',
        'passport_number',
        'passport_expiry',
        'cnic',
        
        // Additional Passengers
        'additional_passengers',
        
        // Contact
        'email',
        'phone',
        'emergency_contact',
        
        // Address
        'address',
        'city',
        'country',
        'zip_code',
        
        // Baggage
        'checked_baggage_count',
        'hand_luggage_count',
        
        // Accessibility
        'wheelchair_required',
        'priority_pass',
        
        // Preferences
        'seat_number',
        'meal_preference',
        'special_assistance',
        'remarks',
        
        // Status
        'booking_status',
        'ticket_status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_expiry' => 'date',
        'additional_passengers' => 'array',
        'checked_baggage_count' => 'integer',
        'hand_luggage_count' => 'integer',
        'priority_pass' => 'boolean',
    ];

    protected $attributes = [
        'booking_status' => 'pending',
        'ticket_status' => 'not_generated',
        'checked_baggage_count' => 0,
        'hand_luggage_count' => 0,
        'wheelchair_required' => 'none',
        'priority_pass' => false,
        'additional_passengers' => '[]',
    ];

    public function selectedFlight(): BelongsTo
    {
        return $this->belongsTo(SelectedFlight::class, 'selected_flight_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->passenger_first_name} {$this->passenger_last_name}";
    }

    /**
     * Get all passengers including primary and additional
     */
    public function getAllPassengersAttribute(): array
    {
        $passengers = [
            [
                'type' => 'primary_adult',
                'first_name' => $this->passenger_first_name,
                'last_name' => $this->passenger_last_name,
                'gender' => $this->gender,
                'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
                'nationality' => $this->nationality,
                'passport_number' => $this->passport_number,
            ],
        ];

        $additional = $this->additional_passengers ?? [];
        foreach ($additional as $p) {
            $passengers[] = $p;
        }

        return $passengers;
    }

    /**
     * Generate unique booking ID
     */
    public static function generateBookingId(): string
    {
        $prefix = 'SKY';
        $timestamp = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -5));
        
        return "{$prefix}-{$timestamp}-{$random}";
    }
}