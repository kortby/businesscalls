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
            $table->string('call_end_reason')->nullable()->after('csat_score');
            $table->string('disconnection_source')->nullable()->after('call_end_reason');
            $table->integer('latency')->nullable()->after('disconnection_source');
            $table->double('transcription_confidence')->nullable()->after('latency');
            $table->double('tool_success_rate')->nullable()->after('transcription_confidence');
            $table->double('call_quality_score')->nullable()->after('tool_success_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn([
                'call_end_reason',
                'disconnection_source',
                'latency',
                'transcription_confidence',
                'tool_success_rate',
                'call_quality_score',
            ]);
        });
    }
};
