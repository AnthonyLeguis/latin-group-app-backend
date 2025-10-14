<?php

namespace App\Http\Controllers\Api\V1;

use App\Data\Client\ClientData;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\ClientManagementService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClientController extends Controller
{
    public function __construct(
        private ClientManagementService $clientService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = ClientData::from($request->all());

        try {
            $client = $this->clientService->createClient($data, $request->user()->id);

            return response()->json([
                'message' => 'Cliente creado exitosamente',
                'client' => $client
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al crear el cliente: ' . $e->getMessage()
            ], 400);
        }
    }

    public function show(Client $client): JsonResponse
    {
        // Verificar que el cliente pertenece al usuario autenticado
        if ($client->user_id !== request()->user()->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return response()->json(['client' => $client]);
    }

    public function index(Request $request): JsonResponse
    {
        $clients = $this->clientService->getClientsByUser($request->user()->id);

        return response()->json(['clients' => $clients]);
    }

    public function update(Request $request, Client $client): JsonResponse
    {
        // Verificar que el cliente pertenece al usuario autenticado
        if ($client->user_id !== $request->user()->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $data = ClientData::from($request->all());

        try {
            $updatedClient = $this->clientService->updateClient($client, $data);

            return response()->json([
                'message' => 'Cliente actualizado exitosamente',
                'client' => $updatedClient
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al actualizar el cliente: ' . $e->getMessage()
            ], 400);
        }
    }

    public function destroy(Client $client): JsonResponse
    {
        // Verificar que el cliente pertenece al usuario autenticado
        if ($client->user_id !== request()->user()->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        try {
            $this->clientService->deleteClient($client);

            return response()->json(['message' => 'Cliente eliminado exitosamente']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al eliminar el cliente: ' . $e->getMessage()
            ], 400);
        }
    }
}
