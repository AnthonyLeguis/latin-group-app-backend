<?php

use App\Http\Controllers\Api\V1\ApplicationFormController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Ruta pública para formulario de contacto
Route::post('v1/contact', [\App\Http\Controllers\Api\V1\ContactController::class, 'submit'])
    ->middleware('throttle:5,1');

// Rutas de Autenticación (Acceso público)
Route::post('v1/auth/login', [AuthController::class, 'login']);
Route::get('v1/auth/google', [AuthController::class, 'googleRedirect']);
Route::get('v1/auth/google/callback', [AuthController::class, 'googleCallback']);

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Registro de usuarios (requiere autenticación)
    Route::post('auth/register', [AuthController::class, 'register']);

    // Gestión de usuarios (solo admin y agent)
    // Admin: CRUD completo para admin, agent y client
    // Agent: CRUD solo para clients que él creó
    Route::get('users/stats', [UserController::class, 'stats']); // Estadísticas generales
    Route::get('users/agents-report', [UserController::class, 'agentsReport']); // Reporte de agentes con clients
    Route::get('users/pending-forms', [UserController::class, 'pendingForms']); // Planillas pendientes (solo admin)
    Route::apiResource('users', UserController::class)->except(['edit', 'create']);

    // Gestión de planillas de aplicación
    Route::apiResource('application-forms', ApplicationFormController::class);
    Route::post('application-forms/{form}/confirm', [ApplicationFormController::class, 'confirm']);
    Route::post('application-forms/{form}/status', [ApplicationFormController::class, 'updateStatus']);
    Route::post('application-forms/{form}/documents', [ApplicationFormController::class, 'uploadDocument']);
    Route::delete('application-forms/{form}/documents/{documentId}', [ApplicationFormController::class, 'deleteDocument']);
});

