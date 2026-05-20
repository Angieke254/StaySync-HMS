<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FolioCharge;
use App\Models\Payment;
use App\Models\Room;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalRooms = Room::where('is_active', true)->count();
        $occupied = Room::where('status', 'occupied')->count();
        $available = Room::where('status', 'available')->count();
        $cleaning = Room::where('status', 'cleaning')->count();
        $today = now()->toDateString();

        return response()->json([
            'rooms' => [
                'total' => $totalRooms,
                'occupied' => $occupied,
                'available' => $available,
                'cleaning' => $cleaning,
                'occupancy_rate' => $totalRooms > 0 ? round(($occupied / $totalRooms) * 100, 2) : 0,
            ],
            'today' => [
                'check_ins' => Booking::whereDate('check_in_date', $today)->where('status', 'confirmed')->count(),
                'check_outs' => Booking::whereDate('check_out_date', $today)->where('status', 'checked_in')->count(),
                'revenue' => $this->revenueBetween($today, $today),
            ],
            'revenue' => [
                'today' => $this->revenueBetween($today, $today),
                'this_week' => $this->revenueBetween(now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()),
                'this_month' => $this->revenueBetween(now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()),
            ],
        ]);
    }

    private function revenueBetween(string $start, string $end): float
    {
        $charges = FolioCharge::whereNull('voided_at')
            ->whereDate('charged_at', '>=', $start)
            ->whereDate('charged_at', '<=', $end)
            ->sum('amount');

        if ((float) $charges > 0) {
            return (float) $charges;
        }

        return (float) Payment::where('status', 'completed')
            ->whereDate('paid_at', '>=', $start)
            ->whereDate('paid_at', '<=', $end)
            ->sum('amount');
    }
}
