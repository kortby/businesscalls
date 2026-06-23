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
            $table->timestamp('en_route_at')->nullable();
            $table->timestamp('on_site_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->double('travel_time')->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['en_route_at', 'on_site_at', 'completed_at', 'travel_time']);
        });
    }
};
