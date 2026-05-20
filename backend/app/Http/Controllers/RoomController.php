<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Services\BookingService;
use App\Services\RoomStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomController extends Controller
{
    public function __construct(private RoomStatusService $roomStatusService)
    {
    }

    public function index(Request $request)
    {
        $rooms = Room::with('roomType')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('room_type'), fn ($query) => $query->where('room_type_id', $request->room_type))
            ->when($request->filled('floor'), fn ($query) => $query->where('floor', $request->floor))
            ->orderBy('floor')
            ->orderBy('room_number')
            ->paginate($request->integer('per_page', 25));

        return response()->json($rooms);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'room_number' => ['required', 'string', 'max:20', 'unique:rooms,room_number'],
            'floor' => ['nullable', 'integer'],
            'status' => ['sometimes', Rule::in(['available', 'occupied', 'maintenance', 'cleaning'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        return response()->json(Room::create($data)->load('roomType'), 201);
    }

    public function show(Room $room)
    {
        return response()->json($room->load(['roomType', 'statusLogs', 'housekeepingTasks']));
    }

    public function update(Request $request, Room $room)
    {
        $data = $request->validate([
            'room_type_id' => ['sometimes', 'exists:room_types,id'],
            'room_number' => ['sometimes', 'string', 'max:20', Rule::unique('rooms', 'room_number')->ignore($room)],
            'floor' => ['nullable', 'integer'],
            'status' => ['sometimes', Rule::in(['available', 'occupied', 'maintenance', 'cleaning'])],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $room->update($data);

        return response()->json($room->refresh()->load('roomType'));
    }

    public function updateStatus(Request $request, Room $room)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['available', 'occupied', 'maintenance', 'cleaning'])],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json(
            $this->roomStatusService->changeStatus($room, $data['status'], $request->user()?->id, $data['notes'] ?? null)
        );
    }

    public function availability(Request $request, BookingService $bookingService)
    {
        $data = $request->validate([
            'check_in' => ['required', 'date'],
            'check_out' => ['required', 'date', 'after:check_in'],
            'room_type_id' => ['nullable', 'exists:room_types,id'],
        ]);

        return response()->json($bookingService->availableRooms(
            $data['check_in'],
            $data['check_out'],
            $data['room_type_id'] ?? null
        ));
    }

    public function destroy(Room $room)
    {
        abort_if($room->bookings()->exists(), 409, 'Cannot delete a room that has bookings.');

        $room->delete();

        return response()->json(null, 204);
    }
}
