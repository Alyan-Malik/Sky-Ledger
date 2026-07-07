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
            
            // Primary Passenger
            'passenger_first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'passenger_last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'passport_expiry' => ['nullable', 'date', 'after:today'],
            'cnic' => ['nullable', 'string', 'max:50'],
            
            // Additional Passengers
            'additional_passengers' => ['nullable', 'array'],
            'additional_passengers.*.first_name' => ['nullable', 'string', 'max:100'],
            'additional_passengers.*.last_name' => ['nullable', 'string', 'max:100'],
            'additional_passengers.*.gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'additional_passengers.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'additional_passengers.*.nationality' => ['nullable', 'string', 'max:100'],
            'additional_passengers.*.passport_number' => ['nullable', 'string', 'max:50'],
            'additional_passengers.*.passport_expiry' => ['nullable', 'date', 'after:today'],
            'additional_passengers.*.passenger_type' => ['nullable', Rule::in(['adult', 'child', 'infant'])],
            
            // Contact
            'email' => ['sometimes', 'required', 'email', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            
            // Address
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            
            // Baggage - NEW
            'checked_baggage_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'hand_luggage_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            
            // Accessibility - NEW
            'wheelchair_required' => ['nullable', Rule::in(['none', 'wheelchair', 'special_assistance'])],
            'priority_pass' => ['nullable', 'boolean'],
            
            // Preferences
            'seat_number' => ['nullable', 'string', 'max:10'],
            'meal_preference' => ['nullable', 'string', 'max:100'],
            'special_assistance' => ['nullable', 'string', 'max:500'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'passenger_first_name.required' => 'Primary passenger first name is required.',
            'passenger_last_name.required' => 'Primary passenger last name is required.',
            'email.required' => 'Email address is required.',
            'phone.required' => 'Phone number is required.',
        ];
    }
}