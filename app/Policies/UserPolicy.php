<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any users.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isAgent();
    }

    /**
     * Determine whether the user can view a specific user.
     */
    public function view(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true; // Admin puede ver cualquier usuario
        }

        if ($user->isAgent()) {
            // Agent solo puede ver clients que él creó
            return $model->isClient() && $model->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create users.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isAgent();
    }

    /**
     * Determine whether the user can create a user of specific type.
     */
    public function createUserType(User $user, string $type): bool
    {
        if ($user->isAdmin()) {
            return in_array($type, ['admin', 'agent', 'client']);
        }

        if ($user->isAgent()) {
            return $type === 'client';
        }

        return false;
    }

    /**
     * Determine whether the user can update a specific user.
     */
    public function update(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true; // Admin puede modificar cualquier usuario
        }

        if ($user->isAgent()) {
            // Agent solo puede modificar clients que él creó
            return $model->isClient() && $model->created_by === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete a specific user.
     */
    public function delete(User $user, User $model): bool
    {
        if ($user->isAdmin()) {
            return true; // Admin puede eliminar cualquier usuario
        }

        // Agent no puede eliminar usuarios
        return false;
    }
}
