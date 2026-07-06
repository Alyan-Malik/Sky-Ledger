<?php
// app/Http/Requests/SelectFlightRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SelectFlightRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'flight_search_id' => ['required', 'integer', 'exists:flight_searches,id'],
            'offer_id' => ['required', 'string', 'max:255'],
            'airline_name' => ['required', 'string', 'max:255'],
            'airline_iata' => ['required', 'string', 'min:2', 'max:3'], // Changed from size:3 to min:2
            'airline_logo' => ['nullable', 'string', 'max:500'],
            'flight_number' => ['required', 'string', 'max:50'],
            'origin_iata' => ['required', 'string', 'size:3'],
            'origin_airport' => ['required', 'string', 'max:255'],
            'origin_city' => ['required', 'string', 'max:255'],
            'destination_iata' => ['required', 'string', 'size:3'],
            'destination_airport' => ['required', 'string', 'max:255'],
            'destination_city' => ['required', 'string', 'max:255'],
            'departure_time' => ['required', 'date'],
            'arrival_time' => ['required', 'date', 'after:departure_time'],
            'duration' => ['required', 'string', 'max:20'],
            'stops' => ['required', 'integer', 'min:0', 'max:5'],
            'cabin_class' => ['required', 'string', 'max:50'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'service_charge' => ['required', 'numeric', 'min:0'],
            'grand_total' => ['required', 'numeric', 'min:0'],
            'offer_data' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'airline_iata.required' => 'Airline code is required.',
            'airline_iata.min' => 'Airline code must be at least 2 characters.',
            'airline_iata.max' => 'Airline code must not exceed 3 characters.',
            'origin_iata.size' => 'Origin airport code must be exactly 3 characters.',
            'destination_iata.size' => 'Destination airport code must be exactly 3 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure airline_iata is uppercase and trimmed
        if ($this->has('airline_iata')) {
            $this->merge([
                'airline_iata' => strtoupper(trim($this->airline_iata)),
            ]);
        }
    }
}