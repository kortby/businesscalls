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
        Schema::create('outbound_campaigns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('status')->default('draft'); // draft, processing, completed
            $table->string('target_group')->nullable();
            $table->dateTime('schedule_time')->nullable();
            $table->decimal('conversion_coefficient', 5, 4)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });

        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('campaign_id')->index();
            $table->string('phone_number');
            $table->string('name')->nullable();
            $table->string('call_id')->nullable()->index();
            $table->string('status')->default('pending'); // pending, called, failed, completed
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('outbound_campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
        Schema::dropIfExists('outbound_campaigns');
    }
};
