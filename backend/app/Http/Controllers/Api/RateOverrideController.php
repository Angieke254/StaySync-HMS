<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RateOverride;

class RateOverrideController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | GET ALL RATE OVERRIDES
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return response()->json(
            RateOverride::with('roomType')->get()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE RATE OVERRIDE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'room_type_id' => 'required|exists:room_types,id',

            'start_date' => 'required|date',

            'end_date' => 'required|date|after_or_equal:start_date',

            'price' => 'required|numeric|min:0',
        ]);

        $override = RateOverride::create($validated);

        return response()->json([
            'message' => 'Rate override created successfully',
            'data' => $override
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW SINGLE RATE OVERRIDE
    |--------------------------------------------------------------------------
    */

    public function show(string $id)
    {
        $override = RateOverride::with('roomType')->find($id);

        if (!$override) {
            return response()->json([
                'message' => 'Rate override not found'
            ], 404);
        }

        return response()->json($override);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE RATE OVERRIDE
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, string $id)
    {
        $override = RateOverride::find($id);

        if (!$override) {
            return response()->json([
                'message' => 'Rate override not found'
            ], 404);
        }

        $validated = $request->validate([

            'room_type_id' => 'sometimes|exists:room_types,id',

            'start_date' => 'sometimes|date',

            'end_date' => 'sometimes|date|after_or_equal:start_date',

            'price' => 'sometimes|numeric|min:0',
        ]);

        $override->update($validated);

        return response()->json([
            'message' => 'Rate override updated successfully',
            'data' => $override
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE RATE OVERRIDE
    |--------------------------------------------------------------------------
    */

    public function destroy(string $id)
    {
        $override = RateOverride::find($id);

        if (!$override) {
            return response()->json([
                'message' => 'Rate override not found'
            ], 404);
        }

        $override->delete();

        return response()->json([
            'message' => 'Rate override deleted successfully'
        ]);
    }
}