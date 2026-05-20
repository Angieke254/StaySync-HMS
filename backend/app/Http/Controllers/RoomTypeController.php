<?php

namespace App\Http\Controllers;

use App\Models\RoomType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoomTypeController extends Controller
{
    public function index()
    {
        return response()->json(RoomType::withCount('rooms')->orderBy('name')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:room_types,name'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:room_types,slug'],
            'base_rate' => ['required', 'numeric', 'min:0'],
            'max_occupancy' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'amenities' => ['nullable', 'array'],
        ]);

        return response()->json(RoomType::create($data), 201);
    }

    public function show(RoomType $roomType)
    {
        return response()->json($roomType->loadCount('rooms'));
    }

    public function update(Request $request, RoomType $roomType)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100', Rule::unique('room_types', 'name')->ignore($roomType)],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('room_types', 'slug')->ignore($roomType)],
            'base_rate' => ['sometimes', 'numeric', 'min:0'],
            'max_occupancy' => ['sometimes', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'amenities' => ['nullable', 'array'],
        ]);

        $roomType->update($data);

        return response()->json($roomType->refresh());
    }

    public function destroy(RoomType $roomType)
    {
        abort_if($roomType->rooms()->exists(), 409, 'Cannot delete a room type that still has rooms.');

        $roomType->delete();

        return response()->json(null, 204);
    }
}
