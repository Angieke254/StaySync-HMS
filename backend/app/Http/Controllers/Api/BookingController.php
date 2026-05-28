<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use Barryvdh\DomPDF\Facade\Pdf;

class BookingController extends Controller
{
    protected $bookingService;

    public function __construct(BookingService $bookingService)
    {
        $this->bookingService = $bookingService;
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL BOOKINGS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return response()->json(
            Booking::with(['guest', 'room', 'roomType'])->get()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE BOOKING
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'guest_id' => 'required|exists:guests,id',
            'room_id' => 'required|exists:rooms,id',
            'room_type_id' => 'required|exists:room_types,id',

            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date',

            'num_adults' => 'required|integer|min:1',
            'num_children' => 'nullable|integer|min:0',

            'source' => 'nullable|string',
            'special_requests' => 'nullable|string',
        ]);

        $booking = $this->bookingService->createBooking($validated);

        return response()->json($booking, 201);
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW SINGLE BOOKING
    |--------------------------------------------------------------------------
    */

    public function show($id)
    {
        $booking = Booking::with([
            'guest',
            'room',
            'roomType',
            'payments',
            'folioCharges'
        ])->find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        return response()->json($booking);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE BOOKING
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        $validated = $request->validate([

            'check_in_date' => 'sometimes|date',
            'check_out_date' => 'sometimes|date',

            'num_adults' => 'sometimes|integer|min:1',
            'num_children' => 'nullable|integer|min:0',

            'status' => 'sometimes|string',

            'special_requests' => 'nullable|string',
        ]);

        $booking->update($validated);

        return response()->json($booking);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE BOOKING
    |--------------------------------------------------------------------------
    */

    public function destroy($id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json([
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->delete();

        return response()->json([
            'message' => 'Booking deleted successfully'
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | AVAILABLE ROOMS
    |--------------------------------------------------------------------------
    */

    public function availableRooms(Request $request)
    {
        $request->validate([
            'check_in_date' => 'required|date',
            'check_out_date' => 'required|date|after:check_in_date'
        ]);

        $bookedRoomIds = Booking::where(function ($query) use ($request) {

            $query->whereBetween('check_in_date', [
                $request->check_in_date,
                $request->check_out_date
            ])

            ->orWhereBetween('check_out_date', [
                $request->check_in_date,
                $request->check_out_date
            ]);

        })->pluck('room_id');

        $rooms = Room::whereNotIn('id', $bookedRoomIds)
            ->where('status', 'available')
            ->get();

        return response()->json($rooms);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK IN
    |--------------------------------------------------------------------------
    */

    public function checkIn($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'confirmed') {
            return response()->json([
                'message' => 'Only confirmed bookings can check in.'
            ], 400);
        }

        $booking->update([
            'status' => 'checked_in',
            'actual_check_in' => now()
        ]);

        $booking->room->update([
            'status' => 'occupied'
        ]);

        return response()->json([
            'message' => 'Guest checked in successfully',
            'booking' => $booking
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CHECK OUT
    |--------------------------------------------------------------------------
    */

    public function checkOut($id)
    {
        $booking = Booking::findOrFail($id);

        if ($booking->status !== 'checked_in') {
            return response()->json([
                'message' => 'Guest is not checked in.'
            ], 400);
        }

        $booking->update([
            'status' => 'checked_out',
            'actual_check_out' => now()
        ]);

        $booking->room->update([
            'status' => 'dirty'
        ]);

        return response()->json([
            'message' => 'Guest checked out successfully',
            'booking' => $booking
        ]);
    }

                    //CALENDAR
public function calendar()
{
    $bookings = Booking::with('room')
        ->get()
        ->map(function ($booking) {

            return [
                'room_id' => $booking->room_id,
                'room_number' => $booking->room->room_number,
                'status' => $booking->status,
                'check_in' => $booking->check_in_date,
                'check_out' => $booking->check_out_date,
            ];
        });

    return response()->json($bookings);
}
       //INVOICE

           public function invoice($id)
{
    $booking = Booking::with(
        'guest',
        'room',
        'payments'
    )->findOrFail($id);

    $pdf = Pdf::loadView(
        'invoice',
        compact('booking')
    );

    return $pdf->download(
        'invoice-'.$booking->booking_reference.'.pdf'
    );
}
}