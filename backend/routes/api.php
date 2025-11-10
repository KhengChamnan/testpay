<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Payment routes
Route::prefix('payment')->group(function () {
    // Create payment request
    Route::post('/create', [PaymentController::class, 'create']);
    
    // Check payment status
    Route::get('/status', [PaymentController::class, 'status']);
});

// ABA PayWay callback routes (MUST be publicly accessible - no auth middleware)
Route::prefix('payway')->group(function () {
    Route::post('/return', [PaymentController::class, 'paywayReturn']);
    Route::post('/cancel', [PaymentController::class, 'paywayCancel']);
});
