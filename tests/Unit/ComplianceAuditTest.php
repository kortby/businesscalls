<?php

use App\Http\Middleware\ResolveCustomDomain;
use App\Models\AuditLog;
use App\Models\CallLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\TenantShard;
use App\Services\DomainSSLService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    TenantScope::setTenantId(null);

    // 1. Create file-based SQLite database for master
    $masterPath = storage_path('master_test.sqlite');
    if (! file_exists($masterPath)) {
        touch($masterPath);
    }

    // 2. Create file-based SQLite database for tenant shard
    $tenantPath = storage_path('tenant_test.sqlite');
    if (! file_exists($tenantPath)) {
        touch($tenantPath);
    }

    // 3. Override database configurations dynamically
    config()->set('database.connections.sqlite.database', $masterPath);
    config()->set('database.connections.tenant', [
        'driver' => 'sqlite',
        'database' => $tenantPath,
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]);

    DB::purge('sqlite');
    DB::purge('tenant');
    DB::setDefaultConnection('sqlite');

    // 4. Create master schemas using raw PDO SQL
    $masterPdo = DB::connection('sqlite')->getPdo();
    $masterPdo->exec('
        CREATE TABLE IF NOT EXISTS tenants (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug VARCHAR(255) UNIQUE,
            name VARCHAR(255),
            plan VARCHAR(255),
            settings TEXT,
            secret_key VARCHAR(255),
            domain VARCHAR(255) UNIQUE,
            created_at DATETIME,
            updated_at DATETIME
        );
    ');
    $masterPdo->exec('
        CREATE TABLE IF NOT EXISTS tenant_shards (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER,
            driver VARCHAR(255),
            host VARCHAR(255),
            port INTEGER,
            database VARCHAR(255),
            username VARCHAR(255),
            password VARCHAR(255),
            database_config TEXT,
            created_at DATETIME,
            updated_at DATETIME
        );
    ');

    // 5. Create tenant schemas using raw PDO SQL
    $tenantPdo = DB::connection('tenant')->getPdo();
    $tenantPdo->exec('
        CREATE TABLE IF NOT EXISTS call_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER,
            call_id VARCHAR(255),
            status VARCHAR(255),
            customer_phone VARCHAR(255),
            transcript TEXT,
            summary TEXT,
            recording_url VARCHAR(255),
            is_test_mode BOOLEAN,
            created_at DATETIME,
            updated_at DATETIME
        );
    ');
    $tenantPdo->exec('
        CREATE TABLE IF NOT EXISTS audit_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            tenant_id INTEGER,
            user_id INTEGER,
            action VARCHAR(255),
            ip_address VARCHAR(255),
            browser_agent TEXT,
            payload TEXT,
            created_at DATETIME,
            updated_at DATETIME
        );
    ');
});

afterEach(function () {
    TenantScope::setTenantId(null);
    DB::setDefaultConnection('sqlite');

    // Delete temp databases
    $masterPath = storage_path('master_test.sqlite');
    if (file_exists($masterPath)) {
        @unlink($masterPath);
    }

    $tenantPath = storage_path('tenant_test.sqlite');
    if (file_exists($tenantPath)) {
        @unlink($tenantPath);
    }
});

test('AuditTenantLogCompliance command correctly scans, redacts PII, and updates compliance index', function () {
    // 1. Create a tenant on the master database
    $tenant = Tenant::create([
        'slug' => 'health-pros',
        'name' => 'Health Pros',
        'plan' => 'trial',
        'settings' => [
            'gdpr_hipaa_enforcement' => true,
        ],
    ]);

    $tenantPath = storage_path('tenant_test.sqlite');

    // 2. Setup the shard mapping
    TenantShard::create([
        'tenant_id' => $tenant->id,
        'driver' => 'sqlite',
        'database' => $tenantPath,
    ]);

    // 3. Seed call log inside tenant shard
    DB::setDefaultConnection('tenant');
    TenantScope::setTenantId($tenant->id);

    $call1 = CallLog::create([
        'tenant_id' => $tenant->id,
        'call_id' => 'call-101',
        'status' => 'ended',
        'customer_phone' => '+15550199',
        'transcript' => 'Hi, my SSN is 123-45-6789 and my birth date is 05/18/1990.',
        'summary' => 'Customer shared credit card number 1234-5678-9012-3456.',
        'recording_url' => null,
    ]);

    // Switch back to master connection to run the console command
    DB::setDefaultConnection('sqlite');
    TenantScope::setTenantId(null);

    // Run the compliance audit console command
    $exitCode = Artisan::call('compliance:audit');
    expect($exitCode)->toBe(0);

    // Switch back to tenant connection to verify results
    DB::setDefaultConnection('tenant');
    TenantScope::setTenantId($tenant->id);

    // Check that PII has been redacted
    $redactedCall = CallLog::find($call1->id);
    expect($redactedCall->transcript)->toBe('Hi, my SSN is [REDACTED] and my birth date is [REDACTED].');
    expect($redactedCall->summary)->toBe('Customer shared credit card number [REDACTED].');

    // Verify audit logs table has logged entries for the redacted event
    $auditLogs = AuditLog::where('action', 'pii_exposure_redacted')->get();
    expect($auditLogs->count())->toBe(1);
    expect($auditLogs->first()->payload['ssn_detected'])->toBeTrue();
    expect($auditLogs->first()->payload['credit_card_detected'])->toBeTrue();
    expect($auditLogs->first()->payload['dob_detected'])->toBeTrue();

    // Switch back to master to assert settings index update
    DB::setDefaultConnection('sqlite');
    TenantScope::setTenantId(null);

    $updatedTenant = Tenant::find($tenant->id);
    $theta = $updatedTenant->getSetting('compliance_integrity_index');

    // Violations sum = SSN(3.0) + CC(3.0) + DOB(1.0) = 7.0. Denominator = 1 check.
    // Index: (1 - 7/1) * 1 = -6.0 -> Clamped to 0.0
    expect((float) $theta)->toBe(0.0);
});

test('ResolveCustomDomain middleware swaps default database connection dynamically', function () {
    $tenant = Tenant::create([
        'slug' => 'plumber-pro',
        'name' => 'Plumber Pro Dashboard',
        'plan' => 'trial',
        'domain' => 'dispatch.plumberpro.com',
        'settings' => [],
    ]);

    $tenantPath = storage_path('tenant_test.sqlite');

    TenantShard::create([
        'tenant_id' => $tenant->id,
        'driver' => 'sqlite',
        'database' => $tenantPath,
    ]);

    $request = Request::create('http://dispatch.plumberpro.com/api/jobs', 'GET');
    $middleware = new ResolveCustomDomain;

    $defaultBefore = DB::getDefaultConnection();

    $response = $middleware->handle($request, function ($req) use ($tenant, $tenantPath) {
        expect(DB::getDefaultConnection())->toBe('tenant');
        expect(config('database.connections.tenant.database'))->toBe($tenantPath);
        expect(TenantScope::getTenantId())->toBe($tenant->id);

        return response('middleware-success');
    });

    expect($response->getContent())->toBe('middleware-success');

    // Restore connection
    DB::setDefaultConnection($defaultBefore);
});

test('DomainSSLService provisioning requests proxy mapping rules on Caddy API', function () {
    Http::fake([
        'http://localhost:2019/*' => Http::response(['success' => true], 200),
    ]);

    $service = new DomainSSLService;
    $result = $service->provisionSSL('dispatch.plumberpro.com');

    expect($result)->toBeTrue();

    Http::assertSent(function (Illuminate\Http\Client\Request $request) {
        return $request->url() === 'http://localhost:2019/config/apps/http/servers/srv0/routes'
            && $request['match'][0]['host'][0] === 'dispatch.plumberpro.com';
    });
});
