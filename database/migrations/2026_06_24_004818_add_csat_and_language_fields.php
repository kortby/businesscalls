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
        Schema::table('call_logs', function (Blueprint $table) {
            $table->float('csat_score')->nullable()->after('duration');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('language')->default('en')->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn('csat_score');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
