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
    Route::get('items', [ItemController::class, 'index']);
    Route::get('items/{item}', [ItemController::class, 'show']);
    Route::post('items/{item}/purchase', [ItemController::class, 'purchase'])->middleware('role:shop_owner,customer');

    Route::middleware('role:shop_owner')->group(function (): void {
        Route::get('users', [AuthController::class, 'indexUsers']);
        Route::post('items', [ItemController::class, 'store']);
        Route::match(['put', 'patch'], 'items/{item}', [ItemController::class, 'update']);
        Route::delete('items/{item}', [ItemController::class, 'destroy']);
        Route::patch('users/{user}/role', [AuthController::class, 'updateRole']);
    });

    Route::middleware('role:shop_owner,shop_keeper')->group(function (): void {
        Route::apiResource('cash-transactions', CashTransactionController::class);
        Route::apiResource('stock-movements', StockMovementController::class)->only(['index', 'store', 'show']);
    });
});
