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
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'certifications')) {
                $table->json('certifications')->nullable();
            }
            if (! Schema::hasColumn('employees', 'latitude')) {
                $table->double('latitude')->nullable();
            }
            if (! Schema::hasColumn('employees', 'longitude')) {
                $table->double('longitude')->nullable();
            }
        });

        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'priority_state')) {
                $table->string('priority_state')->default('routine_maintenance');
            }
            if (! Schema::hasColumn('bookings', 'required_certification')) {
                $table->string('required_certification')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'latitude')) {
                $table->double('latitude')->nullable();
            }
            if (! Schema::hasColumn('bookings', 'longitude')) {
                $table->double('longitude')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['certifications', 'latitude', 'longitude']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['priority_state', 'required_certification', 'latitude', 'longitude']);
        });
    }
};
