<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\RouteController;

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