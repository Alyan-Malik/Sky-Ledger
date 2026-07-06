<?php
// app/Http/Requests/UpdateBookingRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $bookingId = $this->route('id');

        return [
            'booking_id' => ['nullable', 'string', 'max:50', Rule::unique('bookings', 'booking_id')->ignore($bookingId)],
            'pnr_number' => ['nullable', 'string', 'max:20', Rule::unique('bookings', 'pnr_number')->ignore($bookingId)],
            'eticket_number' => ['nullable', 'string', 'max:50', Rule::unique('bookings', 'eticket_number')->ignore($bookingId)],
            
            // Passenger Information
            'passenger_first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'passenger_last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'passport_expiry' => ['nullable', 'date', 'after:today'],
            'cnic' => ['nullable', 'string', 'max:50'],
            
            // Contact
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            
            // Address
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            
            // Preferences
            'seat_number' => ['nullable', 'string', 'max:10'],
            'meal_preference' => ['nullable', 'string', 'max:100'],
            'special_assistance' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }
}