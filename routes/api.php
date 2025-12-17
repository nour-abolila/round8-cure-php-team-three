<?php

use App\Http\Controllers\Api\NotificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Notification Routes
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/delete-all', [NotificationController::class, 'destroyAll']);

    Route::get('/notifications/{notification}', [NotificationController::class, 'show']);
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markAsRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);
});

/*
Route::get('/test-notification', function () {
    $user = \App\Models\User::first();
    if (!$user) {
        $user = \App\Models\User::factory()->create([
             'profile_photo' => 'default.jpg',
             'mobile_number' => '1234567890',
             'birth_date' => '1990-01-01',
             'location' => json_encode(['lat' => 0, 'lng' => 0]),
        ]);
    }

    // Create token for this user
    $token = $user->createToken('test-token')->plainTextToken;

    $specialization = \App\Models\Specialization::firstOrCreate(['name' => 'General']);

    $doctor = \App\Models\Doctor::first();
    if(!$doctor) {
        $doctor = \App\Models\Doctor::create([
            'name' => 'Dr. Test',
            'email' => 'doctor@test.com',
            'password' => 'password',
            'specializations_id' => $specialization->id,
            'mobile_number' => 123456789,
            'license_number' => 'LIC123',
            'session_price' => 100.0,
            'availability_slots' => json_encode([]),
            'clinic_location' => json_encode([]),
        ]);
    }

    $paymentMethod = \App\Models\Payment_method::firstOrCreate([]);

    $booking = \App\Models\Booking::create([
        'user_id' => $user->id,
        'doctor_id' => $doctor->id,
        'booking_date' => now()->addDay()->toDateString(),
        'booking_time' => '10:00:00',
        'status' => \App\Enums\BookingStatus::Upcoming->value,
        'price' => 100.0,
        'payment_method_id' => $paymentMethod->id,
    ]);

    $user->notify(new \App\Notifications\UpcomingAppointmentNotification($booking));

    return response()->json([
        'message' => 'Notification sent to user ' . $user->id,
        'token' => $token,
        'user_id' => $user->id
    ]);
});
*/

/*
Route::get('/seed-notifications', function () {
    $user = \App\Models\User::first();
    if (!$user) {
        $user = \App\Models\User::factory()->create([
             'profile_photo' => 'default.jpg',
             'mobile_number' => '1234567890',
             'birth_date' => '1990-01-01',
             'location' => json_encode(['lat' => 0, 'lng' => 0]),
        ]);
    }

    // Create token
    $token = $user->createToken('test-token')->plainTextToken;

    // Create 5 dummy notifications
    for ($i = 1; $i <= 5; $i++) {
        \App\Models\Notification::create([
            'user_id' => $user->id,
            'title' => "Test Notification #$i",
            'body' => "This is a dummy notification number $i to test the system.",
            'is_read' => false
        ]);
    }

    return response()->json([
        'message' => '5 dummy notifications created for user ' . $user->id,
        'token' => $token,
        'unread_count' => $user->unreadNotifications()->count()
    ]);
});
*/
