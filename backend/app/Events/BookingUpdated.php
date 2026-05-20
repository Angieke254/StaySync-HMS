<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing(['guest', 'room.roomType']);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('hotel.dashboard')];
    }
}
