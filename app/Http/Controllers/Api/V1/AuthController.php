<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterUserData;
use App\Data\Auth\ForgotPasswordData;
use App\Data\Auth\ResetPasswordData;
use App\Data\Auth\ChangePasswordData;
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
        // Verificar que hay un usuario autenticado
        if (!$request->user()) {
            return response()->json([
                'error' => 'Registro público no permitido. Contacta a un administrador.'
            ], 403);
        }

        $data = RegisterUserData::from($request->all());

        // Verificar permisos del usuario autenticado
        if (!$request->user()->can('createUserType', $data->type)) {
            return response()->json([
                'error' => 'No tienes permisos para crear usuarios de este tipo'
            ], 403);
        }

        try {
            $result = $this->authService->register($data, $request->user()->id);
            return response()->json($result, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function googleRedirect()
    {
        return $this->authService->redirectToGoogle();
    }

    public function googleCallback()
    {
        \Log::info('📞 Google callback recibido');
        
        try {
            $result = $this->authService->handleGoogleCallback();

            \Log::info('✅ Callback procesado exitosamente:', [
                'user_id' => $result['user']['id'],
                'user_email' => $result['user']['email'],
                'user_type' => $result['user']['type']
            ]);

            // Redirigir al frontend con el token en la URL
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:4200') . '/dashboard';

            $queryParams = http_build_query([
                'token' => $result['token'],
                'user_type' => $result['user']['type'],
                'user_id' => $result['user']['id'],
                'user_name' => $result['user']['name'],
                'user_email' => $result['user']['email']
            ]);

            $redirectUrl = $frontendUrl . '?' . $queryParams;
            
            \Log::info('🔀 Redirigiendo a:', ['url' => $redirectUrl]);

            return redirect($redirectUrl);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            \Log::error('❌ Error en googleCallback:', [
                'message' => $errorMessage,
                'trace' => $e->getTraceAsString()
            ]);

            // Redirigir a página de error según el tipo de error
            $frontendUrl = env('FRONTEND_URL', 'http://localhost:4200') . '/auth/access-denied';

            $queryParams = http_build_query([
                'error' => 'access_denied',
                'message' => $errorMessage
            ]);

            return redirect($frontendUrl . '?' . $queryParams);
        }
    }

    /**
     * Solicitar recuperación de contraseña
     */
    public function forgotPassword(Request $request)
    {
        $data = ForgotPasswordData::from($request->all());

        try {
            $result = $this->authService->forgotPassword($data);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Resetear la contraseña con el token
     */
    public function resetPassword(Request $request)
    {
        $data = ResetPasswordData::from($request->all());

        try {
            $result = $this->authService->resetPassword($data);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Cambiar contraseña del usuario autenticado
     */
    public function changePassword(Request $request)
    {
        // Verificar que hay un usuario autenticado
        if (!$request->user()) {
            return response()->json([
                'error' => 'No autenticado'
            ], 401);
        }

        $data = ChangePasswordData::from($request->all());

        try {
            $result = $this->authService->changePassword($request->user(), $data);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
