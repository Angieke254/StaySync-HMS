<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\RateOverride;
use App\Models\Room;
use App\Models\RoomType;
use App\Models\Setting;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class PricingEngine
{
    public function calculate(RoomType $roomType, string $checkIn, string $checkOut): array
    {
        $nights = [];
        $subtotal = 0.0;

        foreach (CarbonPeriod::create($checkIn, Carbon::parse($checkOut)->subDay()) as $date) {
            $rate = $this->calculateNightlyRate($roomType, $date);
            $nights[] = [
                'date' => $date->toDateString(),
                'rate' => $rate,
            ];
            $subtotal += $rate;
        }

        $taxRate = (float) (Setting::where('key', 'tax_rate')->value('value') ?? 0);
        $taxAmount = round($subtotal * $taxRate, 2);

        return [
            'nights' => $nights,
            'subtotal' => round($subtotal, 2),
            'tax_amount' => $taxAmount,
            'discount_amount' => 0.00,
            'total_price' => round($subtotal + $taxAmount, 2),
        ];
    }

    public function calculateNightlyRate(RoomType $roomType, Carbon $date): float
    {
        $override = RateOverride::where('room_type_id', $roomType->id)
            ->whereDate('date', $date->toDateString())
            ->first();

        if ($override) {
            return (float) $override->override_rate;
        }

        $rate = (float) $roomType->base_rate;

        if ($date->isFriday() || $date->isSaturday()) {
            $rate *= 1.15;
        }

        $occupancyRate = $this->getOccupancyRate($date);

        if ($occupancyRate > 0.85) {
            $rate *= 1.20;
        } elseif ($occupancyRate > 0.70) {
            $rate *= 1.10;
        }

        return round($rate, 2);
    }

    public function getOccupancyRate(Carbon $date): float
    {
        $totalRooms = Room::where('is_active', true)->count();

        if ($totalRooms === 0) {
            return 0.0;
        }

        $occupiedRooms = Booking::whereIn('status', ['confirmed', 'checked_in'])
            ->whereDate('check_in_date', '<=', $date->toDateString())
            ->whereDate('check_out_date', '>', $date->toDateString())
            ->distinct('room_id')
            ->count('room_id');

        return $occupiedRooms / $totalRooms;
    }
}
