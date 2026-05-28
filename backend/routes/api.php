<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\GuestController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\HousekeepingTaskController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RateOverrideController;

// PUBLIC ROUTES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// PROTECTED ROUTES
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // ROOM CRUD
    Route::apiResource('rooms', RoomController::class);

    //GUEST CRUD
Route::apiResource('guests', GuestController::class);

   //Booking CRUD
Route::apiResource('bookings', BookingController::class);

   //Dashboard
Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

     //ROOM AVAILABILITY
Route::get('/available-rooms', [BookingController::class, 'availableRooms']);

     //REPORTS
 Route::get('/reports/revenue', [ReportController::class, 'revenue']);

    Route::get('/reports/bookings', [ReportController::class, 'bookings']);

    Route::get('/reports/occupancy', [ReportController::class, 'occupancy']);

    Route::get('/reports/monthly-revenue', [ReportController::class, 'monthlyRevenue']);

    Route::get('/reports/monthly-bookings', [ReportController::class, 'monthlyBookings']);

    //CHECK-IN/OUT
Route::post('/bookings/{id}/check-in', [BookingController::class, 'checkIn']);

Route::post('/bookings/{id}/check-out', [BookingController::class, 'checkOut']);

    //HOUSEKEEPING
Route::apiResource('housekeeping-tasks', HousekeepingTaskController::class);

    //PAYMENTS
Route::apiResource('payments', PaymentController::class);

   //RateOverrides
Route::apiResource('rate-overrides', RateOverrideController::class);
});

    //BOOKING_CALENDAR
Route::get('/booking-calendar', [BookingController::class, 'calendar']);


    //INVOICES
Route::get('/bookings/{id}/invoice', [BookingController::class, 'invoice']);
    
