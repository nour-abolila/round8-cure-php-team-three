<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Enums\BookingStatus;

class CleanupExpiredReservations extends Command
{
    protected $signature = 'booking:cleanup-reservations';
    protected $description = 'Cancel expired reserved bookings';

    public function handle()
    {
        $count = Booking::where('status',BookingStatus::Reserved)
            ->where('created_at','<',now()->subMinutes(10))
            ->update(['status'=>BookingStatus::Cancelled]);

        $this->info("Expired reservations cleaned: {$count}");
    }
}
