<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selected_flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('flight_search_id')->constrained()->onDelete('cascade');
            
            // 1. Keep this fluent index helper right here
            $table->string('duffel_offer_id')->index();
            $table->string('provider')->default('duffel');
            
            // Airline Information
            $table->string('airline_name');
            $table->string('airline_code', 3);
            $table->string('airline_logo')->nullable();
            $table->string('flight_number');
            
            // Route Information
            $table->string('origin_airport');
            $table->string('origin_city');
            $table->string('origin_iata', 3);
            $table->string('destination_airport');
            $table->string('destination_city');
            $table->string('destination_iata', 3);
            
            // Schedule
            $table->dateTime('departure_datetime');
            $table->dateTime('arrival_datetime');
            $table->string('duration');
            $table->unsignedInteger('stops')->default(0);
            
            // Flight Details
            $table->string('cabin_class');
            $table->string('fare_brand')->nullable();
            $table->string('aircraft')->nullable();
            $table->string('terminal')->nullable();
            $table->json('baggage')->nullable();
            
            // Pricing
            $table->string('currency', 3)->default('USD');
            $table->decimal('base_price', 12, 2);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('total_price', 12, 2);
            
            // Complete offer snapshot
            $table->json('offer_json');
            
            // Status and Expiry
            $table->enum('status', ['active', 'expired', 'booked', 'cancelled'])->default('active');
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            
            // 2. REMOVED: $table->index('duffel_offer_id');  <-- This line is deleted!
            
            $table->foreign('origin_iata')->references('iata_code')->on('airports');
            $table->foreign('destination_iata')->references('iata_code')->on('airports');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selected_flights');
    }
};
