<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Mail\BookingConfirmationMail;
use App\Models\Room;
use App\Services\ActivityLogService;
use App\Services\BookingService;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(
        private BookingService $bookingService,
        private ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request)
    {
        $bookings = Booking::with(['guest', 'room.roomType'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('guest'), fn ($query) => $query->where('guest_id', $request->guest))
            ->when($request->filled('room'), fn ($query) => $query->where('room_id', $request->room))
            ->when($request->filled('start_date'), fn ($query) => $query->whereDate('check_in_date', '>=', $request->start_date))
            ->when($request->filled('end_date'), fn ($query) => $query->whereDate('check_out_date', '<=', $request->end_date))
            ->latest()
            ->paginate($request->integer('per_page', 25));

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $booking = $this->bookingService->createBooking($this->validateBooking($request));
        $this->activityLogService->log('booking_created', $booking, 'Booking ' . $booking->booking_reference . ' created.', $request);

        if ($booking->guest?->email) {
            Mail::to($booking->guest->email)->queue(new BookingConfirmationMail($booking));
        }

        return response()->json($booking, 201);
    }

    public function show(Booking $booking)
    {
        return response()->json($booking->load(['guest', 'room.roomType', 'addons', 'folioCharges', 'payments']));
    }

    public function update(Request $request, Booking $booking)
    {
        $data = $this->validateBooking($request, true);
        $booking = $this->bookingService->updateBooking($booking, $data);
        $this->activityLogService->log('booking_updated', $booking, 'Booking ' . $booking->booking_reference . ' updated.', $request);

        return response()->json($booking);
    }

    public function checkIn(Request $request, Booking $booking)
    {
        $booking = $this->bookingService->checkIn($booking, $request->user()?->id);
        $this->activityLogService->log('booking_checked_in', $booking, 'Guest checked in.', $request);

        return response()->json($booking);
    }

    public function checkOut(Request $request, Booking $booking)
    {
        $booking = $this->bookingService->checkOut($booking, $request->user()?->id);
        $this->activityLogService->log('booking_checked_out', $booking, 'Guest checked out.', $request);

        return response()->json($booking);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);
        $booking = $this->bookingService->cancel($booking, $data['reason'] ?? null);
        $this->activityLogService->log('booking_cancelled', $booking, $data['reason'] ?? 'Booking cancelled.', $request);

        return response()->json($booking);
    }

    public function noShow(Request $request, Booking $booking)
    {
        $booking = $this->bookingService->markNoShow($booking);
        $this->activityLogService->log('booking_no_show', $booking, 'Booking marked no-show.', $request);

        return response()->json($booking);
    }

    public function tapeChart(Request $request)
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $dateRange = collect(CarbonPeriod::create($data['start_date'], $data['end_date']))
            ->map(fn ($date) => $date->toDateString())
            ->values();

        $rooms = Room::with(['roomType', 'bookings' => function ($query) use ($data) {
            $query->with('guest')
                ->whereNotIn('status', ['cancelled', 'no_show'])
                ->where('check_in_date', '<=', $data['end_date'])
                ->where('check_out_date', '>=', $data['start_date']);
        }])
            ->where('is_active', true)
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get()
            ->map(function (Room $room) {
                return [
                    'id' => $room->id,
                    'room_number' => $room->room_number,
                    'room_type' => $room->roomType?->name,
                    'floor' => $room->floor,
                    'bookings' => $room->bookings->map(fn (Booking $booking) => [
                        'id' => $booking->id,
                        'guest_name' => $booking->guest?->full_name,
                        'check_in' => $booking->check_in_date->toDateString(),
                        'check_out' => $booking->check_out_date->toDateString(),
                        'status' => $booking->status,
                        'color' => [
                            'confirmed' => '#3B82F6',
                            'checked_in' => '#22C55E',
                            'checked_out' => '#6B7280',
                            'cancelled' => '#EF4444',
                            'no_show' => '#F97316',
                        ][$booking->status] ?? '#64748B',
                    ])->values(),
                ];
            });

        return response()->json([
            'rooms' => $rooms,
            'date_range' => $dateRange,
        ]);
    }

    public function destroy(Request $request, Booking $booking)
    {
        return $this->cancel($request, $booking);
    }

    private function validateBooking(Request $request, bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        $guestRule = $partial ? ['nullable', 'exists:guests,id'] : ['nullable', 'exists:guests,id', 'required_without:guest'];
        $newGuestRule = $partial ? ['nullable', 'array'] : ['nullable', 'array', 'required_without:guest_id'];

        return $request->validate([
            'guest_id' => $guestRule,
            'guest' => $newGuestRule,
            'guest.first_name' => ['required_with:guest', 'string', 'max:100'],
            'guest.last_name' => ['required_with:guest', 'string', 'max:100'],
            'guest.email' => ['nullable', 'email', 'max:150'],
            'guest.phone' => ['nullable', 'string', 'max:30'],
            'room_id' => [$required, 'exists:rooms,id'],
            'check_in_date' => [$required, 'date'],
            'check_out_date' => [$required, 'date', 'after:check_in_date'],
            'num_adults' => ['sometimes', 'integer', 'min:1'],
            'num_children' => ['sometimes', 'integer', 'min:0'],
            'status' => ['sometimes', Rule::in(['confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show'])],
            'source' => ['sometimes', Rule::in(['walk_in', 'phone', 'website', 'ota'])],
            'special_requests' => ['nullable', 'string'],
            'addons' => ['sometimes', 'array'],
            'addons.*.description' => ['required_with:addons', 'string', 'max:255'],
            'addons.*.quantity' => ['sometimes', 'integer', 'min:1'],
            'addons.*.unit_price' => ['required_with:addons', 'numeric', 'min:0'],
        ]);
    }
}
