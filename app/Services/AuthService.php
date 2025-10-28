<?php

namespace App\Services;

use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterUserData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class AuthService
{
    public function login(LoginData $data): array
    {
        // Verificar si el usuario existe
        $user = User::where('email', $data->email)->first();

        if (!$user) {
            throw new \Exception('Usuario no encontrado o no autorizado');
        }

        // Verificar la contraseña
        if (!Hash::check($data->password, $user->password)) {
            throw new \Exception('Contraseña inválida');
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

            \Log::info('✅ Usuario encontrado en la base de datos:', [
                'id' => $user->id,
                'email' => $user->email,
                'type' => $user->type
            ]);

            // Actualizar información de Google si es necesario
            $user->update([
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'name' => $googleUser->getName(), // Actualizar nombre si cambió
            ]);

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
}
