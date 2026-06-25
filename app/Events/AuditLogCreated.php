<?php

namespace App\Events;

use App\Models\AuditLog;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuditLogCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public AuditLog $auditLog
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.'.$this->auditLog->tenant_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->auditLog->id,
            'action' => $this->auditLog->action,
            'ip_address' => $this->auditLog->ip_address,
            'browser_agent' => $this->auditLog->browser_agent,
            'payload' => $this->auditLog->payload,
            'created_at' => $this->auditLog->created_at?->toIso8601String(),
            'user' => $this->auditLog->user ? [
                'name' => $this->auditLog->user->name,
                'email' => $this->auditLog->user->email,
            ] : null,
        ];
    }
}
