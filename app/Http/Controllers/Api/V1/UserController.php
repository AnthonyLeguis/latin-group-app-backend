<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Auth\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    /**
     * Listar usuarios (solo admin y agent)
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query();

        // Filtrar por tipo si se especifica
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Admin ve todos, agent solo clients
        if ($request->user()->isAgent()) {
            $query->where('type', 'client');
        }

        $users = $query->paginate(15);

        return response()->json(['users' => $users]);
    }

    /**
     * Ver usuario específico
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json(['user' => $user]);
    }

    /**
     * Crear usuario (admin puede crear admin/agent/client, agent solo client)
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $data = RegisterUserData::from($request->all());

        // Verificar permisos para el tipo de usuario usando la policy
        if (!app(UserPolicy::class)->createUserType($request->user(), $data->type)) {
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

    /**
     * Actualizar usuario
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'type' => 'sometimes|in:admin,agent,client',
        ]);

        // Verificar que no se esté cambiando a un tipo no permitido
        if (isset($validated['type'])) {
            if ($request->user()->isAgent() && $validated['type'] !== 'client') {
                return response()->json([
                    'error' => 'No puedes cambiar el tipo de usuario'
                ], 403);
            }
        }

        try {
            $user->update($validated);
            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'user' => $user->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Eliminar usuario (solo admin)
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        try {
            $user->delete();
            return response()->json(['message' => 'Usuario eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}