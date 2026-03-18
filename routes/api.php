<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\RouteController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VehicleController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\PaymentMethodController;
use App\Http\Controllers\API\PaymentController;
    // Auth
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/register', [AuthController::class, 'register']);

    // Protected routes (require Sanctum token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-all', [AuthController::class, 'logoutAll']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });
});

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{id}', [UserController::class, 'show']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });

    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/roles/{id}', [RoleController::class, 'show']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    Route::get('/customers',               [CustomerController::class, 'index']);
    Route::post('/customers',              [CustomerController::class, 'store']);
    Route::get('/customers/{id}',          [CustomerController::class, 'show']);
    Route::put('/customers/{id}',          [CustomerController::class, 'update']);
    Route::delete('/customers/{id}',       [CustomerController::class, 'destroy']);

    Route::get('/routes',        [RouteController::class, 'index']);
    Route::post('/routes',        [RouteController::class, 'store']);
    Route::get('/routes/{id}',    [RouteController::class, 'show']);
    Route::put('/routes/{id}',    [RouteController::class, 'update']);
    Route::delete('/routes/{id}', [RouteController::class, 'destroy']);

    Route::get('/vehicles', [VehicleController::class, 'index']);
    Route::post('/vehicles', [VehicleController::class, 'store']);
    Route::get('/vehicles/{id}', [VehicleController::class, 'show']);
    Route::put('/vehicles/{id}', [VehicleController::class, 'update']);
    Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);
    Route::get('/vehicles/{id}/seat-map', [VehicleController::class, 'seatMap']);

    Route::get('/schedules',          [ScheduleController::class, 'index']);
    Route::post('/schedules',         [ScheduleController::class, 'store']);
    Route::get('/schedules/{id}',     [ScheduleController::class, 'show']);
    Route::put('/schedules/{id}',     [ScheduleController::class, 'update']);
    Route::delete('/schedules/{id}',  [ScheduleController::class, 'destroy']);

        // ── PAYMENT METHOD CRUD ──────────────
    Route::get('/payment-methods',          [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods',         [PaymentMethodController::class, 'store']);
    Route::get('/payment-methods/{id}',     [PaymentMethodController::class, 'show']);
    Route::put('/payment-methods/{id}',     [PaymentMethodController::class, 'update']);
    Route::delete('/payment-methods/{id}',  [PaymentMethodController::class, 'destroy']);
    Route::post('/payment-methods/{id}/toggle', [PaymentMethodController::class, 'toggle']);

    Route::get('/payments/methods',                [PaymentController::class, 'methods']);
    Route::post('/payments/booking/{id}/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/{id}/process',          [PaymentController::class, 'process']);
    Route::post('/payments/{id}/refund',           [PaymentController::class, 'refund']);
    Route::get('/payments/booking/{id}/history',   [PaymentController::class, 'history']);