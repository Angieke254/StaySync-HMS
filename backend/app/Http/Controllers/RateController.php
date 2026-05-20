<?php

namespace App\Http\Controllers;

use App\Models\RateOverride;
use App\Models\RoomType;
use App\Services\ActivityLogService;
use App\Services\PricingEngine;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;

class RateController extends Controller
{
    public function index(Request $request, PricingEngine $pricingEngine)
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $roomType = RoomType::findOrFail($data['room_type_id']);
        $overrides = RateOverride::where('room_type_id', $roomType->id)
            ->whereBetween('date', [$data['start_date'], $data['end_date']])
            ->get()
            ->keyBy(fn ($override) => $override->date->toDateString());

        $rates = collect(CarbonPeriod::create($data['start_date'], $data['end_date']))
            ->map(function ($date) use ($roomType, $overrides, $pricingEngine) {
                $key = $date->toDateString();
                $override = $overrides->get($key);

                return [
                    'date' => $key,
                    'rate' => $pricingEngine->calculateNightlyRate($roomType, $date),
                    'is_override' => (bool) $override,
                    'override_id' => $override?->id,
                    'reason' => $override?->reason,
                ];
            });

        return response()->json([
            'room_type' => $roomType,
            'rates' => $rates,
        ]);
    }

    public function storeOverride(Request $request, ActivityLogService $activityLogService)
    {
        $data = $request->validate([
            'room_type_id' => ['required', 'exists:room_types,id'],
            'date' => ['required', 'date'],
            'override_rate' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $override = RateOverride::updateOrCreate(
            ['room_type_id' => $data['room_type_id'], 'date' => $data['date']],
            [
                'override_rate' => $data['override_rate'],
                'reason' => $data['reason'] ?? null,
                'created_at' => now(),
            ]
        );

        $activityLogService->log('rate_override_saved', $override, 'Rate override saved.', $request);

        return response()->json($override, 201);
    }

    public function destroy(RateOverride $rateOverride)
    {
        $rateOverride->delete();

        return response()->json(null, 204);
    }
}
