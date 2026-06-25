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
        // 1. Update tenants table with server-to-server client oauth credentials
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('client_id')->nullable()->unique();
            $table->string('client_secret')->nullable();
        });

        // 2. Update call_logs table with turn-taking congruence metrics
        Schema::table('call_logs', function (Blueprint $table) {
            $table->double('turn_taking_congruence')->nullable();
        });

        // 3. Create tenant_integrations table for dynamic Make/GoHighLevel workflows
        Schema::create('tenant_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('platform_name');
            $table->text('webhook_url')->nullable();
            $table->boolean('is_active')->default(false);
            $table->json('settings_json')->nullable();
            $table->timestamps();
        });

        // 4. Create tenant_oauth_tokens table for server-to-server custom token handshakes
        Schema::create('tenant_oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->string('access_token')->unique();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_oauth_tokens');
        Schema::dropIfExists('tenant_integrations');

        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropColumn('turn_taking_congruence');
        });

        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['client_id', 'client_secret']);
        });
    }
};
