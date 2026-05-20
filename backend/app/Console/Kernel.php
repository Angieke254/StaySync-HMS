<?php

namespace App\Console;

use App\Models\Booking;
use App\Models\HousekeepingTask;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->call(function () {
            Booking::where('status', 'checked_in')
                ->whereDate('check_out_date', today())
                ->get()
                ->each(function (Booking $booking) {
                    HousekeepingTask::firstOrCreate(
                        ['room_id' => $booking->room_id, 'status' => 'pending'],
                        [
                            'priority' => 'normal',
                            'notes' => 'Auto-generated: Checkout day cleaning',
                        ]
                    );
                });
        })->dailyAt('11:00');

        $schedule->call(function () {
            Booking::where('status', 'confirmed')
                ->whereDate('check_in_date', '<', today())
                ->update(['status' => 'no_show']);
        })->dailyAt('00:05');

        $schedule->command('emails:pre-arrival')->dailyAt('09:00');
        $schedule->command('emails:post-departure')->dailyAt('10:00');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
    }
}
