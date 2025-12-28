<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Booking;
use App\Models\User;
use App\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;

class DoctorBookingsSeeder extends Seeder
{
    public function run()
    {
        $doctor = \App\Models\Doctor::first();

        if (!$doctor) {
            $this->command->warn('No doctors found. Skipping DoctorBookingsSeeder.');
            return;
        }

        $doctorId = $doctor->id;

        // Create a user to act as a patient if none exist (excluding the doctor's user)
        $user = User::where('id', '!=', $doctor->user_id)->first();

        if (!$user) {
             $user = User::factory()->create();
             $user->assignRole('patient');
        }

        $users = [$user]; // Use array for loop compatibility

        $statuses = ['Upcoming', 'Completed', 'Cancelled'];

        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                Booking::create([
                    'user_id' => $user->id,
                    'doctor_id' => $doctorId,
                    'booking_date' => now()->addDays(rand(0, 10))->toDateString(),
                    'booking_time' => now()->addHours(rand(8, 17))->format('H:i:s'),
                    'status' => $statuses[array_rand($statuses)],
                    'price' => rand(50, 200),
                    'payment_method_id' => 1,
                ]);
            }
        }
    }
}
