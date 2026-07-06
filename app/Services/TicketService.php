<?php
// app/Services/TicketService.php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TicketService
{
    /**
     * Generate ticket for booking
     */
    public function generateTicket(Booking $booking): void
    {
        try {
            // Generate e-ticket number if not set
            if (!$booking->eticket_number) {
                $booking->update([
                    'eticket_number' => $this->generateEticketNumber(),
                ]);
            }

            // Generate HTML ticket content and store it
            $htmlContent = $this->generateTicketHtml($booking);
            
            // Store ticket as HTML file
            $filename = "tickets/{$booking->booking_id}.html";
            Storage::disk('public')->put($filename, $htmlContent);
            
            // Update ticket status
            $booking->update([
                'ticket_status' => 'generated',
            ]);

            Log::info('Ticket generated successfully', [
                'booking_id' => $booking->booking_id,
                'eticket_number' => $booking->eticket_number,
            ]);

        } catch (\Exception $e) {
            Log::error('Ticket generation failed', [
                'booking_id' => $booking->booking_id,
                'error' => $e->getMessage(),
            ]);
            // Don't throw - still mark booking as confirmed
            // Just log the error and continue
        }
    }

    /**
     * Generate e-ticket number
     */
    private function generateEticketNumber(): string
    {
        return '016-' . now()->format('ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Generate ticket HTML content
     */
    public function generateTicketHtml(Booking $booking): string
    {
        $flight = $booking->selectedFlight;
        $appName = config('app.name', 'SkyLedger');
        
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>E-Ticket - ' . e($booking->booking_id) . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: "Segoe UI", Arial, sans-serif; 
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        .ticket { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .ticket-header {
            background: linear-gradient(135deg, #1a56db 0%, #1e40af 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .ticket-header h1 { font-size: 28px; margin-bottom: 5px; }
        .ticket-header .subtitle { font-size: 14px; opacity: 0.9; }
        .ticket-body { padding: 30px; }
        .section {
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e7eb;
        }
        .section:last-child { border-bottom: none; }
        .section h2 {
            font-size: 18px;
            color: #1a56db;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .info-item {
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
        }
        .info-label {
            font-size: 11px;
            text-transform: uppercase;
            color: #6b7280;
            font-weight: 600;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .info-value {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }
        .flight-route {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f0f9ff;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .airport-code {
            font-size: 32px;
            font-weight: 700;
            color: #1a56db;
        }
        .airport-city {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }
        .flight-duration {
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .flight-line {
            flex: 1;
            height: 2px;
            background: #93c5fd;
            margin: 0 15px;
            position: relative;
        }
        .flight-line::before {
            content: "✈";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 20px;
        }
        .price-total {
            font-size: 28px;
            font-weight: 700;
            color: #059669;
            text-align: right;
        }
        .barcode-section {
            background: #f9fafb;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            font-family: "Courier New", monospace;
            font-size: 12px;
            letter-spacing: 2px;
            color: #374151;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            background: #10b981;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .ticket-footer {
            background: #f9fafb;
            padding: 20px 30px;
            text-align: center;
            font-size: 11px;
            color: #9ca3af;
        }
        @media print {
            body { background: white; padding: 0; }
            .ticket { box-shadow: none; max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="ticket-header">
            <h1>' . e($appName) . '</h1>
            <p class="subtitle">ELECTRONIC TICKET</p>
            <div style="margin-top: 10px;">
                <span class="status-badge">CONFIRMED</span>
            </div>
        </div>
        
        <div class="ticket-body">
            <!-- Booking Reference -->
            <div class="section">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Booking ID</div>
                        <div class="info-value">' . e($booking->booking_id) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">E-Ticket Number</div>
                        <div class="info-value">' . e($booking->eticket_number ?? 'N/A') . '</div>
                    </div>
                    ' . ($booking->pnr_number ? '
                    <div class="info-item">
                        <div class="info-label">PNR Number</div>
                        <div class="info-value">' . e($booking->pnr_number) . '</div>
                    </div>' : '') . '
                    <div class="info-item">
                        <div class="info-label">Booking Date</div>
                        <div class="info-value">' . $booking->created_at->format('d M Y') . '</div>
                    </div>
                </div>
            </div>

            <!-- Flight Route -->
            <div class="section">
                <h2>✈ Flight Details</h2>
                <div class="flight-route">
                    <div style="text-align: center;">
                        <div class="airport-code">' . e($flight->origin_iata) . '</div>
                        <div class="airport-city">' . e($flight->origin_city) . '</div>
                        <div style="font-size: 14px; font-weight: 600; margin-top: 5px;">' . $flight->departure_datetime->format('H:i') . '</div>
                        <div style="font-size: 11px; color: #6b7280;">' . $flight->departure_datetime->format('d M Y') . '</div>
                    </div>
                    <div style="flex: 1; padding: 0 20px; text-align: center;">
                        <div class="flight-duration">' . e($flight->duration) . '</div>
                        <div class="flight-line"></div>
                        <div style="font-size: 11px; color: #6b7280; margin-top: 5px;">' . ($flight->stops == 0 ? 'Direct Flight' : $flight->stops . ' Stop(s)') . '</div>
                    </div>
                    <div style="text-align: center;">
                        <div class="airport-code">' . e($flight->destination_iata) . '</div>
                        <div class="airport-city">' . e($flight->destination_city) . '</div>
                        <div style="font-size: 14px; font-weight: 600; margin-top: 5px;">' . $flight->arrival_datetime->format('H:i') . '</div>
                        <div style="font-size: 11px; color: #6b7280;">' . $flight->arrival_datetime->format('d M Y') . '</div>
                    </div>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Airline</div>
                        <div class="info-value">' . e($flight->airline_name) . ' (' . e($flight->airline_code) . ')</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Flight Number</div>
                        <div class="info-value">' . e($flight->flight_number) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Cabin Class</div>
                        <div class="info-value">' . e(ucfirst($flight->cabin_class)) . '</div>
                    </div>
                    ' . ($flight->aircraft ? '
                    <div class="info-item">
                        <div class="info-label">Aircraft</div>
                        <div class="info-value">' . e($flight->aircraft) . '</div>
                    </div>' : '') . '
                </div>
            </div>

            <!-- Passenger Info -->
            <div class="section">
                <h2>👤 Passenger Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Passenger Name</div>
                        <div class="info-value">' . e($booking->full_name) . '</div>
                    </div>
                    ' . ($booking->gender ? '
                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value">' . e(ucfirst($booking->gender)) . '</div>
                    </div>' : '') . '
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value">' . e($booking->email) . '</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone</div>
                        <div class="info-value">' . e($booking->phone) . '</div>
                    </div>
                    ' . ($booking->seat_number ? '
                    <div class="info-item">
                        <div class="info-label">Seat Number</div>
                        <div class="info-value">' . e($booking->seat_number) . '</div>
                    </div>' : '') . '
                    ' . ($booking->meal_preference ? '
                    <div class="info-item">
                        <div class="info-label">Meal Preference</div>
                        <div class="info-value">' . e(ucfirst(str_replace("_", " ", $booking->meal_preference))) . '</div>
                    </div>' : '') . '
                </div>
            </div>

            <!-- Price -->
            <div class="section">
                <h2>💰 Price Breakdown</h2>
                <div style="max-width: 400px; margin-left: auto;">
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; color: #6b7280;">
                        <span>Base Fare</span>
                        <span>' . number_format($flight->base_price, 2) . ' ' . e($flight->currency) . '</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 8px 0; color: #6b7280;">
                        <span>Service Charge</span>
                        <span>' . number_format($flight->service_charge, 2) . ' ' . e($flight->currency) . '</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; padding: 12px 0; border-top: 2px solid #e5e7eb; margin-top: 8px;">
                        <span style="font-weight: 700; font-size: 18px;">Total</span>
                        <span class="price-total">' . number_format($flight->total_price, 2) . ' ' . e($flight->currency) . '</span>
                    </div>
                </div>
            </div>

            <!-- Barcode -->
            <div class="barcode-section">
                <div style="font-size: 10px; margin-bottom: 8px;">E-TICKET BARCODE</div>
                <div style="font-size: 16px; letter-spacing: 3px;">▮▮▮▮ ▮▮ ▮▮▮ ▮▮▮▮ ▮ ▮▮▮▮ ▮▮ ▮▮▮▮▮</div>
                <div style="margin-top: 8px; font-size: 11px;">' . e($booking->eticket_number ?? $booking->booking_id) . '</div>
            </div>
        </div>

        <div class="ticket-footer">
            <p>This is an electronic ticket. Please present a valid photo ID at check-in.</p>
            <p style="margin-top: 5px;">Generated on ' . now()->format('d M Y H:i:s') . ' | ' . e($appName) . ' © ' . date('Y') . '</p>
        </div>
    </div>
</body>
</html>';
        
        return $html;
    }

    /**
     * Get ticket HTML content
     */
    public function getTicketHtml(Booking $booking): string
    {
        $filename = "tickets/{$booking->booking_id}.html";
        
        if (!Storage::disk('public')->exists($filename)) {
            $this->generateTicket($booking);
        }

        return Storage::disk('public')->get($filename);
    }

    /**
     * Get ticket URL
     */
    public function getTicketUrl(Booking $booking): string
    {
        $filename = "tickets/{$booking->booking_id}.html";
        
        if (!Storage::disk('public')->exists($filename)) {
            $this->generateTicket($booking);
        }

        return Storage::disk('public')->url($filename);
    }
}