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

    public function register(RegisterUserData $data): array
    {
        $user = User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'type' => $data->type,
        ]);

        $token = $user->createToken('API Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function handleGoogleCallback(string $code): array
    {
        $googleUser = Socialite::driver('google')->userFromToken($code);

        $user = User::updateOrCreate(
            ['email' => $googleUser->getEmail()],
            [
                'name' => $googleUser->getName(),
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
                'type' => 'client', // Por defecto client, o determinar basado en lógica
            ]
        );

        $token = $user->createToken('API Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function getGoogleRedirectUrl(): string
    {
        return Socialite::driver('google')->redirect()->getTargetUrl();
    }
}
