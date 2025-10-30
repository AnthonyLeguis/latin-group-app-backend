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
            $type = $request->type;
            $query->where('type', $type);

            // Si es admin pidiendo agents, incluir sus clients
            if ($request->user()->isAdmin() && $type === 'agent') {
                $query->with(['createdUsers' => function ($q) {
                    $q->where('type', 'client')
                      ->orderBy('created_at', 'desc');
                }]);
            }

            // Si se están pidiendo clients, cargar la relación application_form
            if ($type === 'client') {
                $query->with(['applicationFormsAsClient' => function ($q) {
                    $q->select('id', 'client_id', 'status', 'confirmed');
                }]);
            }
        }

        // Siempre cargar quien creó al usuario
        $query->with(['createdBy', 'createdByAdmin']);

        // Admin ve todos, agent solo clients que él creó o que están asignados a él
        if ($request->user()->isAgent()) {
            $query->where('type', 'client')
                  ->where(function ($q) use ($request) {
                      // Clientes que el agente creó directamente
                      $q->where('created_by', $request->user()->id)
                        // O clientes que tienen una application_form asignada a este agente
                        ->orWhereHas('applicationFormsAsClient', function ($subQuery) use ($request) {
                            $subQuery->where('agent_id', $request->user()->id);
                        });
                  });
        }

        // Ordenar por fecha de creación (más recientes primero)
        $query->orderBy('created_at', 'desc');

        $users = $query->paginate(15);

        // Si se están listando clientes, agregar el atributo application_form manualmente
        if ($request->has('type') && $request->type === 'client') {
            $users->getCollection()->transform(function ($user) {
                $form = $user->applicationFormsAsClient->first();
                $user->application_form = $form ? [
                    'id' => $form->id,
                    'status' => $form->status,
                    'confirmed' => $form->confirmed
                ] : null;
                
                // Ocultar la relación original para no duplicar datos
                $user->makeHidden('applicationFormsAsClient');
                
                return $user;
            });
        }

        return response()->json($users);
    }

    /**
     * Obtener estadísticas de usuarios (solo admin)
     */
    public function stats(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'total_admins' => User::where('type', 'admin')->count(),
            'total_agents' => User::where('type', 'agent')->count(),
            'total_clients' => User::where('type', 'client')->count(),
            'recent_users' => User::orderBy('created_at', 'desc')->take(5)->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Obtener reporte detallado de agentes con sus clientes (solo admin)
     */
    public function agentsReport(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Obtener todos los agents con sus clients y application_forms
        $agents = User::where('type', 'agent')
            ->with(['createdUsers' => function ($query) {
                $query->where('type', 'client')
                      ->with([
                          'applicationFormsAsClient' => function ($q) {
                              $q->with(['reviewedBy', 'pendingChangesBy'])
                                ->orderBy('created_at', 'desc');
                          },
                          'createdByAdmin' // Incluir quién fue el admin que creó el cliente
                      ])
                      ->orderBy('created_at', 'desc')
                      ->select('id', 'name', 'email', 'created_by', 'created_by_admin', 'created_at', 'updated_at');
            }])
            ->withCount(['createdUsers as clients_count' => function ($query) {
                $query->where('type', 'client');
            }])
            ->orderBy('created_at', 'desc')
            ->get();

        // Obtener planillas con cambios pendientes de aprobación
        $pendingChangesForms = \App\Models\ApplicationForm::where('has_pending_changes', true)
            ->with(['client', 'agent', 'pendingChangesBy'])
            ->orderBy('pending_changes_at', 'desc')
            ->get();

        return response()->json([
            'agents' => $agents,
            'total_agents' => $agents->count(),
            'total_clients' => User::where('type', 'client')->count(),
            'pending_changes_forms' => $pendingChangesForms,
            'total_pending_changes' => $pendingChangesForms->count(),
        ]);
    }

    /**
     * Obtener planillas pendientes de revisión (solo admin)
     */
    public function pendingForms(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $status = $request->input('status', \App\Models\ApplicationForm::STATUS_PENDING);

        $forms = \App\Models\ApplicationForm::where('status', $status)
            ->with(['client', 'agent', 'reviewedBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($forms);
    }

    /**
     * Ver usuario específico
     */
    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        // Cargar las relaciones de quién lo creó y actualizó
        $user->load(['createdBy', 'updatedBy']);

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
            // Determinar quién debe ser el created_by y si hay un admin involucrado
            $createdBy = $request->user()->id;
            $createdByAdmin = null;
            
            // Si es un client y un admin lo está creando con un agent_id específico,
            // el created_by debe ser el agent para que el cliente quede asociado al agent
            // pero guardamos que fue un admin quien lo creó en created_by_admin
            if ($data->type === 'client' && $request->user()->isAdmin()) {
                $agentId = $request->input('agent_id');
                if ($agentId) {
                    $createdByAdmin = $request->user()->id; // Guardar que fue el admin
                    $createdBy = $agentId; // El cliente queda asociado al agent
                }
            }
            
            $result = $this->authService->register($data, $createdBy);
            
            // Si hay un admin involucrado, actualizar el campo created_by_admin
            if ($createdByAdmin) {
                $result['user']->update(['created_by_admin' => $createdByAdmin]);
            }
            
            // Si se creó un client, crear automáticamente su application_form
            if ($data->type === 'client') {
                $agentId = $request->user()->isAgent() 
                    ? $request->user()->id 
                    : $request->input('agent_id'); // Admin debe especificar el agent
                
                if (!$agentId) {
                    return response()->json([
                        'error' => 'Debe especificar un agente para el cliente'
                    ], 400);
                }

                \App\Models\ApplicationForm::create([
                    'client_id' => $result['user']->id,
                    'agent_id' => $agentId,
                    'agent_name' => User::find($agentId)->name,
                    'applicant_name' => $result['user']->name,
                    'email' => $result['user']->email,
                    'status' => \App\Models\ApplicationForm::STATUS_PENDING,
                ]);
            }
            
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
            // Registrar quién hizo la actualización
            $validated['updated_by'] = $request->user()->id;
            
            $user->update($validated);
            
            return response()->json([
                'message' => 'Usuario actualizado exitosamente',
                'user' => $user->fresh(['createdBy', 'updatedBy'])
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