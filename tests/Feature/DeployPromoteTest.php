<?php

use App\Models\Tenant;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    // Ensure environments.yaml exists
    $configDir = config_path('deploy');
    if (! File::exists($configDir)) {
        File::makeDirectory($configDir, 0755, true);
    }

    // Write a standard testing environments yaml file
    File::put("{$configDir}/environments.yaml", <<<'YAML'
dev:
  slug: "dev"
  voice_assistant_id: "vapi-dev-id"
  telephony_provider: "vapi"
  telephony_phone_number: "+15550001111"
  telephony_phone_number_id: "dev-phone-sid"
  webhook_url: "https://dev.businesscalls.com/webhook"

invalid:
  slug: "invalid"
  telephony_provider: "vapi"
YAML
    );
});

test('deploy promote command successfully parses YAML and updates tenant settings', function () {
    $tenant = Tenant::factory()->create();

    $this->artisan('deploy:promote dev')
        ->assertExitCode(0);

    $tenant->refresh();
    expect($tenant->getSetting('voice_assistant_id'))->toBe('vapi-dev-id')
        ->and($tenant->getSetting('telephony_provider'))->toBe('vapi')
        ->and($tenant->getSetting('telephony_phone_number'))->toBe('+15550001111')
        ->and($tenant->getSetting('environment'))->toBe('dev');
});

test('deploy promote command fails on missing parameters and triggers git rollback', function () {
    Process::fake();
    $tenant = Tenant::factory()->create();

    $this->artisan('deploy:promote invalid')
        ->assertExitCode(1);

    Process::assertRan('git reset --hard HEAD');
});

test('deploy promote command fails on unknown environment', function () {
    $this->artisan('deploy:promote missing')
        ->assertExitCode(1);
});
