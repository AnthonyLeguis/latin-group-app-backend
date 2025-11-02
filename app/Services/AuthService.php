<?php

namespace App\Services;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterUserData;
use App\Data\Auth\ForgotPasswordData;
use App\Data\Auth\ResetPasswordData;
use App\Data\Auth\ChangePasswordData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Carbon\Carbon;

class AuthService
{
    public function login(LoginData $data): array
    {
        // Verificar si el usuario existe
        $user = User::where('email', $data->email)->first();

        if (!$user) {
            throw new \Exception('Usuario no encontrado o no autorizado');
        }

        // Verificar si el usuario está restringido/bloqueado
        if ($user->is_restricted) {
            throw new \Exception('Acceso restringido. Contacte al administrador.');
        }

        // Verificar la contraseña
        if (!Hash::check($data->password, $user->password)) {
            throw new \Exception('Contraseña inválida');
        }

        // Si es un agente, registrar el inicio de sesión y la actividad
        if ($user->type === 'agent') {
            $user->update([
                'last_activity' => now(),
                'current_session_start' => now()
            ]);
            
            \Log::info('🔔 Agente inició sesión:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'session_start' => now()->toIso8601String()
            ]);
        } else {
            // Para admins y clientes, solo actualizar last_activity
            $user->update([
                'last_activity' => now()
            ]);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function register(RegisterUserData $data, ?int $createdBy = null): array
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'type' => $data->type,
            'created_by' => $createdBy,
            'agent_id' => $data->agent_id, // Asignar el agente seleccionado
        ]);

        $token = $user->createToken('API Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function handleGoogleCallback(): array
    {
        \Log::info('🔍 Iniciando callback de Google...');
        
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            
            \Log::info('✅ Usuario de Google obtenido:', [
                'google_id' => $googleUser->getId(),
                'email' => $googleUser->getEmail(),
                'name' => $googleUser->getName()
            ]);

            // Buscar usuario existente por email
            $user = User::where('email', $googleUser->getEmail())->first();

            // Verificar que el usuario existe
            if (!$user) {
                \Log::warning('❌ Usuario no encontrado en la base de datos:', [
                    'email' => $googleUser->getEmail()
                ]);
                throw new \Exception('Usuario no registrado en el sistema');
            }

            // Verificar si el usuario está restringido/bloqueado
            if ($user->is_restricted) {
                \Log::warning('🚫 Intento de acceso de usuario restringido:', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
                throw new \Exception('Acceso restringido. Contacte al administrador.');
            }

            \Log::info('✅ Usuario encontrado en la base de datos:', [
                'id' => $user->id,
                'email' => $user->email,
                'type' => $user->type
            ]);

            // Actualizar información de Google si es necesario
            $updateData = [
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'name' => $googleUser->getName(), // Actualizar nombre si cambió
                'last_activity' => now(), // Actualizar actividad
            ];

            // Si es un agente, registrar el inicio de sesión
            if ($user->type === 'agent') {
                $updateData['current_session_start'] = now();
                
                \Log::info('🔔 Agente inició sesión (Google):', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'session_start' => now()->toIso8601String()
                ]);
            }

            $user->update($updateData);

            \Log::info('✅ Usuario actualizado con información de Google');

            $token = $user->createToken('google-token')->plainTextToken;

            \Log::info('✅ Token generado correctamente');

            return [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => $user->type,
                ],
                'token' => $token,
            ];
        } catch (\Exception $e) {
            \Log::error('❌ Error en handleGoogleCallback:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function redirectToGoogle(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    /**
     * Enviar email de recuperación de contraseña
     */
    public function forgotPassword(ForgotPasswordData $data): array
    {
        // Verificar que el usuario existe
        $user = User::where('email', $data->email)->first();

        if (!$user) {
            throw new \Exception('No se encontró ninguna cuenta con ese correo electrónico');
        }

        // Generar token único
        $token = Str::random(64);

        // Eliminar tokens anteriores de este email
        DB::table('password_reset_tokens')->where('email', $data->email)->delete();

        // Guardar el token en la base de datos
        DB::table('password_reset_tokens')->insert([
            'email' => $data->email,
            'token' => Hash::make($token),
            'created_at' => Carbon::now()
        ]);

        // URL del frontend para resetear la contraseña
        $resetUrl = config('services.frontend.url') . '/auth/reset-password?token=' . $token . '&email=' . urlencode($data->email);

        // Enviar email
        Mail::send('emails.password-reset', [
            'user' => $user,
            'resetUrl' => $resetUrl,
            'token' => $token
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                    ->subject('Recuperación de Contraseña - Latin Group');
        });

        \Log::info('Email de recuperación enviado:', [
            'email' => $data->email,
            'reset_url' => $resetUrl
        ]);

        return [
            'message' => 'Se ha enviado un correo electrónico con las instrucciones para restablecer tu contraseña'
        ];
    }

    /**
     * Resetear la contraseña con el token
     */
    public function resetPassword(ResetPasswordData $data): array
    {
        // Validar que las contraseñas coincidan
        if ($data->password !== $data->password_confirmation) {
            throw new \Exception('Las contraseñas no coinciden');
        }

        // Validar longitud mínima
        if (strlen($data->password) < 8) {
            throw new \Exception('La contraseña debe tener al menos 8 caracteres');
        }

        // Buscar el token en la base de datos
        $resetRecord = DB::table('password_reset_tokens')
            ->where('email', $data->email)
            ->first();

        if (!$resetRecord) {
            throw new \Exception('Token inválido o expirado');
        }

        // Verificar que el token no ha expirado (60 minutos)
        $createdAt = Carbon::parse($resetRecord->created_at);
        if ($createdAt->addMinutes(60)->isPast()) {
            DB::table('password_reset_tokens')->where('email', $data->email)->delete();
            throw new \Exception('El token ha expirado. Solicita uno nuevo');
        }

        // Verificar que el token coincida
        if (!Hash::check($data->token, $resetRecord->token)) {
            throw new \Exception('Token inválido');
        }

        // Buscar el usuario
        $user = User::where('email', $data->email)->first();

        if (!$user) {
            throw new \Exception('Usuario no encontrado');
        }

        // Actualizar la contraseña
        $user->password = Hash::make($data->password);
        $user->save();

        // Eliminar el token usado
        DB::table('password_reset_tokens')->where('email', $data->email)->delete();

        // Revocar todos los tokens existentes por seguridad
        $user->tokens()->delete();

        \Log::info('Contraseña restablecida exitosamente:', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return [
            'message' => 'Tu contraseña ha sido restablecida exitosamente. Ya puedes iniciar sesión con tu nueva contraseña'
        ];
    }

    /**
     * Cambiar contraseña del usuario autenticado
     */
    public function changePassword(User $user, ChangePasswordData $data): array
    {
        // Validar contraseña actual
        if (!Hash::check($data->current_password, $user->password)) {
            throw new \Exception('La contraseña actual es incorrecta');
        }

        // Validar que las nuevas contraseñas coincidan
        if ($data->new_password !== $data->new_password_confirmation) {
            throw new \Exception('Las contraseñas nuevas no coinciden');
        }

        // Validar longitud mínima
        if (strlen($data->new_password) < 8) {
            throw new \Exception('La nueva contraseña debe tener al menos 8 caracteres');
        }

        // Validar que la nueva contraseña sea diferente a la actual
        if (Hash::check($data->new_password, $user->password)) {
            throw new \Exception('La nueva contraseña debe ser diferente a la actual');
        }

        // Actualizar la contraseña
        $user->password = Hash::make($data->new_password);
        $user->save();

        \Log::info('Contraseña cambiada exitosamente:', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        return [
            'message' => 'Tu contraseña ha sido actualizada exitosamente'
        ];
    }
}
