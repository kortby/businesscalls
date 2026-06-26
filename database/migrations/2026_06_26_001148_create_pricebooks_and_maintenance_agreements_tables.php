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
        Schema::create('pricebooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('item_code')->index();
            $table->text('description')->nullable();
            $table->decimal('flat_rate_price', 10, 2);
            $table->string('category')->index(); // HVAC, plumbing, electrical
            $table->boolean('diagnostic_required')->default(false);
            $table->timestamps();
        });

        Schema::create('maintenance_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('system_type')->index(); // HVAC, plumbing, electrical
            $table->date('last_service_date');
            $table->string('status')->default('active'); // active, inactive
            $table->date('next_service_due')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_agreements');
        Schema::dropIfExists('pricebooks');
    }
};
