<?php
// database/migrations/2024_07_07_000001_add_passenger_details_to_bookings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Make current passenger fields nullable (for primary passenger)
            $table->string('passenger_first_name')->nullable()->change();
            $table->string('passenger_last_name')->nullable()->change();
            
            // Additional passengers stored as JSON
            $table->json('additional_passengers')->nullable()->after('passenger_last_name');
            
            // Baggage details
            $table->integer('checked_baggage_count')->default(0)->after('special_assistance');
            $table->integer('hand_luggage_count')->default(0)->after('checked_baggage_count');
            
            // Wheelchair and special assistance
            $table->enum('wheelchair_required', ['none', 'wheelchair', 'special_assistance'])->default('none')->after('hand_luggage_count');
            $table->boolean('priority_pass')->default(false)->after('wheelchair_required');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('passenger_first_name')->nullable(false)->change();
            $table->string('passenger_last_name')->nullable(false)->change();
            $table->dropColumn('additional_passengers');
            $table->dropColumn('checked_baggage_count');
            $table->dropColumn('hand_luggage_count');
            $table->dropColumn('wheelchair_required');
            $table->dropColumn('priority_pass');
        });
    }
};