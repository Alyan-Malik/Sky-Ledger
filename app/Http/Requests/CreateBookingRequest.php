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
            
            // Passenger Information
            'passenger_first_name' => ['required', 'string', 'max:100'],
            'passenger_last_name' => ['required', 'string', 'max:100'],
            'gender' => ['nullable', Rule::in(['male', 'female', 'other'])],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'passport_number' => ['nullable', 'string', 'max:50'],
            'passport_expiry' => ['nullable', 'date', 'after:today'],
            'cnic' => ['nullable', 'string', 'max:50'],
            
            // Contact
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
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

    public function messages(): array
    {
        return [
            'passenger_first_name.required' => 'Passenger first name is required.',
            'passenger_last_name.required' => 'Passenger last name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'phone.required' => 'Phone number is required.',
            'booking_id.unique' => 'This Booking ID is already in use.',
            'pnr_number.unique' => 'This PNR number is already in use.',
            'eticket_number.unique' => 'This E-ticket number is already in use.',
        ];
    }
}