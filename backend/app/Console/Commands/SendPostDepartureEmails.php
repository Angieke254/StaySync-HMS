<?php

namespace App\Console\Commands;

use App\Mail\PostDepartureMail;
use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendPostDepartureEmails extends Command
{
    protected $signature = 'emails:post-departure';

    protected $description = 'Send post-departure emails for yesterday check-outs.';

    public function handle(): int
    {
        Booking::with('guest')
            ->where('status', 'checked_out')
            ->whereDate('actual_check_out', now()->subDay()->toDateString())
            ->get()
            ->each(function (Booking $booking) {
                if ($booking->guest?->email) {
                    Mail::to($booking->guest->email)->queue(new PostDepartureMail($booking));
                }
            });

        return self::SUCCESS;
    }
}
