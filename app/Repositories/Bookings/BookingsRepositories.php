<?php

namespace App\Repositories\Bookings;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Carbon\Carbon;


class BookingsRepositories
{
    public function create(array $data): Booking
    {
        return \DB::transaction(function () use ($data) {

        Booking::where('doctor_id', $data['doctor_id'])
            ->whereDate('booking_date', $data['booking_date'])
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->lockForUpdate()
            ->get();

        $exists = Booking::where('doctor_id', $data['doctor_id'])
            ->whereDate('booking_date', $data['booking_date'])
            ->whereTime('booking_time', $data['booking_time'])
            ->where('status', '!=', BookingStatus::Cancelled->value)
            ->exists();

        if ($exists) {
            throw new \Exception('هذا الوقت محجوز مسبقاً لهذا الطبيب.');
        }

        $data['status'] = BookingStatus::Reserved;

        return Booking::create($data);

    }, 5);
    }

    public function update(Booking $booking, array $data): Booking
    {
        $booking->update($data);
        return $booking;
    }
    public function findById($id): ?Booking
    {
        return Booking::findOrFail($id);
    }

    public function delete(Booking $booking): void
    {
        $booking->delete();
    }
    public function getBookingsByUserId($userId)
    {
        return Booking::with(['doctor.user','doctor.specialization', 'payment.booking' => function($query) {
                    $query->where('status', '!=', 'pending');
                }, 'paymentMethod'])
                ->where('user_id', $userId)
                // ->whereHas('payment', function($query) {
                //     $query->where('status', '!=', 'pending');
                // })
                ->orderByDesc('created_at')
                ->get();
    }

    public function getBookings()
    {
        return Booking::with(['user', 'payment', 'paymentMethod', 'doctor.specialization', 'doctor.user'])
                ->orderByDesc('created_at')
                ->get();
    }

    public function search($query, $request)
    {
        if ($request->filled('q')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->q . '%');
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('booking_date', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('booking_date', '<=', $request->to);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        return $query;
    }

    public function deleteAppointment(Booking $booking): void
    {
        $doctor = $booking->doctor;
        if (!$doctor) {
            return;
        }

        $slots = is_array($doctor->availability_slots) ? $doctor->availability_slots : [];

        // التأكد من التنسيق الصحيح للتاريخ والوقت
        try {
            $bookingDate = is_string($booking->booking_date)
                ? $booking->booking_date
                : Carbon::parse($booking->booking_date)->format('Y-m-d');

            $bookingTime = is_string($booking->booking_time)
                ? $booking->booking_time
                : Carbon::parse($booking->booking_time)->format('H:i');
        } catch (\Throwable $e) {
            return; // إذا كان التنسيق غير صحيح، لا نحذف شيء
        }

        $slots = array_filter($slots, function($slot) use ($bookingDate, $bookingTime) {
            if (!is_array($slot)) {
                return true;
            }

            $slotDate = $slot['date'] ?? '';
            $slotFrom = $slot['from'] ?? '';

            // حذف الـ slot إذا التاريخ والوقت يطابقوا
            return !(
                $slotDate === $bookingDate &&
                $slotFrom === $bookingTime
            );
        });

        $doctor->availability_slots = array_values($slots);
        $doctor->save();
    }

    public function restoreAppointment(Booking $booking): void
    {
        $doctor = $booking->doctor;
        if (!$doctor) {
            return;
        }

        $slots = is_array($doctor->availability_slots) ? $doctor->availability_slots : [];

        // التأكد من التنسيق الصحيح للتاريخ والوقت
        try {
            $bookingDate = is_string($booking->booking_date)
                ? $booking->booking_date
                : Carbon::parse($booking->booking_date)->format('Y-m-d');

            $bookingTime = is_string($booking->booking_time)
                ? $booking->booking_time
                : Carbon::parse($booking->booking_time)->format('H:i');
        } catch (\Throwable $e) {
            return; // إذا كان التنسيق غير صحيح، لا نضيف شيء
        }

        // التحقق من عدم وجود الـ slot مسبقاً (لتجنب التكرار)
        $slotExists = false;
        foreach ($slots as $slot) {
            if (is_array($slot) &&
                ($slot['date'] ?? '') === $bookingDate &&
                ($slot['from'] ?? '') === $bookingTime) {
                $slotExists = true;
                break;
            }
        }

        // إضافة الـ slot فقط إذا لم يكن موجوداً
        if (!$slotExists) {
            $slots[] = [
                'date' => $bookingDate,
                'from' => $bookingTime,
                'to' => Carbon::parse($bookingTime)->addMinutes(30)->format('H:i'),
            ];

            $doctor->availability_slots = array_values($slots);
            $doctor->save();
        }
    }

}
