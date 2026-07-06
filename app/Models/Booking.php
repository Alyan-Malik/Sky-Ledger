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
        'passenger_first_name',
        'passenger_last_name',
        'gender',
        'date_of_birth',
        'nationality',
        'passport_number',
        'passport_expiry',
        'cnic',
        'email',
        'phone',
        'emergency_contact',
        'address',
        'city',
        'country',
        'zip_code',
        'seat_number',
        'meal_preference',
        'special_assistance',
        'remarks',
        'booking_status',
        'ticket_status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'passport_expiry' => 'date',
    ];

    protected $attributes = [
        'booking_status' => 'pending',
        'ticket_status' => 'not_generated',
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