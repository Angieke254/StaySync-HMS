<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuestController extends Controller
{
    public function index(Request $request)
    {
        $guests = Guest::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($inner) use ($search) {
                    $inner->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy('last_name')
            ->paginate($request->integer('per_page', 25));

        return response()->json($guests);
    }

    public function store(Request $request)
    {
        $data = $this->validateGuest($request);

        return response()->json(Guest::create($data), 201);
    }

    public function show(Guest $guest)
    {
        return response()->json($guest->load(['bookings.room.roomType']));
    }

    public function update(Request $request, Guest $guest)
    {
        $data = $this->validateGuest($request, $guest);
        $guest->update($data);

        return response()->json($guest->refresh());
    }

    public function destroy(Guest $guest)
    {
        abort_if($guest->bookings()->exists(), 409, 'Cannot delete a guest with booking history.');

        $guest->delete();

        return response()->json(null, 204);
    }

    private function validateGuest(Request $request, ?Guest $guest = null): array
    {
        $required = $guest ? 'sometimes' : 'required';

        return $request->validate([
            'first_name' => [$required, 'string', 'max:100'],
            'last_name' => [$required, 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:150', Rule::unique('guests', 'email')->ignore($guest)],
            'phone' => ['nullable', 'string', 'max:30'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'loyalty_tier' => ['sometimes', 'string', 'max:50'],
            'total_stays' => ['sometimes', 'integer', 'min:0'],
        ]);
    }
}
