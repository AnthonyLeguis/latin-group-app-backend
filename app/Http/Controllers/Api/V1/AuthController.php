<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterUserData;
use App\Data\Auth\ForgotPasswordData;
use App\Data\Auth\ResetPasswordData;
use App\Data\Auth\ChangePasswordData;
use App\Events\AgentActivityUpdated;
use App\Http\Controllers\Controller;
use App\Models\User;
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
            
            // Si es un agente, hacer broadcast de la actividad
            if (isset($result['user']) && $result['user']['type'] === 'agent') {
                \Log::info('🔔 Disparando broadcast de login para agente:', [
                    'user_id' => $result['user']['id'],
                    'email' => $result['user']['email']
                ]);
                
                $this->broadcastAgentActivity();
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('❌ Error en login:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
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

            // Si es un agente, hacer broadcast de la actividad
            if ($result['user']['type'] === 'agent') {
                \Log::info('🔔 Disparando broadcast de Google login para agente:', [
                    'user_id' => $result['user']['id'],
                    'email' => $result['user']['email']
                ]);
                
                $this->broadcastAgentActivity();
            }

            // Redirigir al frontend con el token en la URL
            $frontendUrl = config('services.frontend.url') . '/dashboard';

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
            $frontendUrl = config('services.frontend.url') . '/auth/access-denied';

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

    /**
     * Renovar el token de autenticación (extender sesión 1 hora más)
     */
    public function refreshToken(Request $request)
    {
        try {
            // Verificar que hay un usuario autenticado
            if (!$request->user()) {
                return response()->json([
                    'error' => 'No autenticado'
                ], 401);
            }

            $user = $request->user();

            // Eliminar el token actual
            $request->user()->currentAccessToken()->delete();

            // Crear un nuevo token con 1 hora de vigencia
            $token = $user->createToken('API Token')->plainTextToken;

            \Log::info('Token renovado exitosamente:', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'message' => 'Sesión renovada exitosamente',
                'token' => $token,
                'expires_in' => 60 // minutos (1 hora)
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verificar el tiempo restante del token actual
     */
    public function checkTokenExpiry(Request $request)
    {
        try {
            if (!$request->user()) {
                return response()->json([
                    'error' => 'No autenticado'
                ], 401);
            }

            $currentToken = $request->user()->currentAccessToken();
            
            if (!$currentToken) {
                return response()->json([
                    'error' => 'Token no encontrado'
                ], 401);
            }

            // Obtener la fecha de creación del token
            $createdAt = $currentToken->created_at;
            $expirationMinutes = config('sanctum.expiration', 60); // 1 hora por defecto
            
            // Calcular la fecha de expiración
            $expiresAt = $createdAt->addMinutes($expirationMinutes);
            
            // Calcular los minutos restantes
            $minutesRemaining = now()->diffInMinutes($expiresAt, false);
            
            // Si el valor es negativo, el token ya expiró
            if ($minutesRemaining <= 0) {
                return response()->json([
                    'expired' => true,
                    'minutes_remaining' => 0,
                    'seconds_remaining' => 0,
                    'message' => 'El token ha expirado'
                ]);
            }

            // Calcular segundos restantes
            $secondsRemaining = now()->diffInSeconds($expiresAt, false);

            return response()->json([
                'expired' => false,
                'minutes_remaining' => $minutesRemaining,
                'seconds_remaining' => $secondsRemaining,
                'expires_at' => $expiresAt->toIso8601String(),
                'created_at' => $createdAt->toIso8601String(),
                'should_warn' => $secondsRemaining <= 180 // Advertir en los últimos 3 minutos (180 segundos)
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al verificar expiración del token:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cerrar sesión (revocar token actual)
     */
    public function logout(Request $request)
    {
        try {
            if (!$request->user()) {
                return response()->json([
                    'error' => 'No autenticado'
                ], 401);
            }

            $user = $request->user();

            // Si es un agente, calcular y acumular el tiempo activo
            if ($user->type === 'agent' && $user->current_session_start) {
                $sessionStart = $user->current_session_start;
                $sessionEnd = now();
                
                // Calcular duración de la sesión en minutos
                $sessionDuration = $sessionStart->diffInMinutes($sessionEnd);
                
                // Acumular al tiempo total
                $totalActiveTime = ($user->total_active_time ?? 0) + $sessionDuration;
                
                $user->update([
                    'last_activity' => now()->subMinutes(10), // Marcar como desconectado
                    'total_active_time' => $totalActiveTime,
                    'last_session_duration' => $sessionDuration,
                    'current_session_start' => null // Limpiar el inicio de sesión
                ]);
                
                \Log::info('🔔 Disparando evento de logout para agente:', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'session_duration' => $sessionDuration,
                    'total_active_time' => $totalActiveTime
                ]);
                
                $this->broadcastAgentActivity();
            } else {
                // Marcar como desconectado estableciendo last_activity en el pasado
                $user->update([
                    'last_activity' => now()->subMinutes(10)
                ]);
            }

            // Eliminar el token actual (después del broadcast)
            $user->currentAccessToken()->delete();

            \Log::info('Sesión cerrada exitosamente:', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return response()->json([
                'message' => 'Sesión cerrada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Broadcast agent activity stats to all connected clients
     */
    private function broadcastAgentActivity(): void
    {
        $onlineThreshold = now()->subMinutes(1);
        
        $stats = [
            'online_agents' => User::where('type', 'agent')
                ->where('last_activity', '>=', $onlineThreshold)
                ->count(),
            'total_agents' => User::where('type', 'agent')->count(),
            'timestamp' => now()->toIso8601String()
        ];
        
        \Log::info('📊 Broadcasting agent stats:', $stats);

        try {
            broadcast(new AgentActivityUpdated($stats));
        } catch (\Throwable $e) {
            \Log::error('❌ Error al transmitir evento de agentes:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
