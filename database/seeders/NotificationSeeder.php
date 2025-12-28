<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Notification;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all users
        $users = User::all();

        if ($users->isEmpty()) {
            return;
        }

        // Create random notifications for each user
        foreach ($users as $user) {
            Notification::factory()
                ->count(rand(2, 5)) // Create 2-5 notifications per user
                ->create([
                    'user_id' => $user->id,
                ]);
        }
    }
}
