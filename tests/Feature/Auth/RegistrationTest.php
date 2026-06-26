<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register and have permission to manage plans and subscribe', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = auth()->user();
    expect($user->is_supervisor)->toBeTrue();

    // Verify they can access billing settings index
    $response = $this->get(route('settings.billing.index'));
    $response->assertOk();

    // Verify dashboard counts are 0 for the newly registered user
    $response = $this->get(route('dashboard'));
    $response->assertOk();
    $inertiaData = $response->original->getData()['page']['props'];
    expect($inertiaData['totalCallsCount'])->toBe(0);
    expect($inertiaData['successfulBookingsCount'])->toBe(0);
    expect($inertiaData['openJobsTodayCount'])->toBe(0);

    // Verify they can successfully call checkout for plan upgrade (mocks upgrade in test mode)
    $response = $this->postJson(route('billing.checkout'), [
        'plan' => 'pro',
    ]);
    $response->assertOk()
        ->assertJsonStructure(['url']);

    expect($response->json('url'))->toContain('checkout=success');
});
