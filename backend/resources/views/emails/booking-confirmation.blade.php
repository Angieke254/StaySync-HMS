<p>Hello {{ $booking->guest->first_name }},</p>
<p>Your booking {{ $booking->booking_reference }} is confirmed for {{ $booking->check_in_date->toDateString() }} to {{ $booking->check_out_date->toDateString() }}.</p>
<p>Room: {{ $booking->room->room_number }} - {{ $booking->room->roomType->name }}</p>
<p>Total: {{ number_format($booking->total_price, 2) }}</p>
