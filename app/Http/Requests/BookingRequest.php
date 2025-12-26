<?php

namespace App\Http\Requests;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'booking_time' => ['required', 'date_format:H:i'],
            'price' => ['required', 'numeric', 'min:0'],
            'payment_method_id' => ['required', 'integer', 'exists:payment_methods,id'],
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



//     public function messages()
//     {
//         return [
//             'doctor_id.required' => 'حقل الطبيب مطلوب.',
//             'doctor_id.integer' => 'حقل الطبيب يجب أن يكون رقماً صحيحاً.',
//             'doctor_id.exists' => 'الطبيب المحدد غير موجود.',
//             'booking_date.required' => 'حقل تاريخ الحجز مطلوب.',
//             'booking_date.date_format' => 'حقل تاريخ الحجز يجب أن يكون بالتنسيق YYYY-MM-DD.',
//             'booking_date.after_or_equal' => 'حقل تاريخ الحجز يجب أن يكون اليوم أو بعده.',
//             'booking_time.required' => 'حقل وقت الحجز مطلوب.',
//             'booking_time.date_format' => 'حقل وقت الحجز يجب أن يكون بالتنسيق HH:MM.',
//             'price.required' => 'حقل السعر مطلوب.',
//             'price.numeric' => 'حقل السعر يجب أن يكون رقمياً.',
//             'price.min' => 'حقل السعر يجب أن يكون على الأقل :min.',
//             'payment_method_id.required' => 'حقل طريقة الدفع مطلوب.',
//             'payment_method_id.integer' => 'حقل طريقة الدفع يجب أن يكون رقماً صحيحاً.',
//             'payment_method_id.exists' => 'طريقة الدفع المحددة غير موجودة.',
//         ];
//     }
}
