<?php

use App\Http\Controllers\BookingController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\PayPalController;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



    Route::post('/webhook/stripe', [PaymentWebhookController::class, 'handle']);

    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        Route::post('bookings',[BookingController::class,'store']);
    });




