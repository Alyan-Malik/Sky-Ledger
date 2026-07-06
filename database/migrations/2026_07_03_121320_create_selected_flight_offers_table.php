<?php
// database/migrations/2024_01_15_000001_create_selected_flight_offers_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('selected_flight_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_search_id')->constrained()->onDelete('cascade');
            $table->string('duffel_offer_id');
            $table->string('airline_name');
            $table->string('airline_iata', 3);
            $table->string('flight_number');
            $table->string('origin_iata', 3);
            $table->string('destination_iata', 3);
            $table->dateTime('departure_time');
            $table->dateTime('arrival_time');
            $table->string('duration');
            $table->unsignedInteger('stops')->default(0);
            $table->string('cabin_class');
            $table->decimal('base_price', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('grand_total', 12, 2);
            $table->json('offer_data'); // Full normalized offer data
            $table->json('passenger_details')->nullable();
            $table->json('booking_info')->nullable();
            $table->enum('status', ['selected', 'booked', 'cancelled', 'expired'])->default('selected');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['flight_search_id', 'status']);
            $table->index('duffel_offer_id');
            $table->index('created_at');
            
            // Foreign key to airports
            $table->foreign('origin_iata')->references('iata_code')->on('airports');
            $table->foreign('destination_iata')->references('iata_code')->on('airports');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('selected_flight_offers');
    }
};