<?php

use App\Models\User;

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('returns a successful response for authenticated user', function () {
    $user = User::factory()->create();
    $response = $this->actingAs($user)->get(route('home'));

    $response->assertOk();
});
