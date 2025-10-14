<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ClientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas de Autenticación (Acceso público)
Route::post('v1/auth/login', [AuthController::class, 'login']);
Route::post('v1/auth/register', [AuthController::class, 'register']);
Route::get('v1/auth/google', [AuthController::class, 'googleRedirect']);
Route::get('v1/auth/google/callback', [AuthController::class, 'googleCallback']);

// Rutas protegidas por autenticación
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::apiResource('clients', ClientController::class);
});

