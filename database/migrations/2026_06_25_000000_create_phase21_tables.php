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
        // 1. Create semantic_caches table
        Schema::create('semantic_caches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->text('query_text');
            $table->json('vector_embedding');
            $table->json('response_json');
            $table->timestamps();
        });

        // 2. Add cost & tracking columns to call_logs
        Schema::table('call_logs', function (Blueprint $table) {
            $table->double('cost')->nullable();
            $table->integer('integrations_count')->default(0);
            $table->integer('rag_lookups_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn(['cost', 'integrations_count', 'rag_lookups_count']);
        });

        Schema::dropIfExists('semantic_caches');
    }
};
