<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        return Payment::with('booking')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:cash,mpesa,card,bank',
            'payment_date' => 'required|date',
            'reference_number' => 'nullable|string'
        ]);

        $payment = Payment::create($validated);

        return response()->json($payment, 201);
    }

    public function show($id)
    {
        return Payment::with('booking')->findOrFail($id);
    }

    public function destroy($id)
    {
        Payment::destroy($id);

        return response()->json([
            'message' => 'Payment deleted successfully'
        ]);
    }
}