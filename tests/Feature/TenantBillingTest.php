<?php

use App\Models\Invoice;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    TenantScope::setTenantId(null);
    session()->forget('tenant_id');
});

test('generate invoices command aggregates call durations and calculates costs correctly', function () {
    $tenant = Tenant::factory()->create([
        'plan' => 'pro',
        'settings' => [
            'baseline_rate' => 0.15,
            'markup_rate' => 0.05,
            'base_plan_rate' => 79.00,
        ],
    ]);

    // Create Call Logs for Tenant under specific date
    // Log 1: 120 seconds (2.0 minutes)
    DB::table('call_logs')->insert([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-1',
        'customer_phone' => '+15550001111',
        'status' => 'ended',
        'duration' => 120,
        'created_at' => Carbon::parse('2026-06-15 10:00:00'),
        'updated_at' => Carbon::parse('2026-06-15 10:00:00'),
    ]);

    // Log 2: 300 seconds (5.0 minutes)
    DB::table('call_logs')->insert([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-2',
        'customer_phone' => '+15550001111',
        'status' => 'ended',
        'duration' => 300,
        'created_at' => Carbon::parse('2026-06-16 11:00:00'),
        'updated_at' => Carbon::parse('2026-06-16 11:00:00'),
    ]);

    // Log 3: Out of billing period (ignored)
    DB::table('call_logs')->insert([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-3',
        'customer_phone' => '+15550001111',
        'status' => 'ended',
        'duration' => 600,
        'created_at' => Carbon::parse('2026-07-01 00:00:00'),
        'updated_at' => Carbon::parse('2026-07-01 00:00:00'),
    ]);

    // Act
    $this->artisan('billing:generate-invoices --month=2026-06')
        ->assertExitCode(0);

    // Assert database insertion
    TenantScope::setTenantId($tenant->id);
    $invoice = Invoice::where('billing_period', '2026-06')->first();

    expect($invoice)->not->toBeNull();
    expect($invoice->total_calls_count)->toBe(2);
    // 120 + 300 = 420 seconds = 7.0 minutes
    expect($invoice->total_duration_minutes)->toBe(7.0);
    // base_amount = $79.00
    expect((float) $invoice->base_amount)->toBe(79.00);
    // usage_amount = 7.0 * (0.15 + 0.05) = 7.0 * 0.20 = $1.40
    expect((float) $invoice->usage_amount)->toBe(1.40);
    // total_amount = 79.00 + 1.40 = $80.40
    expect((float) $invoice->total_amount)->toBe(80.40);

    // Verify HTML file outputs
    expect(File::exists($invoice->pdf_path))->toBeTrue();
    $htmlContent = File::get($invoice->pdf_path);
    expect($htmlContent)->toContain('Invoice')
        ->and($htmlContent)->toContain('79.00')
        ->and($htmlContent)->toContain('1.40')
        ->and($htmlContent)->toContain('80.40')
        ->and($htmlContent)->toContain('BusinessCalls');
});
