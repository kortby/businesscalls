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
        // 1. Create experiments table
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('name');
            $table->string('status')->default('active'); // active, archived
            $table->integer('traffic_split')->default(50); // % routed to Variant B
            $table->timestamps();
        });

        // 2. Create experiment_variants table
        Schema::create('experiment_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained('experiments')->onDelete('cascade');
            $table->string('version'); // 'A' or 'B'
            $table->text('prompt_instructions');
            $table->string('model_provider');
            $table->integer('call_count')->default(0);
            $table->integer('booking_count')->default(0);
            $table->timestamps();
        });

        // 3. Add experiment tracking and audio denoising quality columns to call_logs
        Schema::table('call_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('experiment_variant_id')->nullable();
            $table->double('snr_raw')->nullable();
            $table->double('snr_processed')->nullable();
            $table->double('denoising_quality_improvement')->nullable();

            $table->foreign('experiment_variant_id')->references('id')->on('experiment_variants')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropForeign(['experiment_variant_id']);
            $table->dropColumn(['experiment_variant_id', 'snr_raw', 'snr_processed', 'denoising_quality_improvement']);
        });

        Schema::dropIfExists('experiment_variants');
        Schema::dropIfExists('experiments');
    }
};
