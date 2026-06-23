<?php

namespace App\Console\Commands;

use App\Models\CallLog;
use App\Models\Invoice;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

class GenerateMonthlyTenantInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'billing:generate-invoices {--month= : The billing month in YYYY-MM format, defaults to current month}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Aggregate call durations, calculate usage fees, and generate monthly tenant invoices';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $month = $this->option('month') ?: now()->format('Y-m');
        $this->info("Starting invoice generation for period: {$month}");

        $startDate = Carbon::parse($month)->startOfMonth();
        $endDate = Carbon::parse($month)->endOfMonth();

        // Fetch all active tenants
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("Processing Billing for Tenant: {$tenant->name} (ID: {$tenant->id})");

            // Enforce isolated tenant context for database querying
            TenantScope::setTenantId($tenant->id);

            // Fetch call logs within period
            $calls = CallLog::whereBetween('created_at', [$startDate, $endDate])->get();

            $totalCallsCount = $calls->count();
            $totalDurationSeconds = $calls->sum('duration');
            $totalDurationMinutes = $totalDurationSeconds / 60.0;

            // Get billing rates from settings
            $baselineRate = (float) $tenant->getSetting('baseline_rate', 0.15); // baseline cost per minute
            $markupRate = (float) $tenant->getSetting('markup_rate', 0.05);     // custom SaaS platform markup per minute

            $basePlanRate = match ($tenant->plan) {
                'basic' => 29.00,
                'pro' => 79.00,
                'enterprise' => 199.00,
                default => 29.00,
            };
            $baseAmount = (float) $tenant->getSetting('base_plan_rate', $basePlanRate);

            // Calculate usage amount: Sum( D_c * (R_baseline + M) )
            // Since (R_baseline + M) is constant per tenant, we can multiply the sum of durations directly:
            $ratePerMinute = $baselineRate + $markupRate;
            $usageAmount = $totalDurationMinutes * $ratePerMinute;

            // Total: Usage + Base Plan
            $totalAmount = $usageAmount + $baseAmount;

            // Update or Create Invoice
            $invoice = Invoice::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'billing_period' => $month,
                ],
                [
                    'total_calls_count' => $totalCallsCount,
                    'total_duration_minutes' => round($totalDurationMinutes, 2),
                    'base_amount' => round($baseAmount, 2),
                    'usage_amount' => round($usageAmount, 2),
                    'total_amount' => round($totalAmount, 2),
                    'status' => 'pending',
                ]
            );

            // Create storage directory if missing
            $invoiceDir = storage_path('app/invoices');
            if (! File::exists($invoiceDir)) {
                File::makeDirectory($invoiceDir, 0755, true);
            }

            // Render Blade View HTML
            $htmlContent = View::make('billing.invoice', [
                'invoice' => $invoice,
                'tenant' => $tenant,
                'rate_per_minute' => $ratePerMinute,
            ])->render();

            $htmlPath = "{$invoiceDir}/invoice_{$invoice->id}.html";
            File::put($htmlPath, $htmlContent);
            $invoice->pdf_path = $htmlPath;

            // Optional PDF Compilation using dompdf if class exists
            if (class_exists(Pdf::class)) {
                try {
                    $pdf = Pdf::loadHTML($htmlContent);
                    $pdfPath = "{$invoiceDir}/invoice_{$invoice->id}.pdf";
                    $pdf->save($pdfPath);
                    $invoice->pdf_path = $pdfPath;
                    $this->info("Compiled actual PDF invoice for Tenant: {$tenant->name}");
                } catch (\Exception $e) {
                    Log::error("PDF Generation failed for Invoice ID {$invoice->id}: ".$e->getMessage());
                }
            }

            $invoice->save();

            $this->info("Finalized Invoice ID {$invoice->id} total: $".number_format($totalAmount, 2));
        }

        // Reset scope at the end
        TenantScope::setTenantId(null);
        $this->info('Invoice generation complete.');
    }
}
