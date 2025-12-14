<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LayananApiController;
use App\Http\Controllers\Api\ArtikelController;
use App\Http\Controllers\Api\AuthController;

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

// Layanan API Routes
Route::prefix('v1')->group(function () {
    Route::get('/layanan', [LayananApiController::class, 'index']);
    Route::get('/layanan/{id}', [LayananApiController::class, 'show']);
});

Route::get('/artikel', [ArtikelController::class, 'index']); 