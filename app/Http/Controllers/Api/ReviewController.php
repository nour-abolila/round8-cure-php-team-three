<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    protected $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string'
        ]);

        $booking = Booking::where('id', $validated['booking_id'])
            ->where('user_id', Auth::id())
            ->where('status', 'Completed')
            ->firstOrFail();

        $review = Review::create([
            'booking_id' => $validated['booking_id'],
            'user_id' => Auth::id(),
            'doctor_id' => $booking->doctor_id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment']
        ]);

        $this->notificationService->sendNewReviewNotification(
            $booking->doctor,
            [
                'rating' => $review->rating,
                'patient_name' => Auth::user()->name
            ]
        );

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => $review
        ], 201);
    }
}
