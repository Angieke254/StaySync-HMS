<?php

namespace App\Services;

use App\Events\BookingCreated;
use App\Events\BookingUpdated;
use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use App\Models\BookingAddon;
use App\Models\Guest;
use App\Models\HousekeepingTask;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private PricingEngine $pricingEngine,
        private RoomStatusService $roomStatusService,
        private FolioService $folioService,
    ) {
    }

    public function createBooking(array $data): Booking
    {
        return DB::transaction(function () use ($data) {
            $room = Room::with('roomType')->where('id', $data['room_id'])->lockForUpdate()->firstOrFail();

            if (!$room->is_active) {
                throw new BookingConflictException('Room is inactive.');
            }

            if (!$this->isRoomAvailable($room->id, $data['check_in_date'], $data['check_out_date'])) {
                throw new BookingConflictException('Room is not available for the selected dates.');
            }

            $guest = $this->resolveGuest($data);
            $pricing = $this->pricingEngine->calculate($room->roomType, $data['check_in_date'], $data['check_out_date']);

            $booking = Booking::create([
                'guest_id' => $guest->id,
                'room_id' => $room->id,
                'room_type_id' => $room->room_type_id,
                'check_in_date' => $data['check_in_date'],
                'check_out_date' => $data['check_out_date'],
                'num_adults' => $data['num_adults'] ?? 1,
                'num_children' => $data['num_children'] ?? 0,
                'status' => $data['status'] ?? 'confirmed',
                'source' => $data['source'] ?? 'walk_in',
                'subtotal' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'discount_amount' => $pricing['discount_amount'],
                'total_price' => $pricing['total_price'],
                'special_requests' => $data['special_requests'] ?? null,
            ]);

            foreach ($data['addons'] ?? [] as $addon) {
                BookingAddon::create([
                    'booking_id' => $booking->id,
                    'description' => $addon['description'],
                    'quantity' => $addon['quantity'] ?? 1,
                    'unit_price' => $addon['unit_price'],
                    'total_price' => ($addon['quantity'] ?? 1) * $addon['unit_price'],
                    'created_at' => now(),
                ]);
            }

            if (now()->toDateString() === $booking->check_in_date->toDateString()) {
                $this->roomStatusService->changeStatus($room, 'occupied', null, 'Auto status update for same-day booking.');
            }

            $booking = $booking->load(['guest', 'room.roomType', 'addons']);
            event(new BookingCreated($booking));

            return $booking;
        });
    }

    public function updateBooking(Booking $booking, array $data): Booking
    {
        return DB::transaction(function () use ($booking, $data) {
            $roomId = $data['room_id'] ?? $booking->room_id;
            $checkIn = $data['check_in_date'] ?? $booking->check_in_date->toDateString();
            $checkOut = $data['check_out_date'] ?? $booking->check_out_date->toDateString();
            $room = Room::with('roomType')->where('id', $roomId)->lockForUpdate()->firstOrFail();

            if (!$this->isRoomAvailable($room->id, $checkIn, $checkOut, $booking->id)) {
                throw new BookingConflictException('Room is not available for the selected dates.');
            }

            $pricing = $this->pricingEngine->calculate($room->roomType, $checkIn, $checkOut);

            $booking->update(array_merge($data, [
                'room_id' => $room->id,
                'room_type_id' => $room->room_type_id,
                'check_in_date' => $checkIn,
                'check_out_date' => $checkOut,
                'subtotal' => $pricing['subtotal'],
                'tax_amount' => $pricing['tax_amount'],
                'total_price' => $pricing['total_price'],
            ]));

            $booking = $booking->refresh()->load(['guest', 'room.roomType', 'addons', 'folioCharges', 'payments']);
            event(new BookingUpdated($booking));

            return $booking;
        });
    }

    public function checkIn(Booking $booking, ?int $userId = null): Booking
    {
        return DB::transaction(function () use ($booking, $userId) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $room = Room::whereKey($booking->room_id)->lockForUpdate()->firstOrFail();

            if (!in_array($room->status, ['available', 'cleaning'], true)) {
                throw new BookingConflictException('Room must be available or cleaning before check-in.');
            }

            $booking->update([
                'status' => 'checked_in',
                'actual_check_in' => now(),
            ]);

            $this->roomStatusService->changeStatus($room, 'occupied', $userId, 'Guest checked in.');
            $this->folioService->postRoomCharges($booking->refresh());

            $booking = $booking->refresh()->load(['guest', 'room.roomType', 'folioCharges']);
            event(new BookingUpdated($booking));

            return $booking;
        });
    }

    public function checkOut(Booking $booking, ?int $userId = null): Booking
    {
        return DB::transaction(function () use ($booking, $userId) {
            $booking = Booking::whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $booking->update([
                'status' => 'checked_out',
                'actual_check_out' => now(),
            ]);

            $this->roomStatusService->changeStatus($booking->room_id, 'cleaning', $userId, 'Guest checked out.');

            HousekeepingTask::firstOrCreate(
                ['room_id' => $booking->room_id, 'status' => 'pending'],
                ['priority' => 'normal', 'notes' => 'Checkout cleaning for ' . $booking->booking_reference]
            );

            $booking->guest?->increment('total_stays');

            $booking = $booking->refresh()->load(['guest', 'room.roomType']);
            event(new BookingUpdated($booking));

            return $booking;
        });
    }

    public function cancel(Booking $booking, ?string $reason = null): Booking
    {
        $booking->update([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
        ]);

        $booking = $booking->refresh();
        event(new BookingUpdated($booking));

        return $booking;
    }

    public function markNoShow(Booking $booking): Booking
    {
        $booking->update(['status' => 'no_show']);

        $booking = $booking->refresh();
        event(new BookingUpdated($booking));

        return $booking;
    }

    public function isRoomAvailable(int $roomId, string $checkIn, string $checkOut, ?int $ignoreBookingId = null): bool
    {
        return !Booking::where('room_id', $roomId)
            ->whereNotIn('status', ['cancelled', 'no_show'])
            ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
            ->where(function ($query) use ($checkIn, $checkOut) {
                $query->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->exists();
    }

    public function availableRooms(string $checkIn, string $checkOut, ?int $roomTypeId = null)
    {
        return Room::with('roomType')
            ->where('is_active', true)
            ->when($roomTypeId, fn ($query) => $query->where('room_type_id', $roomTypeId))
            ->whereDoesntHave('bookings', function ($query) use ($checkIn, $checkOut) {
                $query->whereNotIn('status', ['cancelled', 'no_show'])
                    ->where('check_in_date', '<', $checkOut)
                    ->where('check_out_date', '>', $checkIn);
            })
            ->orderBy('room_number')
            ->get();
    }

    private function resolveGuest(array $data): Guest
    {
        if (!empty($data['guest_id'])) {
            return Guest::findOrFail($data['guest_id']);
        }

        return Guest::create($data['guest']);
    }
}
