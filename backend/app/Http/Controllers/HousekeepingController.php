<?php

namespace App\Http\Controllers;

use App\Models\HousekeepingTask;
use App\Services\RoomStatusService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HousekeepingController extends Controller
{
    public function __construct(private RoomStatusService $roomStatusService)
    {
    }

    public function index(Request $request)
    {
        $tasks = HousekeepingTask::with(['room.roomType', 'assignee'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('priority'), fn ($query) => $query->where('priority', $request->priority))
            ->when($request->filled('assigned_to'), fn ($query) => $query->where('assigned_to', $request->assigned_to))
            ->when($request->filled('floor'), fn ($query) => $query->whereHas('room', fn ($room) => $room->where('floor', $request->floor)))
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($tasks);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'urgent'])],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed'])],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json(HousekeepingTask::create($data)->load(['room', 'assignee']), 201);
    }

    public function update(Request $request, HousekeepingTask $task)
    {
        $data = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
            'priority' => ['sometimes', Rule::in(['low', 'normal', 'urgent'])],
            'status' => ['sometimes', Rule::in(['pending', 'in_progress', 'completed'])],
            'notes' => ['nullable', 'string'],
        ]);

        if (($data['status'] ?? null) === 'completed') {
            $data['completed_at'] = now();
        }

        $task->update($data);

        return response()->json($task->refresh()->load(['room', 'assignee']));
    }

    public function complete(Request $request, HousekeepingTask $task)
    {
        $task->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->roomStatusService->changeStatus($task->room_id, 'available', $request->user()?->id, 'Housekeeping completed.');

        return response()->json($task->refresh()->load(['room.roomType', 'assignee']));
    }
}
