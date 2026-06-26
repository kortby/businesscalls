<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TechnicianLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Booking $booking,
        public float $latitude,
        public float $longitude,
        public float $etaMinutes
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('booking-tracking.'.$this->booking->booking_hash),
        ];
    }

    /**
     * The name of the event being broadcasted.
     */
    public function broadcastAs(): string
    {
        return 'TechnicianLocationUpdated';
    }
}
