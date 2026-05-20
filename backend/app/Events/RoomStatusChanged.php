<?php

namespace App\Events;

use App\Models\Room;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Room $room)
    {
        $this->room->loadMissing('roomType');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('hotel.dashboard'),
            new PrivateChannel('hotel.housekeeping'),
        ];
    }
}
