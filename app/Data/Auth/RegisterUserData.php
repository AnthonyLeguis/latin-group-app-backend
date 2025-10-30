<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

class RegisterUserData extends Data
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $type, // admin, agent, client
        public ?int $agent_id = null, // ID del agente asignado (para clientes)
    ) {}

    public static function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'type' => 'required|in:admin,agent,client',
            'agent_id' => 'nullable|integer|exists:users,id',
        ];
    }
}
