<?php

use App\Http\Controllers\Api\V1\ApplicationFormController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ConfirmationController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


// Ruta pública para formulario de contacto
Route::post('v1/contact', [\App\Http\Controllers\Api\V1\ContactController::class, 'submit'])
    ->middleware('throttle:5,1');

// Rutas públicas para confirmación de planillas (sin autenticación)
Route::prefix('v1/confirm')->group(function () {
    Route::get('{token}', [ConfirmationController::class, 'show']); // Ver datos de la planilla
    Route::post('{token}/accept', [ConfirmationController::class, 'accept']); // Confirmar planilla
});

// Rutas de Autenticación (Acceso público)
Route::post('v1/auth/login', [AuthController::class, 'login']);
Route::get('v1/auth/google', [AuthController::class, 'googleRedirect']);
Route::get('v1/auth/google/callback', [AuthController::class, 'googleCallback']);

// Rutas de recuperación de contraseña (públicas)
Route::post('v1/auth/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('v1/auth/reset-password', [AuthController::class, 'resetPassword']);

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Registro de usuarios (requiere autenticación)
    Route::post('auth/register', [AuthController::class, 'register']);
    
    // Cambiar contraseña (requiere autenticación)
    Route::post('auth/change-password', [AuthController::class, 'changePassword']);

    // Gestión de usuarios (solo admin y agent)
    // Admin: CRUD completo para admin, agent y client
    // Agent: CRUD solo para clients que él creó
    Route::get('users/stats', [UserController::class, 'stats']); // Estadísticas generales
    Route::get('users/agents-report', [UserController::class, 'agentsReport']); // Reporte de agentes con clients
    Route::get('users/pending-forms', [UserController::class, 'pendingForms']); // Planillas pendientes (solo admin)
    Route::apiResource('users', UserController::class)->except(['edit', 'create']);

    // Gestión de planillas de aplicación
    // IMPORTANTE: Rutas específicas ANTES del apiResource para evitar conflictos
    Route::get('application-forms/clients-with-forms', [ApplicationFormController::class, 'getClientsWithForms']);
    Route::post('application-forms/{form}/confirm', [ApplicationFormController::class, 'confirm']);
    Route::post('application-forms/{form}/status', [ApplicationFormController::class, 'updateStatus']);
    Route::post('application-forms/{form}/approve-changes', [ApplicationFormController::class, 'approvePendingChanges']);
    Route::post('application-forms/{form}/reject-changes', [ApplicationFormController::class, 'rejectPendingChanges']);
    Route::post('application-forms/{form}/renew-token', [ApplicationFormController::class, 'renewToken']);
    Route::post('application-forms/{form}/documents', [ApplicationFormController::class, 'uploadDocument']);
    Route::get('application-forms/{form}/documents/{documentId}/view', [ApplicationFormController::class, 'viewDocument']);
    Route::get('application-forms/{form}/documents/{documentId}/download', [ApplicationFormController::class, 'downloadDocument']);
    Route::delete('application-forms/{form}/documents/{documentId}', [ApplicationFormController::class, 'deleteDocument']);
    Route::get('forms/{id}/download-pdf', [ConfirmationController::class, 'downloadPdf']);
    Route::get('forms/{id}/view-pdf', [ConfirmationController::class, 'viewPdf']);
    
    // Rutas RESTful estándar
    Route::apiResource('application-forms', ApplicationFormController::class);
});

