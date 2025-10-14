<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(Request $request)
    {
        $data = LoginData::from($request->all());

        try {
            $result = $this->authService->login($data);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 401);
        }
    }

    public function register(Request $request)
    {
        $data = RegisterUserData::from($request->all());

        // Si no hay usuario autenticado (registro público), solo permitir type 'client'
        if (!$request->user()) {
            if ($data->type !== 'client') {
                return response()->json([
                    'error' => 'Solo puedes registrarte como cliente. Contacta a un administrador para otros tipos de cuenta.'
                ], 403);
            }
        } else {
            // Si hay usuario autenticado, verificar permisos
            if (!$request->user()->can('createUserType', $data->type)) {
                return response()->json([
                    'error' => 'No tienes permisos para crear usuarios de este tipo'
                ], 403);
            }
        }

        try {
            $result = $this->authService->register($data);
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function googleRedirect()
    {
        return response()->json([
            'url' => $this->authService->getGoogleRedirectUrl()
        ]);
    }

    public function googleCallback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return response()->json(['error' => 'Código de autorización faltante'], 400);
        }

        try {
            $result = $this->authService->handleGoogleCallback($code);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
