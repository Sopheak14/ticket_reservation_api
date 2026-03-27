<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BookingController;
use App\Http\Controllers\API\CustomerController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\PaymentController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\RouteController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Ticket Reservation System
|--------------------------------------------------------------------------
|
| Roles:
|   Admin                            → គ្រប់យ៉ាង
|   Admin, Manager                   → Dashboard, Routes, Vehicles, Schedules
|   Admin, Manager, Cashier          → Payments
|   Admin, Manager, Cashier, Agent   → Customers, Bookings
|   Driver                           → View Schedule, Validate QR
|   Customer                         → My Bookings, My Notifications
|
*/


// ════════════════════════════════════════════════════════════════════════
// 🔓 PUBLIC — គ្មាន Login
// ════════════════════════════════════════════════════════════════════════

// Auth
Route::prefix('auth')->group(function () {
    Route::post('/login',    [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});

// Public Search
Route::get('/schedules/search',      [ScheduleController::class, 'search']);
Route::get('/routes/list',           [RouteController::class, 'index']);

// QR & Booking Lookup
Route::get('/bookings/find/{code}',  [BookingController::class, 'findByCode']);
Route::post('/bookings/validate-qr', [BookingController::class, 'validateQR']);

// Payment Webhook
Route::post('/payments/aba-webhook', [PaymentController::class, 'abaWebhook']);


// ════════════════════════════════════════════════════════════════════════
// 🔐 PROTECTED — ត្រូវ Login (auth:sanctum)
// ════════════════════════════════════════════════════════════════════════

Route::middleware('auth:sanctum')->group(function () {

    // ── AUTH (គ្រប់ Role) ─────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::get('/me',               [AuthController::class, 'me']);
        Route::post('/logout',          [AuthController::class, 'logout']);
        Route::post('/logout-all',      [AuthController::class, 'logoutAll']);
        Route::post('/change-password', [AuthController::class, 'changePassword']);
    });


    // ════════════════════════════════════════════════════════════════════
    // 👑 ADMIN ONLY
    // ════════════════════════════════════════════════════════════════════

    Route::middleware('role:Admin')->group(function () {

        // ── ROLE CRUD ────────────────────────────────────────────────
        Route::get('/roles',          [RoleController::class, 'index']);
        Route::post('/roles',         [RoleController::class, 'store']);
        Route::get('/roles/{id}',     [RoleController::class, 'show']);
        Route::put('/roles/{id}',     [RoleController::class, 'update']);
        Route::delete('/roles/{id}',  [RoleController::class, 'destroy']);

        // ── USER CRUD ────────────────────────────────────────────────
        Route::get('/users',          [UserController::class, 'index']);
        Route::post('/users',         [UserController::class, 'store']);
        Route::get('/users/{id}',     [UserController::class, 'show']);
        Route::put('/users/{id}',     [UserController::class, 'update']);
        Route::delete('/users/{id}',  [UserController::class, 'destroy']);

        // ── VEHICLE DELETE (Admin Only) ──────────────────────────────
        Route::delete('/vehicles/{id}', [VehicleController::class, 'destroy']);

        // ── NOTIFICATION TEMPLATES ───────────────────────────────────
        Route::get('/notifications/templates',          [NotificationController::class, 'templates']);
        Route::post('/notifications/templates',         [NotificationController::class, 'storeTemplate']);
        Route::put('/notifications/templates/{id}',     [NotificationController::class, 'updateTemplate']);
        Route::delete('/notifications/templates/{id}',  [NotificationController::class, 'destroyTemplate']);
    });


    // ════════════════════════════════════════════════════════════════════
    // 📊 ADMIN + MANAGER
    // ════════════════════════════════════════════════════════════════════

    Route::middleware('role:Admin,Manager')->group(function () {

        // ── DASHBOARD ────────────────────────────────────────────────
        Route::get('/dashboard',                  [DashboardController::class, 'index']);
        Route::get('/dashboard/stats',            [DashboardController::class, 'stats']);
        Route::get('/dashboard/sales-report',     [DashboardController::class, 'salesReport']);
        Route::get('/dashboard/best-customers',   [DashboardController::class, 'bestCustomers']);
        Route::get('/dashboard/revenue-by-route', [DashboardController::class, 'revenueByRoute']);
        Route::get('/dashboard/trip-occupancy',   [DashboardController::class, 'tripOccupancy']);

        // ── ROUTE CRUD ───────────────────────────────────────────────
        Route::post('/routes',        [RouteController::class, 'store']);
        Route::get('/routes/{id}',    [RouteController::class, 'show']);
        Route::put('/routes/{id}',    [RouteController::class, 'update']);
        Route::delete('/routes/{id}', [RouteController::class, 'destroy']);

        // ── VEHICLE CRUD (Admin + Manager) ───────────────────────────
        Route::get('/vehicles',              [VehicleController::class, 'index']);
        Route::post('/vehicles',             [VehicleController::class, 'store']);
        Route::get('/vehicles/{id}',         [VehicleController::class, 'show']);
        Route::put('/vehicles/{id}',         [VehicleController::class, 'update']);
        // DELETE → Admin Only (ខាងលើ)

        // ── SCHEDULE CRUD ────────────────────────────────────────────
        Route::get('/schedules',          [ScheduleController::class, 'index']);
        Route::post('/schedules',         [ScheduleController::class, 'store']);
        Route::get('/schedules/{id}',     [ScheduleController::class, 'show']);
        Route::put('/schedules/{id}',     [ScheduleController::class, 'update']);
        Route::delete('/schedules/{id}',  [ScheduleController::class, 'destroy']);

        // ── NOTIFICATIONS ────────────────────────────────────────────
        Route::post('/notifications',      [NotificationController::class, 'send']);
        Route::get('/notifications/logs',  [NotificationController::class, 'logs']);
    });


    // ════════════════════════════════════════════════════════════════════
    // 💰 ADMIN + MANAGER + CASHIER
    // ════════════════════════════════════════════════════════════════════

    Route::middleware('role:Admin,Manager,Cashier')->group(function () {

        // ── PAYMENT ──────────────────────────────────────────────────
        Route::get('/payments/methods',                [PaymentController::class, 'methods']);
        Route::post('/payments/booking/{id}/initiate', [PaymentController::class, 'initiate']);
        Route::post('/payments/{id}/process',          [PaymentController::class, 'process']);
        Route::post('/payments/{id}/refund',           [PaymentController::class, 'refund']);
        Route::get('/payments/booking/{id}/history',   [PaymentController::class, 'history']);
    });


    // ════════════════════════════════════════════════════════════════════
    // 🎫 ADMIN + MANAGER + CASHIER + AGENT
    // ════════════════════════════════════════════════════════════════════

    Route::middleware('role:Admin,Manager,Cashier,Agent')->group(function () {

        // ── VEHICLE SEAT MAP (Staff ទាំងអស់) ─────────────────────────
        Route::get('/vehicles/{id}/seat-map', [VehicleController::class, 'seatMap']);

        // ── CUSTOMER CRUD ────────────────────────────────────────────
        Route::get('/customers',               [CustomerController::class, 'index']);
        Route::post('/customers',              [CustomerController::class, 'store']);
        Route::get('/customers/{id}',          [CustomerController::class, 'show']);
        Route::put('/customers/{id}',          [CustomerController::class, 'update']);
        Route::get('/customers/{id}/bookings', [CustomerController::class, 'bookings']);

        // ── BOOKING CRUD ─────────────────────────────────────────────
        Route::get('/bookings',                      [BookingController::class, 'index']);
        Route::get('/bookings/search',               [BookingController::class, 'search']);
        Route::get('/bookings/{id}',                 [BookingController::class, 'show']);
        Route::post('/bookings/initiate',            [BookingController::class, 'initiate']);
        Route::post('/bookings/{id}/select-seats',   [BookingController::class, 'selectSeats']);
        Route::post('/bookings/{id}/confirm',        [BookingController::class, 'confirm']);
        Route::post('/bookings/{id}/cancel',         [BookingController::class, 'cancel']);
        Route::get('/bookings/{id}/download-ticket', [BookingController::class, 'downloadTicket']);
    });


    // ════════════════════════════════════════════════════════════════════
    // 🚌 DRIVER ONLY
    // ════════════════════════════════════════════════════════════════════

    Route::middleware('role:Driver')->group(function () {
        Route::get('/schedules/{id}',        [ScheduleController::class, 'show']);
        Route::post('/bookings/validate-qr', [BookingController::class, 'validateQR']);
    });


    // ════════════════════════════════════════════════════════════════════
    // 👤 CUSTOMER ONLY
    // ════════════════════════════════════════════════════════════════════

    Route::middleware('role:Customer')->group(function () {

        // ── MY BOOKINGS ──────────────────────────────────────────────
        Route::get('/my/bookings',                       [BookingController::class, 'myBookings']);
        Route::get('/my/bookings/{id}',                  [BookingController::class, 'show']);
        Route::post('/my/bookings/initiate',             [BookingController::class, 'initiate']);
        Route::post('/my/bookings/{id}/select-seats',    [BookingController::class, 'selectSeats']);
        Route::post('/my/bookings/{id}/cancel',          [BookingController::class, 'cancel']);
        Route::get('/my/bookings/{id}/download-ticket',  [BookingController::class, 'downloadTicket']);

        // ── MY NOTIFICATIONS ─────────────────────────────────────────
        Route::get('/my/notifications',                  [NotificationController::class, 'index']);
        Route::post('/my/notifications/{id}/mark-read',  [NotificationController::class, 'markRead']);
        Route::post('/my/notifications/mark-all-read',   [NotificationController::class, 'markAllRead']);

        // ── PAYMENT (Customer) ───────────────────────────────────────
        Route::get('/payments/methods',                  [PaymentController::class, 'methods']);
        Route::post('/payments/booking/{id}/initiate',   [PaymentController::class, 'initiate']);
        Route::post('/payments/{id}/process',            [PaymentController::class, 'process']);
    });

});