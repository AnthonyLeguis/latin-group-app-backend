<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Auth\RegisterUserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ApplicationForm;
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
                $query->with([
                    'applicationFormsAsClient' => function ($q) {
                        $q->select('id', 'client_id', 'status', 'confirmed');
                    },
                    'agent' // Cargar el agente asignado al cliente
                ]);
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

        if ($request->filled('search')) {
            $search = strtolower($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"]);
            });
        }

        // Ordenar por fecha de creación (más recientes primero)
        $query->orderBy('created_at', 'desc');

        $perPage = max(1, min((int) $request->input('per_page', 15), 100));

        $users = $query->paginate($perPage);

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
            'pending_forms' => ApplicationForm::where('status', ApplicationForm::STATUS_PENDING)->count(),
            'active_forms' => ApplicationForm::where('status', ApplicationForm::STATUS_ACTIVE)->count(),
            'inactive_forms' => ApplicationForm::where('status', ApplicationForm::STATUS_INACTIVE)->count(),
            'rejected_forms' => ApplicationForm::where('status', ApplicationForm::STATUS_REJECTED)->count(),
            'online_agents' => User::where('type', 'agent')
                ->where('last_activity', '>=', now()->subMinutes(1)) // 1 minuto
                ->count(),
            'recent_users' => User::orderBy('created_at', 'desc')->take(5)->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Obtener lista de agentes conectados (solo admin)
     * Considera "conectado" si last_activity < 1 minuto
     */
    public function onlineAgents(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $onlineThreshold = now()->subMinutes(1); // 1 minuto
        $totalAgents = User::where('type', 'agent')->count();

        $agents = User::where('type', 'agent')
            ->select('id', 'name', 'email', 'avatar', 'last_activity', 'created_at', 'total_active_time', 'current_session_start')
            ->orderByRaw('last_activity IS NULL, last_activity DESC')
            ->get()
            ->map(function ($agent) use ($onlineThreshold) {
                $isOnline = $agent->last_activity && $agent->last_activity >= $onlineThreshold;
                
                return [
                    'id' => $agent->id,
                    'name' => $agent->name,
                    'email' => $agent->email,
                    'avatar' => $agent->avatar,
                    'last_activity' => $agent->last_activity,
                    'is_online' => $isOnline,
                    'minutes_ago' => $agent->last_activity 
                        ? now()->diffInMinutes($agent->last_activity)
                        : null,
                    'total_active_time' => $agent->total_active_time ?? 0, // Minutos totales acumulados
                ];
            });

        $onlineCount = $agents->where('is_online', true)->count();

        return response()->json([
            'total_agents' => $totalAgents,
            'online_agents' => $onlineCount,
            'offline_agents' => $totalAgents - $onlineCount,
            'agents' => $agents,
            'last_updated' => now()->toIso8601String(),
        ]);
    }

    /**
     * Obtener reporte detallado de agentes con sus clientes (solo admin)
     * Incluye tanto agents como admins que tengan clientes con application forms
     */
    public function agentsReport(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Obtener todos los agentes (type = 'agent') con sus clientes
        $agents = User::where('type', 'agent')
            ->with([
                'createdUsers' => function ($query) {
                    $query->where('type', 'client')
                          ->with([
                              'applicationFormsAsClient' => function ($q) {
                                  $q->with(['reviewedBy', 'pendingChangesBy'])
                                    ->orderBy('created_at', 'desc');
                              },
                              'createdByAdmin'
                          ])
                          ->orderBy('created_at', 'desc')
                          ->select('id', 'name', 'email', 'created_by', 'created_by_admin', 'created_at', 'updated_at', 'agent_id');
                },
                'assignedClients' => function ($query) {
                    $query->where('type', 'client')
                          ->with([
                              'applicationFormsAsClient' => function ($q) {
                                  $q->with(['reviewedBy', 'pendingChangesBy'])
                                    ->orderBy('created_at', 'desc');
                              },
                              'createdByAdmin'
                          ])
                          ->orderBy('created_at', 'desc')
                          ->select('id', 'name', 'email', 'created_by', 'created_by_admin', 'created_at', 'updated_at', 'agent_id');
                }
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($agent) {
                $createdClients = $agent->createdUsers ?? collect();
                $assignedClients = $agent->assignedClients ?? collect();

                $combinedClients = $createdClients
                    ->concat($assignedClients)
                    ->unique('id')
                    ->values();

                $agent->setRelation('createdUsers', $combinedClients);
                $agent->setRelation('assignedClients', collect());
                $agent->clients_count = $combinedClients->count();

                return $agent;
            });

        // Obtener admins que tengan clientes donde ellos son el agent_id en application_forms
        $adminsWithClients = \App\Models\ApplicationForm::select('agent_id')
            ->whereHas('agent', function ($q) {
                $q->where('type', 'admin');
            })
            ->distinct()
            ->pluck('agent_id');

        $admins = User::where('type', 'admin')
            ->whereIn('id', $adminsWithClients)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($admin) {
                // Obtener solo los clientes que tienen application_forms donde este admin es el agent_id
                $clientIds = \App\Models\ApplicationForm::where('agent_id', $admin->id)
                    ->pluck('client_id')
                    ->unique();

                $clients = User::whereIn('id', $clientIds)
                    ->where('type', 'client')
                    ->with([
                        'applicationFormsAsClient' => function ($q) use ($admin) {
                            $q->where('agent_id', $admin->id)
                              ->with(['reviewedBy', 'pendingChangesBy'])
                              ->orderBy('created_at', 'desc');
                        },
                        'createdByAdmin'
                    ])
                    ->orderBy('created_at', 'desc')
                    ->select('id', 'name', 'email', 'created_by', 'created_by_admin', 'created_at', 'updated_at', 'agent_id')
                    ->get();

                $admin->setRelation('createdUsers', $clients);
                $admin->setRelation('assignedClients', collect());
                $admin->clients_count = $clients->count();

                return $admin;
            });

        // Combinar agents y admins en una sola colección
        $allAgentsAndAdmins = $agents->concat($admins)->sortByDesc('created_at')->values();

        // Obtener planillas con cambios pendientes de aprobación
        $pendingChangesForms = \App\Models\ApplicationForm::where('has_pending_changes', true)
            ->with(['client', 'agent', 'pendingChangesBy'])
            ->orderBy('pending_changes_at', 'desc')
            ->get();

        return response()->json([
            'agents' => $allAgentsAndAdmins, // Ahora incluye agents y admins
            'total_agents' => $allAgentsAndAdmins->count(),
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
            
            // Recargar el usuario con sus relaciones para devolver datos completos
            $result['user'] = User::with(['agent', 'createdBy', 'createdByAdmin'])
                ->find($result['user']->id);
            
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

    /**
     * Toggle restricción de acceso de un usuario (solo admin)
     */
    public function toggleRestriction(Request $request, User $user): JsonResponse
    {
        // Solo admin puede restringir usuarios
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // No se puede restringir a sí mismo
        if ($user->id === $request->user()->id) {
            return response()->json(['error' => 'No puedes restringir tu propio acceso'], 400);
        }

        try {
            $newStatus = !$user->is_restricted;
            
            $user->update([
                'is_restricted' => $newStatus
            ]);

            // Si se está restringiendo, revocar todos los tokens activos
            if ($newStatus) {
                $user->tokens()->delete();
                
                \Log::info('🚫 Usuario restringido y tokens revocados:', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'restricted_by' => $request->user()->email
                ]);
            } else {
                \Log::info('✅ Usuario desbloqueado:', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'unblocked_by' => $request->user()->email
                ]);
            }

            return response()->json([
                'message' => $newStatus ? 'Usuario restringido exitosamente' : 'Usuario desbloqueado exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_restricted' => $newStatus
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Resetear tiempo activo de un agente (solo admin)
     */
    public function resetActiveTime(Request $request, User $user): JsonResponse
    {
        // Solo admin puede resetear
        if (!$request->user()->isAdmin()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Solo se puede resetear tiempo de agentes
        if ($user->type !== 'agent') {
            return response()->json(['error' => 'Solo se puede resetear el tiempo de agentes'], 400);
        }

        try {
            $user->update([
                'total_active_time' => 0,
                'last_session_duration' => null
            ]);

            \Log::info('⏱️ Tiempo activo reseteado:', [
                'user_id' => $user->id,
                'email' => $user->email,
                'reset_by' => $request->user()->email
            ]);

            return response()->json([
                'message' => 'Tiempo activo reseteado exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'total_active_time' => 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}