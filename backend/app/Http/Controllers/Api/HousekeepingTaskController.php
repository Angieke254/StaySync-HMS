<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HousekeepingTask;
use Illuminate\Http\Request;

class HousekeepingTaskController extends Controller
{
    public function index()
    {
        return HousekeepingTask::with('room', 'assignedStaff')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'assigned_to' => 'nullable|exists:users,id',
            'task_type' => 'required|string',
            'status' => 'required|in:pending,in_progress,completed',
            'notes' => 'nullable|string'
        ]);

        $task = HousekeepingTask::create($validated);

        return response()->json($task, 201);
    }

    public function show($id)
    {
        return HousekeepingTask::with('room', 'assignedStaff')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $task = HousekeepingTask::findOrFail($id);

        $task->update($request->all());

        return response()->json($task);
    }

    public function destroy($id)
    {
        HousekeepingTask::destroy($id);

        return response()->json([
            'message' => 'Task deleted successfully'
        ]);
    }
}