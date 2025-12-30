<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DoctorFilterController extends Controller
{
    public function filter(Request $request)
    {
        $query = Doctor::with(['user', 'specialization']);

        // 1. Filter by Availability (Today/Tomorrow)
        if ($request->has('availability')) {
            $availability = $request->input('availability');

            if ($availability === 'today') {
                $date = Carbon::today()->format('Y-m-d');
                $query->whereJsonContains('availability_slots', [['date' => $date]]);
            } elseif ($availability === 'tomorrow') {
                $date = Carbon::tomorrow()->format('Y-m-d');
                $query->whereJsonContains('availability_slots', [['date' => $date]]);
            }
        }

        // 2. Sort by Price
        if ($request->has('sort_price')) {
            $sortDirection = $request->input('sort_price') === 'desc' ? 'desc' : 'asc';
            $query->orderBy('session_price', $sortDirection);
        }

        $doctors = $query->paginate(10);

        // Transform the collection to include calculated fields
        $doctors->getCollection()->transform(function ($doctor) {
            return $this->transformDoctor($doctor);
        });

        return response()->json([
            'success' => true,
            'data' => $doctors
        ]);
    }

 public function search(Request $request)
{
    $request->validate([
        'search' => 'required|string|min:2',
    ]);

    $searchQuery = $request->input('search');

    $query = Doctor::with(['user', 'specialization']);

    $query->where(function ($q) use ($searchQuery) {
        $q->whereHas('user', function ($subQ) use ($searchQuery) {
            $subQ->where('name', 'like', '%' . $searchQuery . '%');
        })->orWhereHas('specialization', function ($subQ) use ($searchQuery) {
            $subQ->where('name', 'like', '%' . $searchQuery . '%');
        });
    });

    $doctors = $query->paginate(10);

    $doctors->getCollection()->transform(function ($doctor) {
        return $this->transformDoctor($doctor);
    });

    return response()->json([
        'success' => true,
        'data' => $doctors
    ]);
}

    private function transformDoctor($doctor)
    {
        return [
            'id' => $doctor->id,
            'name' => $doctor->user->name,
            'profile_photo' => $doctor->user->profile_photo,
            'specialization' => $doctor->specialization->name ?? null,
            'session_price' => $doctor->session_price,
            'clinic_location' => $doctor->clinic_location,
            'availability_slots' => $doctor->availability_slots,
            'average_rating' => round($doctor->averageRating(), 1),
            'reviews_count' => $doctor->reviewsCount(),
        ];
    }
}
