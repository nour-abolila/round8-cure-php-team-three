<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],// for function withValidator
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id']
        ];
    }

    // للتحقق من الوقت المتاح للطبيب
   public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $doctorId = $this->input('doctor_id');
            $bookingDate = $this->input('booking_date');
            $bookingTime = trim($this->input('booking_time')); // إزالة أي فراغات

            if (!$doctorId || !$bookingDate || !$bookingTime) {
                return;
            }

            // تحقق من عدم وجود حجز متضارب
            $conflictExists = Booking::query()
                ->where('doctor_id', $doctorId)
                ->whereDate('booking_date', $bookingDate)
                ->whereTime('booking_time', $bookingTime)
                ->where('status', '!=', BookingStatus::Cancelled->value)
                ->exists();

            if ($conflictExists) {
                $validator->errors()->add('booking_time', 'هذا الوقت محجوز مسبقاً لهذا الطبيب.');
                return;
            }

            $doctor = Doctor::find($doctorId);
            if (!$doctor) {
                return;
            }

            try {
                $bookingTimeMinutes = Carbon::parse($bookingTime)->hour * 60 + Carbon::parse($bookingTime)->minute;
            } catch (\Throwable) {
                $validator->errors()->add('booking_time', 'تنسيق الوقت غير صحيح.');
                return;
            }

            $slots = is_array($doctor->availability_slots) ? $doctor->availability_slots : [];
            $valid = false;

            foreach ($slots as $slot) {
                if (!is_array($slot)) continue;

                // مقارنة التاريخ مباشرة (date) وليس يوم الأسبوع (day)
                $slotDate = $slot['date'] ?? '';
                if ($slotDate !== $bookingDate) {
                    continue;
                }

                try {
                    $fromMinutes = Carbon::parse($slot['from'])->hour * 60 + Carbon::parse($slot['from'])->minute;
                    $toMinutes   = Carbon::parse($slot['to'])->hour * 60 + Carbon::parse($slot['to'])->minute;
                } catch (\Throwable) {
                    continue;
                }

                // تحقق إذا الوقت داخل الـ slot
                if ($bookingTimeMinutes >= $fromMinutes && $bookingTimeMinutes < $toMinutes) {
                    $valid = true;
                    break;
                }
            }

            if (!$valid) {
                $validator->errors()->add('booking_time', 'وقت الحجز يجب أن يكون ضمن مواعيد الطبيب المتوفرة في هذا اليوم.');
            }
        });
    }
}
