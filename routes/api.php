<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ScheduleController;    // <-- new
use App\Http\Controllers\Api\ExceptionController;   // <-- new

// Public routes
Route::post('/contact', [ContactController::class, 'send']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::match(['PUT', 'POST'], '/auth/profile', [AuthController::class, 'updateProfile']);
    Route::get('/auth/my-activity', [AuthController::class, 'getMyActivityLogs']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread/count', [NotificationController::class, 'unreadCount']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

    // Appointments
    Route::get('/appointments', [AppointmentController::class, 'index']);
    Route::get('/appointments/services', [AppointmentController::class, 'services']);
    // IMPORTANT: Define specific routes BEFORE parameterized {id} routes
    Route::get('/appointments/available-slots', [AppointmentController::class, 'availableSlots']);
    Route::post('/appointments', [AppointmentController::class, 'store']);
    Route::get('/appointments/{id}', [AppointmentController::class, 'show']);
    Route::put('/appointments/{id}', [AppointmentController::class, 'update']);
    Route::post('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);

    // Projects
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/projects/{id}', [ProjectController::class, 'show']);
    Route::put('/projects/{id}', [ProjectController::class, 'update']);
    Route::post('/projects/{id}/images', [ProjectController::class, 'addImages']);
    Route::delete('/projects/images/{id}', [ProjectController::class, 'deleteImage']);
    Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);

    // Admin-only routes
    Route::get('/auth/pending-clients', [ClientController::class, 'getPendingClients']);
    Route::get('/auth/clients', [ClientController::class, 'getAllClients']);
    Route::post('/auth/approve-client/{id}', [ClientController::class, 'approveClient']);
    Route::post('/auth/reject-client/{id}', [ClientController::class, 'rejectClient']);
    Route::put('/auth/clients/{id}', [ClientController::class, 'editClient']);
    Route::delete('/auth/clients/{id}', [ClientController::class, 'deleteClient']);
    Route::get('/auth/activity-logs', [ClientController::class, 'getActivityLogs']);
    Route::get('/auth/admins', [ClientController::class, 'getAdmins']);

    // NEW: Schedule & Exception management (admin only)

    Route::apiResource('schedules', ScheduleController::class)->except(['create', 'edit']);
    Route::apiResource('exceptions', ExceptionController::class)->except(['create', 'edit']);

});