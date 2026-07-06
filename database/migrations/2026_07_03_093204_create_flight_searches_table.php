<?php
// database/migrations/2024_01_01_000002_create_flight_searches_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flight_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('trip_type', ['one_way', 'round_trip']);
            $table->string('origin_iata', 3);
            $table->string('destination_iata', 3);
            $table->date('departure_date');
            $table->date('return_date')->nullable();
            $table->unsignedTinyInteger('adults')->default(1);
            $table->unsignedTinyInteger('children')->default(0);
            $table->unsignedTinyInteger('infants')->default(0);
            $table->enum('cabin_class', ['economy', 'premium_economy', 'business', 'first']);
            $table->json('search_metadata')->nullable(); // For future Duffel integration
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['origin_iata', 'destination_iata']);
            $table->foreign('origin_iata')->references('iata_code')->on('airports')->onDelete('cascade');
            $table->foreign('destination_iata')->references('iata_code')->on('airports')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flight_searches');
    }
};