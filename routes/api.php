<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\RouteController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;

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


    // Route::get('/users', [UserController::class, 'index']);       // List users
    // Route::post('/users', [UserController::class, 'store']);      // Create user
    // Route::get('/users/{id}', [UserController::class, 'show']);   // Show user
    // Route::put('/users/{id}', [UserController::class, 'update']); // Update user
    // Route::delete('/users/{id}', [UserController::class, 'destroy']); // Delete user

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