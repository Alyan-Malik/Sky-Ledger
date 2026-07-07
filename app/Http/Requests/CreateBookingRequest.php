<?php
// app/Http/Requests/CreateBookingRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'booking_id' => ['nullable', 'string', 'max:50', 'unique:bookings,booking_id'],
            'pnr_number' => ['nullable', 'string', 'max:20', 'unique:bookings,pnr_number'],
            'eticket_number' => ['nullable', 'string', 'max:50', 'unique:bookings,eticket_number'],
            
            // Primary Passenger (required)
            'passenger_first_name' => ['required', 'string', 'max:100'],
            'passenger_last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'passport_expiry' => ['nullable', 'date', 'after:today'],
            'cnic' => ['nullable', 'string', 'max:50'],
            
            // Contact (required for primary passenger)
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'emergency_contact' => ['nullable', 'string', 'max:20'],
            
            // Address
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            
            // Additional Passengers
            'additional_passengers' => ['nullable', 'array'],
            'additional_passengers.*.first_name' => ['required', 'string', 'max:100'],
            'additional_passengers.*.last_name' => ['required', 'string', 'max:100'],
            'additional_passengers.*.gender' => ['required', Rule::in(['male', 'female', 'other'])],
            'additional_passengers.*.date_of_birth' => ['nullable', 'date', 'before:today'],
            'additional_passengers.*.nationality' => ['nullable', 'string', 'max:100'],
            'additional_passengers.*.passport_number' => ['nullable', 'string', 'max:50'],
            'additional_passengers.*.passport_expiry' => ['nullable', 'date', 'after:today'],
            'additional_passengers.*.passenger_type' => ['required', Rule::in(['adult', 'child', 'infant'])],
            
            // Baggage
            'checked_baggage_count' => ['required', 'integer', 'min:0', 'max:20'],
            'hand_luggage_count' => ['required', 'integer', 'min:0', 'max:20'],
            
            // Assistance
            'wheelchair_required' => ['required', Rule::in(['none', 'wheelchair', 'special_assistance'])],
            'priority_pass' => ['required', 'boolean'],
            
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
            'gender.required' => 'Please select gender for the primary passenger.',
            'email.required' => 'Email address is required for the primary passenger.',
            'phone.required' => 'Phone number is required.',
            'additional_passengers.*.first_name.required' => 'Each additional passenger must have a first name.',
            'additional_passengers.*.last_name.required' => 'Each additional passenger must have a last name.',
            'additional_passengers.*.gender.required' => 'Please select gender for each additional passenger.',
        ];
    }
}