<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->text('triage_notes')->nullable();
            $table->string('appliance_brand')->nullable();
            $table->integer('appliance_age')->nullable();
            $table->json('urgency_markers')->nullable();
            $table->string('booking_hash')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['triage_notes', 'appliance_brand', 'appliance_age', 'urgency_markers', 'booking_hash']);
        });
    }
};
