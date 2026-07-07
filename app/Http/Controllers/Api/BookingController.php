<?php
// app/Http/Controllers/Api/BookingController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\BookingListResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\SelectedFlightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly SelectedFlightService $selectedFlightService
    ) {}

    /**
     * Get all bookings for authenticated user with search and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::where('created_by', auth()->id())
            ->with(['selectedFlight:id,airline_name,airline_code,airline_logo,flight_number,origin_iata,destination_iata,departure_datetime,arrival_datetime,duration,stops,cabin_class,total_price,currency'])
            ->latest();

        // Search functionality
        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_id', 'like', "%{$search}%")
                    ->orWhere('pnr_number', 'like', "%{$search}%")
                    ->orWhere('passenger_first_name', 'like', "%{$search}%")
                    ->orWhere('passenger_last_name', 'like', "%{$search}%")
                    ->orWhere('eticket_number', 'like', "%{$search}%")
                    ->orWhereHas('selectedFlight', function ($sq) use ($search) {
                        $sq->where('flight_number', 'like', "%{$search}%")
                            ->orWhere('airline_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('booking_status', $status);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 50);
        $bookings = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => BookingListResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
                'from' => $bookings->firstItem(),
                'to' => $bookings->lastItem(),
            ],
        ]);
    }

    /**
     * Create a new booking
     */
    public function store(CreateBookingRequest $request, int $selectedFlightId): JsonResponse
    {
        try {
            // Get and validate selected flight
            $selectedFlight = $this->selectedFlightService->getActiveSelectedFlight(
                $selectedFlightId,
                auth()->id()
            );

            // Create booking
            $booking = $this->bookingService->createBooking(
                $request->validated(),
                $selectedFlight,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Booking confirmed successfully',
                'data' => new BookingResource($booking),
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Selected flight not found.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Booking creation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'selected_flight_id' => $selectedFlightId,
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Failed to create booking.',
            ], 400);
        }
    }

    /**
     * Get booking details
     */
    public function show(int $id): JsonResponse
    {
        $booking = Booking::where('created_by', auth()->id())
            ->with('selectedFlight')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new BookingResource($booking),
        ]);
    }

    /**
     * Update booking (only passenger details, not flight)
     */
    public function update(UpdateBookingRequest $request, int $id): JsonResponse
{
    try {
        $booking = Booking::where('created_by', auth()->id())
            ->findOrFail($id);

        // Only allow updating if booking is confirmed
        if ($booking->booking_status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update a cancelled booking.',
            ], 400);
        }

        // Log the incoming request data
        Log::info('Booking update request', [
            'booking_id' => $id,
            'validated_data' => $request->validated(),
        ]);

        $booking = $this->bookingService->updateBooking($booking, $request->validated());

        Log::info('Booking updated successfully', [
            'booking_id' => $booking->booking_id,
            'updated_fields' => array_keys($request->validated()),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully',
            'data' => new BookingResource($booking),
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Booking not found.',
        ], 404);
    } catch (\Exception $e) {
        Log::error('Booking update failed', [
            'error' => $e->getMessage(),
            'booking_id' => $id,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update booking.',
        ], 400);
    }
}

    /**
     * Cancel booking
     */
    public function cancel(int $id): JsonResponse
    {
        try {
            $booking = Booking::where('created_by', auth()->id())
                ->findOrFail($id);

            $booking = $this->bookingService->cancelBooking($booking);

            return response()->json([
                'success' => true,
                'message' => 'Booking cancelled successfully',
                'data' => new BookingResource($booking),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel booking.',
            ], 400);
        }
    }

    /**
     * Delete booking
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $booking = Booking::where('created_by', auth()->id())
                ->findOrFail($id);

            // Only allow deleting if cancelled or pending
            if (!in_array($booking->booking_status, ['cancelled', 'pending'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only cancelled or pending bookings can be deleted.',
                ], 400);
            }

            $booking->delete();

            Log::info('Booking deleted', [
                'booking_id' => $booking->booking_id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Booking deleted successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Booking deletion failed', [
                'error' => $e->getMessage(),
                'booking_id' => $id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete booking.',
            ], 400);
        }
    }


    /**
 * Update booking status
 */
public function updateStatus(Request $request, int $id): JsonResponse
{
    try {
        $booking = Booking::where('created_by', auth()->id())
            ->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:confirmed,pending,cancelled'],
        ]);

        $newStatus = $validated['status'];

        // Update booking status
        $booking->update(['booking_status' => $newStatus]);

        // If cancelled, update ticket status
        if ($newStatus === 'cancelled') {
            $booking->update(['ticket_status' => 'not_generated']);
            $booking->selectedFlight->update(['status' => 'cancelled']);
        }

        // If confirmed, update selected flight status
        if ($newStatus === 'confirmed') {
            $booking->selectedFlight->update(['status' => 'booked']);
        }

        Log::info('Booking status updated', [
            'booking_id' => $booking->booking_id,
            'new_status' => $newStatus,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Booking status updated to {$newStatus}",
            'data' => new BookingResource($booking->load('selectedFlight')),
        ]);

    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Booking not found.',
        ], 404);
    } catch (\Exception $e) {
        Log::error('Failed to update booking status', [
            'error' => $e->getMessage(),
            'booking_id' => $id,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update booking status.',
        ], 400);
    }
}
}