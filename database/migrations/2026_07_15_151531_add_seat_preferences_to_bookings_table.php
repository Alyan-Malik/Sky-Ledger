<?php
// database/migrations/2024_07_15_000001_add_seat_preferences_to_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Add seat preference columns
            $table->enum('seat_preference', ['window', 'aisle', 'middle'])->nullable()->after('seat_number');
            $table->boolean('extra_legroom')->default(false)->after('seat_preference');
            
            // Add baggage weight columns
            $table->integer('checked_baggage_kg')->default(23)->after('hand_luggage_count');
            $table->integer('hand_luggage_kg')->default(7)->after('checked_baggage_kg');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('seat_preference');
            $table->dropColumn('extra_legroom');
            $table->dropColumn('checked_baggage_kg');
            $table->dropColumn('hand_luggage_kg');
        });
    }
};