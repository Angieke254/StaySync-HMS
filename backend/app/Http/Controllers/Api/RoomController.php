<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use App\Models\Booking;

class RoomController extends Controller
{
    public function index()
    {
        return Room::with('roomType')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_type_id' => 'required',
            'room_number' => 'required|unique:rooms',
            'floor' => 'required',
            'status' => 'required|in:available,occupied,dirty,cleaning,maintenance,out_of_service',
            'is_active' => 'required'
        ]);

        $room = Room::create($validated);

        return response()->json($room, 201);
    }

    public function show(Room $room)
    {
        return $room->load('roomType');
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
    'room_type_id' => 'sometimes|required',
    'room_number' => 'sometimes|required|unique:rooms,room_number,' . $room->id,
    'floor' => 'sometimes|required',

    'status' => 'sometimes|required|in:available,occupied,dirty,cleaning,maintenance,out_of_service',

    'is_active' => 'sometimes|required|boolean'
]);

$room->update($validated);

        return response()->json($room);
    }

    public function destroy(Room $room)
    {
        $room->delete();

        return response()->json([
            'message' => 'Room deleted'
        ]);
    }
public function availableRooms(Request $request)
{
    $request->validate([
        'check_in_date' => 'required|date',
        'check_out_date' => 'required|date|after:check_in_date'
    ]);

    $bookedRoomIds = Booking::where(function ($query) use ($request) {
        $query->whereBetween('check_in_date', [
            $request->check_in_date,
            $request->check_out_date
        ])
        ->orWhereBetween('check_out_date', [
            $request->check_in_date,
            $request->check_out_date
        ]);
    })->pluck('room_id');

    $rooms = Room::whereNotIn('id', $bookedRoomIds)
        ->where('status', 'available')
        ->get();

    return response()->json($rooms);
}
}