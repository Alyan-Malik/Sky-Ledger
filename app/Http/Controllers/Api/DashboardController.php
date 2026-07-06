<?php
// app/Http/Controllers/Api/DashboardController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\FlightSearch;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics and data
     */
    public function index(): JsonResponse
    {
        $userId = auth()->id();
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $today = $now->copy()->startOfDay();

        // Statistics
        $stats = [
            [
                'label' => 'Total Bookings',
                'value' => $this->formatNumber(
                    Booking::where('created_by', $userId)->count()
                ),
                'change' => $this->calculateChange(
                    Booking::where('created_by', $userId)
                        ->whereMonth('bookings.created_at', $now->month)
                        ->count(),
                    Booking::where('created_by', $userId)
                        ->whereMonth('bookings.created_at', $now->copy()->subMonth()->month)
                        ->count()
                ),
                'icon' => 'TicketCheck',
            ],
            [
                'label' => "Today's Bookings",
                'value' => $this->formatNumber(
                    Booking::where('created_by', $userId)
                        ->whereDate('bookings.created_at', $today)
                        ->count()
                ),
                'change' => $this->getTodayChange($userId),
                'icon' => 'CalendarClock',
            ],
            [
                'label' => 'Tickets Generated',
                'value' => $this->formatNumber(
                    Booking::where('created_by', $userId)
                        ->where('ticket_status', 'generated')
                        ->count()
                ),
                'change' => $this->calculateChange(
                    Booking::where('created_by', $userId)
                        ->where('ticket_status', 'generated')
                        ->whereMonth('bookings.created_at', $now->month)
                        ->count(),
                    Booking::where('created_by', $userId)
                        ->where('ticket_status', 'generated')
                        ->whereMonth('bookings.created_at', $now->copy()->subMonth()->month)
                        ->count()
                ),
                'icon' => 'Printer',
            ],
            [
                'label' => 'Revenue (Month)',
                'value' => $this->formatCurrency(
                    $this->getMonthlyRevenue($userId, $now)
                ),
                'change' => $this->calculateRevenueChange($userId, $now),
                'icon' => 'TrendingUp',
            ],
        ];

        // Recent bookings
        $recentBookings = Booking::where('bookings.created_by', $userId)
            ->with(['selectedFlight:id,airline_name,airline_code,airline_logo,flight_number,total_price,currency'])
            ->select('bookings.*')
            ->latest('bookings.created_at')
            ->take(7)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'booking_id' => $booking->booking_id,
                    'passenger' => $booking->full_name,
                    'airline_code' => $booking->selectedFlight->airline_code ?? 'N/A',
                    'airline_name' => $booking->selectedFlight->airline_name ?? 'N/A',
                    'airline_logo' => $booking->selectedFlight->airline_logo ?? null,
                    'flight_number' => $booking->selectedFlight->flight_number ?? 'N/A',
                    'status' => $booking->booking_status,
                    'amount' => $booking->selectedFlight->total_price ?? 0,
                    'currency' => $booking->selectedFlight->currency ?? 'USD',
                    'created_at' => $booking->created_at->format('Y-m-d H:i:s'),
                ];
            });

        // Recent searches
        $recentSearches = FlightSearch::where('user_id', $userId)
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($search) {
                $passengers = $search->adults + $search->children + $search->infants;
                return [
                    'id' => $search->id,
                    'route' => "{$search->origin_iata} → {$search->destination_iata}",
                    'origin' => $search->origin_iata,
                    'destination' => $search->destination_iata,
                    'pax' => "{$passengers} passenger" . ($passengers > 1 ? 's' : ''),
                    'trip_type' => $search->trip_type,
                    'departure_date' => $search->departure_date->format('Y-m-d'),
                    'when' => $search->created_at->diffForHumans(),
                    'created_at' => $search->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_bookings' => $recentBookings,
                'recent_searches' => $recentSearches,
            ],
        ]);
    }

    /**
     * Get monthly revenue using query builder to avoid ambiguous columns
     */
    private function getMonthlyRevenue($userId, $now): float
    {
        return (float) DB::table('bookings')
            ->join('selected_flights', 'bookings.selected_flight_id', '=', 'selected_flights.id')
            ->where('bookings.created_by', $userId)
            ->where('bookings.booking_status', 'confirmed')
            ->whereMonth('bookings.created_at', $now->month)
            ->whereYear('bookings.created_at', $now->year)
            ->sum('selected_flights.total_price');
    }

    /**
     * Format number for display
     */
    private function formatNumber($number): string
    {
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        }
        if ($number >= 1000) {
            return number_format($number);
        }
        return (string) $number;
    }

    /**
     * Format currency for display
     */
    private function formatCurrency($amount): string
    {
        if ($amount >= 1000000) {
            return round($amount / 1000000, 1) . 'M';
        }
        if ($amount >= 1000) {
            return round($amount / 1000, 1) . 'K';
        }
        return number_format($amount, 0);
    }

    /**
     * Calculate percentage change
     */
    private function calculateChange($current, $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? '+100%' : '0%';
        }
        $change = (($current - $previous) / $previous) * 100;
        $sign = $change >= 0 ? '+' : '';
        return $sign . round($change, 1) . '%';
    }

    /**
     * Get today's change indicator
     */
    private function getTodayChange($userId): string
    {
        $today = Carbon::today();
        $yesterday = Carbon::yesterday();
        
        $todayCount = Booking::where('created_by', $userId)
            ->whereDate('bookings.created_at', $today)
            ->count();
        $yesterdayCount = Booking::where('created_by', $userId)
            ->whereDate('bookings.created_at', $yesterday)
            ->count();
        
        $diff = $todayCount - $yesterdayCount;
        if ($diff > 0) return "+{$diff} today";
        if ($diff < 0) return "{$diff} today";
        return 'Same as yesterday';
    }

    /**
     * Calculate revenue change
     */
    private function calculateRevenueChange($userId, $now): string
    {
        $currentMonth = $this->getMonthlyRevenue($userId, $now);
        $lastMonth = $this->getMonthlyRevenue($userId, $now->copy()->subMonth());
        
        return $this->calculateChange($currentMonth, $lastMonth);
    }
}