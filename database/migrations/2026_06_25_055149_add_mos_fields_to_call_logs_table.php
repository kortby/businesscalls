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
            $table->double('mos_score')->nullable();
            $table->double('acoustic_intelligibility')->nullable();
            $table->double('vocal_inflection_variance')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn(['mos_score', 'acoustic_intelligibility', 'vocal_inflection_variance']);
        });
    }
};
