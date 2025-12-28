<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\Review;
use App\Enums\BookingStatus;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all completed bookings that don't have a review
        $bookings = Booking::where('status', BookingStatus::Completed)
            ->doesntHave('review')
            ->get();

        foreach ($bookings as $booking) {
            Review::factory()->create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'doctor_id' => $booking->doctor_id,
            ]);
        }
    }
}
