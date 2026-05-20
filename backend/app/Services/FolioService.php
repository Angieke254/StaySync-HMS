<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\FolioCharge;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class FolioService
{
    public function summary(Booking|int $booking): array
    {
        $booking = $booking instanceof Booking
            ? $booking->loadMissing(['folioCharges', 'payments'])
            : Booking::with(['folioCharges', 'payments'])->findOrFail($booking);

        $activeCharges = $booking->folioCharges->whereNull('voided_at');
        $roomCharges = $activeCharges->where('charge_type', 'room')->sum('amount');
        $otherCharges = $activeCharges->where('charge_type', '!=', 'room')->sum('amount');
        $totalCharges = $activeCharges->sum('amount');
        $totalPayments = $booking->payments->where('status', 'completed')->sum('amount');
        $balance = $totalCharges - $totalPayments;

        return [
            'booking' => $booking,
            'charges' => $booking->folioCharges->values(),
            'payments' => $booking->payments->values(),
            'summary' => compact('roomCharges', 'otherCharges', 'totalCharges', 'totalPayments', 'balance'),
        ];
    }

    public function addCharge(int $bookingId, array $data, ?int $postedBy = null): FolioCharge
    {
        return FolioCharge::create([
            'booking_id' => $bookingId,
            'charge_type' => $data['charge_type'],
            'description' => $data['description'],
            'amount' => $data['amount'],
            'posted_by' => $postedBy,
            'charged_at' => $data['charged_at'] ?? now(),
        ]);
    }

    public function voidCharge(int $chargeId, int $userId): FolioCharge
    {
        $charge = FolioCharge::findOrFail($chargeId);
        $charge->update([
            'voided_at' => now(),
            'voided_by' => $userId,
        ]);

        return $charge;
    }

    public function addPayment(int $bookingId, array $data): Payment
    {
        return Payment::create([
            'booking_id' => $bookingId,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'transaction_reference' => $data['transaction_reference'] ?? null,
            'status' => $data['status'] ?? 'completed',
            'paid_at' => $data['paid_at'] ?? now(),
        ]);
    }

    public function postRoomCharges(Booking $booking): void
    {
        DB::transaction(function () use ($booking) {
            if ($booking->folioCharges()->where('charge_type', 'room')->exists()) {
                return;
            }

            $nights = max(1, $booking->check_in_date->diffInDays($booking->check_out_date));
            $nightlyAmount = round(((float) $booking->subtotal) / $nights, 2);

            for ($i = 0; $i < $nights; $i++) {
                $date = $booking->check_in_date->copy()->addDays($i);

                $this->addCharge($booking->id, [
                    'charge_type' => 'room',
                    'description' => 'Room charge for ' . $date->toDateString(),
                    'amount' => $nightlyAmount,
                    'charged_at' => $date,
                ]);
            }
        });
    }
}
