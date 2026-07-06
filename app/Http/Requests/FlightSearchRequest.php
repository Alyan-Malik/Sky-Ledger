<?php
// app/Http/Requests/FlightSearchRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class FlightSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'trip_type' => ['required', Rule::in(['one_way', 'round_trip'])],
            'origin_iata' => [
                'required',
                'string',
                'size:3',
                'exists:airports,iata_code,active,1'
            ],
            'destination_iata' => [
                'required',
                'string',
                'size:3',
                'exists:airports,iata_code,active,1',
                'different:origin_iata'
            ],
            'departure_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'return_date' => [
                Rule::requiredIf(function () {
                    return $this->trip_type === 'round_trip';
                }),
                'nullable',
                'date',
                'after:departure_date',
            ],
            'adults' => ['required', 'integer', 'min:1', 'max:9'],
            'children' => ['required', 'integer', 'min:0', 'max:9'],
            'infants' => ['required', 'integer', 'min:0', 'max:9', function ($attribute, $value, $fail) {
                if ($value > $this->adults) {
                    $fail('The number of infants cannot exceed the number of adults.');
                }
            }],
            'cabin_class' => [
                'required',
                Rule::in(['economy', 'premium_economy', 'business', 'first'])
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'destination_iata.different' => 'Origin and destination airports must be different.',
            'departure_date.after_or_equal' => 'Departure date cannot be in the past.',
            'return_date.after' => 'Return date must be after departure date.',
            'return_date.required' => 'Return date is required for round trip searches.',
            'adults.min' => 'At least one adult passenger is required.',
        ];
    }

    /**
     * Prepare validated data for storage
     */
    public function validatedForStorage(): array
    {
        $validated = $this->validated();
        $validated['user_id'] = auth()->id();
        $validated['search_metadata'] = [
            'user_agent' => $this->userAgent(),
            'ip_address' => $this->ip(),
            'search_source' => 'web',
        ];
        
        return $validated;
    }
}