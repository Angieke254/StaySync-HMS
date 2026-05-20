<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\FolioCharge;
use App\Services\ActivityLogService;
use App\Services\FolioService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FolioController extends Controller
{
    public function __construct(
        private FolioService $folioService,
        private ActivityLogService $activityLogService,
    ) {
    }

    public function show(Booking $booking)
    {
        return response()->json($this->folioService->summary($booking));
    }

    public function addCharge(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'charge_type' => ['required', Rule::in(['room', 'restaurant', 'spa', 'minibar', 'laundry', 'other'])],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'charged_at' => ['nullable', 'date'],
        ]);

        $charge = $this->folioService->addCharge($booking->id, $data, $request->user()?->id);
        $this->activityLogService->log('folio_charge_added', $charge, 'Charge added to booking ' . $booking->booking_reference, $request);

        return response()->json($charge, 201);
    }

    public function voidCharge(Request $request, FolioCharge $charge)
    {
        $charge = $this->folioService->voidCharge($charge->id, $request->user()->id);
        $this->activityLogService->log('folio_charge_voided', $charge, 'Charge voided.', $request);

        return response()->json($charge);
    }

    public function payments(Booking $booking)
    {
        return response()->json($booking->payments()->latest('paid_at')->get());
    }

    public function addPayment(Request $request, Booking $booking)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer', 'online'])],
            'transaction_reference' => ['nullable', 'string', 'max:150'],
            'status' => ['sometimes', Rule::in(['pending', 'completed', 'refunded'])],
            'paid_at' => ['nullable', 'date'],
        ]);

        $payment = $this->folioService->addPayment($booking->id, $data);
        $this->activityLogService->log('payment_recorded', $payment, 'Payment recorded for booking ' . $booking->booking_reference, $request);

        return response()->json($payment, 201);
    }

    public function invoice(Booking $booking)
    {
        $folio = $this->folioService->summary($booking);

        if (app()->bound('dompdf.wrapper')) {
            return app('dompdf.wrapper')
                ->loadView('invoice', ['booking' => $booking, 'folio' => $folio])
                ->download($booking->booking_reference . '.pdf');
        }

        return response()->view('invoice', ['booking' => $booking, 'folio' => $folio]);
    }
}
