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
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('is_test_mode')->default(true);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->boolean('is_test_mode')->default(true);
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->boolean('is_test_mode')->default(true);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('is_test_mode')->default(true);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_supervisor')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('is_test_mode');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('is_test_mode');
        });

        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn('is_test_mode');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('is_test_mode');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_supervisor');
        });
    }
};
