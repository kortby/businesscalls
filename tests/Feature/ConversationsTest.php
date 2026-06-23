<?php

use App\Events\ChatMessageReceived;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    TenantScope::setTenantId(null);
});

test('authenticated user can load conversations index', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);

    Conversation::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15551112222',
        'status' => 'open',
    ]);

    $response = $this->actingAs($user)
        ->get('/conversations');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Conversations/Index')
        ->has('conversations')
        ->where('tenant.id', $tenant->id)
    );
});

test('user cannot access conversations of another tenant', function () {
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();

    $user1 = User::factory()->create(['tenant_id' => $tenant1->id]);

    TenantScope::setTenantId($tenant2->id);
    $conv2 = Conversation::factory()->create([
        'tenant_id' => $tenant2->id,
        'customer_phone' => '+15553334444',
    ]);

    // Try to send a message to tenant2's conversation as tenant1 user
    $response = $this->actingAs($user1)
        ->post("/conversations/{$conv2->id}/messages", [
            'body' => 'Hello another tenant',
        ]);

    $response->assertStatus(403);
});

test('authenticated user can send chat message which triggers broadcast event', function () {
    Event::fake();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    TenantScope::setTenantId($tenant->id);
    $conv = Conversation::factory()->create([
        'tenant_id' => $tenant->id,
        'customer_phone' => '+15555556666',
    ]);

    $response = $this->actingAs($user)
        ->post("/conversations/{$conv->id}/messages", [
            'body' => 'Hi, your booking is confirmed.',
        ]);

    $response->assertRedirect();

    // Assert message exists in DB
    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conv->id,
        'sender' => 'agent',
        'body' => 'Hi, your booking is confirmed.',
    ]);

    // Assert event was broadcasted
    Event::assertDispatched(ChatMessageReceived::class, function ($event) use ($tenant) {
        return $event->tenantId === $tenant->id && $event->message->body === 'Hi, your booking is confirmed.';
    });
});
