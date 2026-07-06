<?php
// database/migrations/2024_01_20_000002_create_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('selected_flight_id')->constrained('selected_flights')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            
            // Booking References
            $table->string('booking_id')->unique();
            $table->string('pnr_number')->nullable()->unique();
            $table->string('eticket_number')->nullable()->unique();
            
            // Passenger Information
            $table->string('passenger_first_name');
            $table->string('passenger_last_name');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry')->nullable();
            $table->string('cnic')->nullable();
            
            // Contact Information
            $table->string('email');
            $table->string('phone');
            $table->string('emergency_contact')->nullable();
            
            // Address
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
            
            // Flight Preferences
            $table->string('seat_number')->nullable();
            $table->string('meal_preference')->nullable();
            $table->text('special_assistance')->nullable();
            
            // Additional
            $table->text('remarks')->nullable();
            
            // Status
            $table->enum('booking_status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->enum('ticket_status', ['not_generated', 'generated', 'sent'])->default('not_generated');
            
            $table->timestamps();
            
            // Indexes
            $table->index('booking_status');
            $table->index('ticket_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};