<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $booking->booking_reference }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 22px; margin-bottom: 4px; }
        h2 { font-size: 15px; margin-top: 24px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border-bottom: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        .muted { color: #6b7280; }
        .right { text-align: right; }
        .summary { margin-top: 20px; width: 45%; margin-left: auto; }
    </style>
</head>
<body>
    <h1>StaySync Hotel</h1>
    <div class="muted">Invoice {{ $booking->booking_reference }}</div>

    <h2>Guest</h2>
    <p>
        {{ $booking->guest->full_name }}<br>
        {{ $booking->guest->email }}<br>
        Room {{ $booking->room->room_number }} - {{ $booking->room->roomType->name }}<br>
        {{ $booking->check_in_date->toDateString() }} to {{ $booking->check_out_date->toDateString() }}
    </p>

    <h2>Charges</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Type</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($folio['charges'] as $charge)
                <tr>
                    <td>{{ optional($charge->charged_at)->toDateString() }}</td>
                    <td>{{ $charge->description }}</td>
                    <td>{{ $charge->charge_type }}</td>
                    <td class="right">{{ number_format($charge->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Payments</h2>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Method</th>
                <th>Reference</th>
                <th class="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($folio['payments'] as $payment)
                <tr>
                    <td>{{ optional($payment->paid_at)->toDateString() }}</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ $payment->transaction_reference }}</td>
                    <td class="right">{{ number_format($payment->amount, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary">
        <tr><th>Total charges</th><td class="right">{{ number_format($folio['summary']['totalCharges'], 2) }}</td></tr>
        <tr><th>Total paid</th><td class="right">{{ number_format($folio['summary']['totalPayments'], 2) }}</td></tr>
        <tr><th>Balance</th><td class="right">{{ number_format($folio['summary']['balance'], 2) }}</td></tr>
    </table>
</body>
</html>
