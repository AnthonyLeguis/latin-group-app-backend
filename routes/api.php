<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas de Autenticación (Acceso público)
Route::post('v1/auth/login', [AuthController::class, 'login']);
Route::post('v1/auth/register', [AuthController::class, 'register']);
Route::get('v1/auth/google', [AuthController::class, 'googleRedirect']);
Route::get('v1/auth/google/callback', [AuthController::class, 'googleCallback']);

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Gestión de usuarios (solo admin y agent)
    Route::apiResource('users', UserController::class)->except(['edit', 'create']);

    // Gestión de clientes
    Route::apiResource('clients', ClientController::class);
});

