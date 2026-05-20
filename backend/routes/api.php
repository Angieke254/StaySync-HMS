<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolioController;
use App\Http\Controllers\GuestController;
use App\Http\Controllers\HousekeepingController;
use App\Http\Controllers\RateController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomTypeController;
use App\Http\Controllers\SettingsController;

Route::get('/', function () {
    return response()->json([
        'message' => 'StaySync API running'
    ]);
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])
        ->middleware('role:admin,front_desk,manager');

    Route::get('/rooms/availability', [RoomController::class, 'availability'])
        ->middleware('role:admin,front_desk,manager');
    Route::patch('/rooms/{room}/status', [RoomController::class, 'updateStatus'])
        ->middleware('role:admin,front_desk,manager,housekeeping');
    Route::apiResource('rooms', RoomController::class)
        ->middleware('role:admin,front_desk,manager');
    Route::apiResource('room-types', RoomTypeController::class)
        ->middleware('role:admin,manager');

    Route::apiResource('guests', GuestController::class)
        ->middleware('role:admin,front_desk,manager');

    Route::get('/tape-chart', [BookingController::class, 'tapeChart'])
        ->middleware('role:admin,front_desk,manager');
    Route::patch('/bookings/{booking}/check-in', [BookingController::class, 'checkIn'])
        ->middleware('role:admin,front_desk,manager');
    Route::patch('/bookings/{booking}/check-out', [BookingController::class, 'checkOut'])
        ->middleware('role:admin,front_desk,manager');
    Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])
        ->middleware('role:admin,front_desk,manager');
    Route::patch('/bookings/{booking}/no-show', [BookingController::class, 'noShow'])
        ->middleware('role:admin,front_desk,manager');
    Route::apiResource('bookings', BookingController::class)
        ->middleware('role:admin,front_desk,manager');

    Route::get('/housekeeping/tasks', [HousekeepingController::class, 'index'])
        ->middleware('role:admin,manager,housekeeping');
    Route::post('/housekeeping/tasks', [HousekeepingController::class, 'store'])
        ->middleware('role:admin,manager,housekeeping');
    Route::patch('/housekeeping/tasks/{task}', [HousekeepingController::class, 'update'])
        ->middleware('role:admin,manager,housekeeping');
    Route::patch('/housekeeping/tasks/{task}/complete', [HousekeepingController::class, 'complete'])
        ->middleware('role:admin,manager,housekeeping');

    Route::get('/bookings/{booking}/folio', [FolioController::class, 'show'])
        ->middleware('role:admin,front_desk,manager,pos_staff');
    Route::post('/bookings/{booking}/charges', [FolioController::class, 'addCharge'])
        ->middleware('role:admin,front_desk,manager,pos_staff');
    Route::delete('/folio-charges/{charge}', [FolioController::class, 'voidCharge'])
        ->middleware('role:admin,manager');
    Route::get('/bookings/{booking}/payments', [FolioController::class, 'payments'])
        ->middleware('role:admin,front_desk,manager,pos_staff');
    Route::post('/bookings/{booking}/payments', [FolioController::class, 'addPayment'])
        ->middleware('role:admin,front_desk,manager,pos_staff');
    Route::get('/bookings/{booking}/invoice', [FolioController::class, 'invoice'])
        ->middleware('role:admin,front_desk,manager');

    Route::get('/rates', [RateController::class, 'index'])
        ->middleware('role:admin,manager');
    Route::post('/rates/overrides', [RateController::class, 'storeOverride'])
        ->middleware('role:admin,manager');
    Route::delete('/rate-overrides/{rateOverride}', [RateController::class, 'destroy'])
        ->middleware('role:admin,manager');

    Route::get('/reports', [ReportController::class, 'index'])
        ->middleware('role:admin,manager');
    Route::get('/reports/occupancy', [ReportController::class, 'occupancy'])
        ->middleware('role:admin,manager');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])
        ->middleware('role:admin,manager');
    Route::get('/reports/room-type-performance', [ReportController::class, 'roomTypePerformance'])
        ->middleware('role:admin,manager');
    Route::get('/reports/guest-statistics', [ReportController::class, 'guestStatistics'])
        ->middleware('role:admin,manager');

    Route::get('/settings', [SettingsController::class, 'index'])
        ->middleware('role:admin,manager');
    Route::put('/settings', [SettingsController::class, 'update'])
        ->middleware('role:admin,manager');
});
