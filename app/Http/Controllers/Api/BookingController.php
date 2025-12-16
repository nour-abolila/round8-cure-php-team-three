<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    protected $notificationService;

    public function __construct()
    {
        $this->notificationService = new NotificationService();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'booking_date' => 'required|date',
            'booking_time' => 'required',
            'payment_method_id' => 'required|exists:payment_methods,id'
        ]);

       $booking = Booking::create([
            'user_id' => Auth::id(),
            'doctor_id' => $validated['doctor_id'],
            'booking_date' => $validated['booking_date'],
            'booking_time' => $validated['booking_time'],
            'status' => 'Upcoming',
            'price' => $request->price,
            'payment_method_id' => $validated['payment_method_id']
        ]);

        $this->notificationService->sendToPatient(
            Auth::user(),
            'Appointment Confirmed',
            "You have successfully booked your appointment with Dr. {$booking->doctor->name}"
        );

        $this->notificationService->sendNewBookingNotification(
            $booking->doctor,
            $booking
        );

        return response()->json([
            'message' => 'Booking created successfully',
            'booking' => $booking
        ], 201);
    }

    public function cancel($id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        $oldStatus = $booking->status;
        $booking->update(['status' => 'Cancelled']);

        $this->notificationService->sendBookingCancelledNotification(
            Auth::user(),
            $booking
        );

        $this->notificationService->sendToDoctor(
            $booking->doctor,
            'Appointment Cancelled',
            "Patient {$booking->user->name} cancelled their appointment"
        );

        return response()->json([
            'message' => 'Booking cancelled successfully'
        ]);
    }

    public function reschedule(Request $request, $id)
    {
        $booking = Booking::where('user_id', Auth::id())->findOrFail($id);

        $oldData = [
            'old_date' => $booking->booking_date,
            'old_time' => $booking->booking_time
        ];

        $booking->update([
            'booking_date' => $request->booking_date,
            'booking_time' => $request->booking_time
        ]);

       $this->notificationService->sendBookingRescheduledNotification(
            Auth::user(),
            $booking,
            $oldData
        );

        $this->notificationService->sendToDoctor(
            $booking->doctor,
            'Appointment Rescheduled',
            "Patient {$booking->user->name} rescheduled their appointment"
        );

        return response()->json([
            'message' => 'Booking rescheduled successfully'
        ]);
    }
}
