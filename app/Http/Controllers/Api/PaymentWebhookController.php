<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Enums\BookingStatus;
use App\Models\Payment;
use App\Repositories\Bookings\BookingsRepositories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected BookingsRepositories $bookingsRepositories,
    )
    {}
    public function handle(Request $request)
    {
        Log::info('Stripe webhook received', $request->all());

        $event = $request->input('type');
        $intent = $request->input('data.object');

        if ($event === 'payment_intent.succeeded') {
            $payment = Payment::where('transaction_id', $intent['id'])->first();

            if ($payment) {
                $payment->update(['status' => 'success']);
                $payment->booking->update(['status' => BookingStatus::Upcoming]);
                
                // حذف الموعد من المواعيد المتاحة بعد نجاح الدفع
                $this->bookingsRepositories->deleteAppointment($payment->booking);
            }
            return response()->json(['ok' => true]);
        }

        if ($event === 'payment_intent.payment_failed') {
            $payment = Payment::where('transaction_id', $intent['id'])->first();

            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'response' => $intent
                ]);

                $payment->booking->update([
                    'status' => BookingStatus::Cancelled
                ]);

                $this->bookingsRepositories->restoreAppointment($payment->booking);

            }
            return response()->json(['ok' => false]);
        }

    }
}
