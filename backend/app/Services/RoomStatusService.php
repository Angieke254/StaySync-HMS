<?php

namespace App\Services;

use App\Events\RoomStatusChanged;
use App\Models\Room;
use App\Models\RoomStatusLog;
use Illuminate\Support\Facades\DB;

class RoomStatusService
{
    public function changeStatus(int|Room $room, string $status, ?int $userId = null, ?string $notes = null): Room
    {
        return DB::transaction(function () use ($room, $status, $userId, $notes) {
            $room = $room instanceof Room
                ? Room::whereKey($room->id)->lockForUpdate()->firstOrFail()
                : Room::whereKey($room)->lockForUpdate()->firstOrFail();

            $previous = $room->status;

            if ($previous === $status) {
                return $room;
            }

            $room->update(['status' => $status]);

            RoomStatusLog::create([
                'room_id' => $room->id,
                'previous_status' => $previous,
                'new_status' => $status,
                'changed_by' => $userId,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $room = $room->refresh();
            event(new RoomStatusChanged($room));

            return $room;
        });
    }
}
