<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Guest;

class GuestController extends Controller
{
    // GET ALL GUESTS
    public function index()
    {
        return response()->json(Guest::latest()->get());
    }

    // STORE GUEST
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:guests,email',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:20',
            'nationality' => 'nullable|string|max:100',
            'id_type' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        $guest = Guest::create($validated);

        return response()->json([
            'message' => 'Guest created successfully',
            'data' => $guest
        ], 201);
    }

    // SHOW SINGLE GUEST
    public function show(string $id)
    {
        $guest = Guest::findOrFail($id);

        return response()->json($guest);
    }

    // UPDATE GUEST
    public function update(Request $request, string $id)
    {
        $guest = Guest::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'nullable|email|unique:guests,email,' . $guest->id,
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|string|max:20',
            'nationality' => 'nullable|string|max:100',
            'id_type' => 'nullable|string|max:100',
            'id_number' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        $guest->update($validated);

        return response()->json([
            'message' => 'Guest updated successfully',
            'data' => $guest
        ]);
    }

    // DELETE GUEST
    public function destroy(string $id)
    {
        $guest = Guest::findOrFail($id);

        $guest->delete();

        return response()->json([
            'message' => 'Guest deleted successfully'
        ]);
    }
}