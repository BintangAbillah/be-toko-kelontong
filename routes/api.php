<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CashTransactionController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\StockMovementController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function (): void {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('jwt')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('jwt')->group(function (): void {
    Route::apiResource('items', ItemController::class);
    Route::apiResource('cash-transactions', CashTransactionController::class);
    Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'store', 'show']);
});
