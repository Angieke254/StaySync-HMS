<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FolioCharge;
use App\Models\Guest;
use App\Models\Room;
use App\Models\RoomType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        return response()->json([
            'occupancy' => $this->occupancy($request)->getData(true),
            'revenue' => $this->revenue($request)->getData(true),
            'room_type_performance' => $this->roomTypePerformance()->getData(true),
            'guest_statistics' => $this->guestStatistics()->getData(true),
        ]);
    }

    public function occupancy(Request $request)
    {
        $data = $this->dateRange($request);
        $totalRooms = max(1, Room::where('is_active', true)->count());

        $rows = collect(CarbonPeriod::create($data['start_date'], $data['end_date']))
            ->map(function (Carbon $date) use ($totalRooms) {
                $occupied = Booking::whereIn('status', ['confirmed', 'checked_in'])
                    ->whereDate('check_in_date', '<=', $date->toDateString())
                    ->whereDate('check_out_date', '>', $date->toDateString())
                    ->distinct('room_id')
                    ->count('room_id');

                return [
                    'date' => $date->toDateString(),
                    'occupied_rooms' => $occupied,
                    'total_rooms' => $totalRooms,
                    'occupancy_percentage' => round(($occupied / $totalRooms) * 100, 2),
                ];
            });

        return response()->json($rows);
    }

    public function revenue(Request $request)
    {
        $data = $this->dateRange($request) + $request->validate([
            'group_by' => ['sometimes', 'in:day,week,month'],
        ]);

        $format = match ($data['group_by'] ?? 'day') {
            'week' => '%x-W%v',
            'month' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $rows = FolioCharge::selectRaw("DATE_FORMAT(charged_at, ?) as period, SUM(amount) as revenue", [$format])
            ->whereNull('voided_at')
            ->whereDate('charged_at', '>=', $data['start_date'])
            ->whereDate('charged_at', '<=', $data['end_date'])
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json($rows);
    }

    public function roomTypePerformance()
    {
        $rows = RoomType::query()
            ->leftJoin('rooms', 'rooms.room_type_id', '=', 'room_types.id')
            ->leftJoin('bookings', 'bookings.room_id', '=', 'rooms.id')
            ->select([
                'room_types.id',
                'room_types.name',
                DB::raw('COUNT(DISTINCT rooms.id) as room_count'),
                DB::raw("SUM(CASE WHEN bookings.status IN ('confirmed','checked_in','checked_out') THEN bookings.total_price ELSE 0 END) as revenue"),
                DB::raw("COUNT(CASE WHEN bookings.status IN ('confirmed','checked_in','checked_out') THEN bookings.id END) as booking_count"),
            ])
            ->groupBy('room_types.id', 'room_types.name')
            ->orderBy('room_types.name')
            ->get();

        return response()->json($rows);
    }

    public function guestStatistics()
    {
        return response()->json([
            'top_guests' => Guest::withCount('bookings')->orderByDesc('bookings_count')->limit(10)->get(),
            'repeat_guests' => Guest::where('total_stays', '>', 1)->count(),
            'average_stay_length' => round((float) Booking::whereNotNull('actual_check_out')
                ->selectRaw('AVG(DATEDIFF(check_out_date, check_in_date)) as average_stay')
                ->value('average_stay'), 2),
        ]);
    }

    public function export(Request $request)
    {
        return response()->json([
            'message' => 'CSV/PDF export can be added after the Laravel app has dompdf or league/csv installed.',
        ], 501);
    }

    private function dateRange(Request $request): array
    {
        return $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);
    }
}
