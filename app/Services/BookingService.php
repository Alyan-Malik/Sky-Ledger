<?php
// app/Services/BookingService.php

namespace App\Services;

use App\Models\Booking;
use App\Models\SelectedFlight;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BookingService
{
    /**
     * Create a new booking
     */
    public function createBooking(array $validatedData, SelectedFlight $selectedFlight, int $userId): Booking
    {
        return DB::transaction(function () use ($validatedData, $selectedFlight, $userId) {
            // Generate booking ID if not provided
            $bookingId = $validatedData['booking_id'] ?? null;
            if (empty($bookingId)) {
                $bookingId = Booking::generateBookingId();
            }

            // Create booking
            $booking = Booking::create([
                'selected_flight_id' => $selectedFlight->id,
                'created_by' => $userId,
                'booking_id' => $bookingId,
                'pnr_number' => $validatedData['pnr_number'] ?? null,
                'eticket_number' => $validatedData['eticket_number'] ?? null,
                
                // Primary Passenger
                'passenger_first_name' => $validatedData['passenger_first_name'],
                'passenger_last_name' => $validatedData['passenger_last_name'],
                'gender' => $validatedData['gender'] ?? null,
                'date_of_birth' => $validatedData['date_of_birth'] ?? null,
                'nationality' => $validatedData['nationality'] ?? null,
                'passport_number' => $validatedData['passport_number'] ?? null,
                'passport_expiry' => $validatedData['passport_expiry'] ?? null,
                'cnic' => $validatedData['cnic'] ?? null,
                
                // Additional Passengers (JSON)
                'additional_passengers' => $validatedData['additional_passengers'] ?? [],
                
                // Contact
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'emergency_contact' => $validatedData['emergency_contact'] ?? null,
                
                // Address
                'address' => $validatedData['address'] ?? null,
                'city' => $validatedData['city'] ?? null,
                'country' => $validatedData['country'] ?? null,
                'zip_code' => $validatedData['zip_code'] ?? null,
                
                // Baggage
                'checked_baggage_count' => $validatedData['checked_baggage_count'] ?? 0,
                'hand_luggage_count' => $validatedData['hand_luggage_count'] ?? 0,
                
                // Accessibility
                'wheelchair_required' => $validatedData['wheelchair_required'] ?? 'none',
                'priority_pass' => $validatedData['priority_pass'] ?? false,
                
                // Preferences
                'seat_number' => $validatedData['seat_number'] ?? null,
                'meal_preference' => $validatedData['meal_preference'] ?? null,
                'special_assistance' => $validatedData['special_assistance'] ?? null,
                'remarks' => $validatedData['remarks'] ?? null,
                
                // Status
                'booking_status' => 'confirmed',
                'ticket_status' => 'generated',
            ]);

            // Update selected flight status
            $selectedFlight->update(['status' => 'booked']);

            // Generate ticket (don't fail if ticket generation fails)
            try {
                app(TicketService::class)->generateTicket($booking);
            } catch (\Exception $e) {
                Log::warning('Ticket generation failed, but booking was created', [
                    'booking_id' => $booking->booking_id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Booking created successfully', [
                'booking_id' => $booking->booking_id,
                'user_id' => $userId,
                'selected_flight_id' => $selectedFlight->id,
                'passengers_count' => 1 + count($validatedData['additional_passengers'] ?? []),
                'baggage' => [
                    'checked' => $validatedData['checked_baggage_count'] ?? 0,
                    'hand' => $validatedData['hand_luggage_count'] ?? 0,
                ],
                'wheelchair' => $validatedData['wheelchair_required'] ?? 'none',
                'priority_pass' => $validatedData['priority_pass'] ?? false,
            ]);

            return $booking->load('selectedFlight');
        });
    }

    /**
     * Update booking
     */
     public function updateBooking(Booking $booking, array $validatedData): Booking
    {
        $updateData = [];
        
        // Only update fields that are present in the request
        $fields = [
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
            'additional_passengers',
            'email',
            'phone',
            'emergency_contact',
            'address',
            'city',
            'country',
            'zip_code',
            'checked_baggage_count',
            'hand_luggage_count',
            'wheelchair_required',
            'priority_pass',
            'seat_number',
            'meal_preference',
            'special_assistance',
            'remarks',
        ];

        foreach ($fields as $field) {
            if (array_key_exists($field, $validatedData)) {
                $updateData[$field] = $validatedData[$field];
            }
        }

        // Ensure boolean fields are properly cast
        if (array_key_exists('priority_pass', $updateData)) {
            $updateData['priority_pass'] = (bool) $updateData['priority_pass'];
        }

        // Ensure integer fields are properly cast
        if (array_key_exists('checked_baggage_count', $updateData)) {
            $updateData['checked_baggage_count'] = (int) $updateData['checked_baggage_count'];
        }
        if (array_key_exists('hand_luggage_count', $updateData)) {
            $updateData['hand_luggage_count'] = (int) $updateData['hand_luggage_count'];
        }

        // Log what we're updating for debugging
        Log::info('Updating booking fields', [
            'booking_id' => $booking->booking_id,
            'fields_being_updated' => array_keys($updateData),
            'data' => $updateData,
        ]);

        $booking->update($updateData);
        
        return $booking->fresh(['selectedFlight']);
    }

    /**
     * Cancel booking
     */
    public function cancelBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking->update([
                'booking_status' => 'cancelled',
                'ticket_status' => 'not_generated',
            ]);

            $booking->selectedFlight->update(['status' => 'cancelled']);

            Log::info('Booking cancelled', [
                'booking_id' => $booking->booking_id,
            ]);

            return $booking;
        });
    }
}