<?php
// database/migrations/2024_01_01_000001_create_airports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('airports', function (Blueprint $table) {
            $table->id();
            $table->string('iata_code', 3)->unique();
            $table->string('icao_code', 4)->nullable()->index();
            $table->string('airport_name');
            $table->string('city');
            $table->string('country');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('timezone', 50)->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
            
            // Composite index for search performance
            $table->index(['airport_name', 'city', 'iata_code']);
            $table->fullText(['airport_name', 'city', 'country', 'iata_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('airports');
    }
};