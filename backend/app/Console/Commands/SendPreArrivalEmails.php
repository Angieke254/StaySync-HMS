<?php

namespace App\Console\Commands;

use App\Mail\PreArrivalMail;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPreArrivalEmails extends Command
{
    protected $signature = 'emails:pre-arrival';

    protected $description = 'Send pre-arrival emails for tomorrow check-ins.';

    public function handle(): int
    {
        Booking::with('guest')
            ->where('status', 'confirmed')
            ->whereDate('check_in_date', now()->addDay()->toDateString())
            ->get()
            ->each(function (Booking $booking) {
                if ($booking->guest?->email) {
                    Mail::to($booking->guest->email)->queue(new PreArrivalMail($booking));
                }
            });

        return self::SUCCESS;
    }
}
