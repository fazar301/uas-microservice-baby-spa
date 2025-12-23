<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LayananApiController;
use App\Http\Controllers\Api\ArtikelController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ReservationApiController;
use App\Http\Controllers\Api\BookingServiceController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected authentication routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // User profile routes
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'update']);
    Route::post('/logout', [AuthController::class, 'logout']);
    
    // User CRUD routes (Admin only)
    Route::get('/users', [AuthController::class, 'index']);
    Route::get('/users/{id}', [AuthController::class, 'show']);
    Route::delete('/users/{id}', [AuthController::class, 'destroy']);
});

// Reservation API Routes (Protected) - Service B
Route::middleware('auth:sanctum')->prefix('reservations')->name('reservations.')->group(function () {
    Route::get('/', [ReservationApiController::class, 'index']);
    Route::post('/', [ReservationApiController::class, 'store']);
    Route::get('/{id}', [ReservationApiController::class, 'show']);
    Route::put('/{id}', [ReservationApiController::class, 'update']);
    Route::delete('/{id}', [ReservationApiController::class, 'destroy']);
});

// Booking Service Routes (Protected) - Service C
// Service ini melakukan call ke User Service dan Reservation Service
Route::middleware('auth:sanctum')->prefix('bookings')->name('bookings.')->group(function () {
    Route::get('/', [BookingServiceController::class, 'index']);
    Route::post('/', [BookingServiceController::class, 'createBooking']);
    Route::get('/{id}', [BookingServiceController::class, 'getBooking']);
});

// Layanan API Routes
Route::prefix('v1')->group(function () {
    Route::get('/layanan', [LayananApiController::class, 'index']);
    Route::get('/layanan/{id}', [LayananApiController::class, 'show']);
});

Route::get('/artikel', [ArtikelController::class, 'index']); 