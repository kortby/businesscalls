<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\TenantShard;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('compliance:audit')]
#[Description('Scan database transcripts and call logs for PII exposure, redact them, and compute Compliance Integrity Index.')]
class AuditTenantLogCompliance extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting automated compliance scan...');

        $defaultConnection = DB::getDefaultConnection();
        $tenants = Tenant::all(); // Fetches all tenants from the master database

        foreach ($tenants as $tenant) {
            $this->info("Scanning logs for Tenant: {$tenant->name} (ID: {$tenant->id})...");

            // Switch database connection dynamically to tenant shard
            $shard = TenantShard::where('tenant_id', $tenant->id)->first();
            if ($shard) {
                $shardConfig = $shard->database_config;
                if (empty($shardConfig)) {
                    $shardConfig = [
                        'driver' => $shard->driver ?? 'sqlite',
                        'database' => $shard->database,
                        'prefix' => '',
                    ];
                    if ($shardConfig['driver'] === 'sqlite') {
                        $shardConfig['foreign_key_constraints'] = true;
                    } else {
                        $shardConfig['host'] = $shard->host;
                        $shardConfig['port'] = $shard->port;
                        $shardConfig['username'] = $shard->username;
                        $shardConfig['password'] = $shard->password;
                    }
                }
                DB::purge('tenant');
                config()->set('database.connections.tenant', $shardConfig);
                DB::setDefaultConnection('tenant');
            } else {
                DB::setDefaultConnection($defaultConnection);
            }

            // Set current tenant ID inside static memory for global scope scoping
            TenantScope::setTenantId($tenant->id);

            // Execute compliance scan
            $this->scanTenantLogs($tenant);
        }

        // Restore connections
        DB::setDefaultConnection($defaultConnection);
        TenantScope::setTenantId(null);

        $this->info('Compliance audit complete.');

        return 0;
    }

    /**
     * Scan database call logs for a specific tenant.
     */
    protected function scanTenantLogs(Tenant $tenant): void
    {
        // SSN format: 123-45-6789 or raw 9-digits
        $ssnPattern1 = '/\b\d{3}-\d{2}-\d{4}\b/';
        $ssnPattern2 = '/\b\d{9}\b/';

        // Credit Card: standard 16 digit patterns
        $ccPattern = '/\b\d{4}[- ]?\d{4}[- ]?\d{4}[- ]?\d{4}\b/';

        // Date of Birth: MM/DD/YYYY or YYYY-MM-DD formats
        $dobPattern = '/\b(?:\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}|\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2})\b/';

        $callLogs = CallLog::all(); // Query runs under active default connection
        $totalAuditsCount = $callLogs->count();
        $exposureInstances = [];

        foreach ($callLogs as $call) {
            $hasViolation = false;
            $ssnDetected = false;
            $ccDetected = false;
            $dobDetected = false;

            $redactedTranscript = $call->transcript;
            $redactedSummary = $call->summary;

            // Scan Transcript
            if ($call->transcript) {
                if (preg_match($ssnPattern1, $call->transcript) || preg_match($ssnPattern2, $call->transcript)) {
                    $ssnDetected = true;
                    $hasViolation = true;
                    $exposureInstances[] = ['type' => 'ssn', 'severity' => 3.0];
                }
                if (preg_match($ccPattern, $call->transcript)) {
                    $ccDetected = true;
                    $hasViolation = true;
                    $exposureInstances[] = ['type' => 'credit_card', 'severity' => 3.0];
                }
                if (preg_match($dobPattern, $call->transcript)) {
                    $dobDetected = true;
                    $hasViolation = true;
                    $exposureInstances[] = ['type' => 'dob', 'severity' => 1.0];
                }
            }

            // Scan Summary
            if ($call->summary) {
                if (preg_match($ssnPattern1, $call->summary) || preg_match($ssnPattern2, $call->summary)) {
                    if (! $ssnDetected) {
                        $ssnDetected = true;
                        $hasViolation = true;
                        $exposureInstances[] = ['type' => 'ssn', 'severity' => 3.0];
                    }
                }
                if (preg_match($ccPattern, $call->summary)) {
                    if (! $ccDetected) {
                        $ccDetected = true;
                        $hasViolation = true;
                        $exposureInstances[] = ['type' => 'credit_card', 'severity' => 3.0];
                    }
                }
                if (preg_match($dobPattern, $call->summary)) {
                    if (! $dobDetected) {
                        $dobDetected = true;
                        $hasViolation = true;
                        $exposureInstances[] = ['type' => 'dob', 'severity' => 1.0];
                    }
                }
            }

            if ($hasViolation) {
                // Redact values
                if ($call->transcript) {
                    $redactedTranscript = preg_replace($ssnPattern1, '[REDACTED]', $redactedTranscript);
                    $redactedTranscript = preg_replace($ssnPattern2, '[REDACTED]', $redactedTranscript);
                    $redactedTranscript = preg_replace($ccPattern, '[REDACTED]', $redactedTranscript);
                    $redactedTranscript = preg_replace($dobPattern, '[REDACTED]', $redactedTranscript);
                }
                if ($call->summary) {
                    $redactedSummary = preg_replace($ssnPattern1, '[REDACTED]', $redactedSummary);
                    $redactedSummary = preg_replace($ssnPattern2, '[REDACTED]', $redactedSummary);
                    $redactedSummary = preg_replace($ccPattern, '[REDACTED]', $redactedSummary);
                    $redactedSummary = preg_replace($dobPattern, '[REDACTED]', $redactedSummary);
                }

                $call->transcript = $redactedTranscript;
                $call->summary = $redactedSummary;
                $call->save();

                // Log entry in isolated audit_logs table
                AuditLog::create([
                    'tenant_id' => $tenant->id,
                    'action' => 'pii_exposure_redacted',
                    'payload' => [
                        'call_log_id' => $call->id,
                        'call_id' => $call->call_id,
                        'ssn_detected' => $ssnDetected,
                        'credit_card_detected' => $ccDetected,
                        'dob_detected' => $dobDetected,
                    ],
                ]);
            }
        }

        // Calculate Compliance Integrity Index (Theta_compliance)
        $gdprEnabled = $tenant->getSetting('gdpr_hipaa_enforcement', false) || $tenant->getSetting('gdpr_hipaa_enabled', false);
        $psiConsent = 1;

        if ($gdprEnabled) {
            // Mismatch if GDPR/HIPAA enforcement is active but recording_url is present (meaning call recorded without gate/consent opt-out)
            $hasRecordingWithGdpr = CallLog::whereNotNull('recording_url')->exists();
            if ($hasRecordingWithGdpr) {
                $psiConsent = 0;
            }
        }

        $sumSeverity = 0.0;
        foreach ($exposureInstances as $instance) {
            // Delta represents whether system successfully auto-redacted (always 1 in our sweep)
            $delta = 1;
            $sumSeverity += $instance['severity'] * $delta;
        }

        $auditsDenominator = max(1, $totalAuditsCount);
        $theta = (1.0 - ($sumSeverity / $auditsDenominator)) * $psiConsent;

        // Clamp to [0.0, 1.0]
        $theta = max(0.0, min(1.0, $theta));

        // Save compliance index to tenant settings in master database
        $masterConnection = config('database.master_connection', 'sqlite');

        // Ensure we save back to master database connection dynamically
        DB::purge($masterConnection);
        $tenantOnMaster = Tenant::on($masterConnection)->find($tenant->id);
        if ($tenantOnMaster) {
            $settings = $tenantOnMaster->settings ?? [];
            $settings['compliance_integrity_index'] = $theta;
            $tenantOnMaster->settings = $settings;
            $tenantOnMaster->save();
        }
    }
}
