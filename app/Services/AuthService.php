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
        if (!Auth::attempt(['email' => $data->email, 'password' => $data->password])) {
            throw new \Exception('Credenciales inválidas');
        }

        $user = Auth::user();
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
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Buscar usuario existente por email
        $user = User::where('email', $googleUser->getEmail())->first();

        // Verificar que el usuario existe
        if (!$user) {
            throw new \Exception('Usuario no registrado en el sistema');
        }

        // Actualizar información de Google si es necesario
        $user->update([
            'google_id' => $googleUser->getId(),
            'avatar' => $googleUser->getAvatar(),
            'name' => $googleUser->getName(), // Actualizar nombre si cambió
        ]);

        $token = $user->createToken('google-token')->plainTextToken;

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'type' => $user->type,
            ],
            'token' => $token,
        ];
    }

    public function redirectToGoogle(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')->stateless()->redirect();
    }
}
