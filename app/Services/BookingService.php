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
            $bookingId = $validatedData['booking_id'] ?? Booking::generateBookingId();

            // Create booking
            $booking = Booking::create([
                'selected_flight_id' => $selectedFlight->id,
                'created_by' => $userId,
                'booking_id' => $bookingId,
                'pnr_number' => $validatedData['pnr_number'] ?? null,
                'eticket_number' => $validatedData['eticket_number'] ?? null,
                
                // Passenger
                'passenger_first_name' => $validatedData['passenger_first_name'],
                'passenger_last_name' => $validatedData['passenger_last_name'],
                'gender' => $validatedData['gender'] ?? null,
                'date_of_birth' => $validatedData['date_of_birth'] ?? null,
                'nationality' => $validatedData['nationality'] ?? null,
                'passport_number' => $validatedData['passport_number'] ?? null,
                'passport_expiry' => $validatedData['passport_expiry'] ?? null,
                'cnic' => $validatedData['cnic'] ?? null,
                
                // Contact
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'],
                'emergency_contact' => $validatedData['emergency_contact'] ?? null,
                
                // Address
                'address' => $validatedData['address'] ?? null,
                'city' => $validatedData['city'] ?? null,
                'country' => $validatedData['country'] ?? null,
                'zip_code' => $validatedData['zip_code'] ?? null,
                
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
                // Continue - booking is still valid
            }

            Log::info('Booking created successfully', [
                'booking_id' => $booking->booking_id,
                'user_id' => $userId,
                'selected_flight_id' => $selectedFlight->id,
            ]);

            return $booking->load('selectedFlight');
        });
    }

    /**
     * Update booking
     */
    public function updateBooking(Booking $booking, array $validatedData): Booking
    {
        $booking->update($validatedData);
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