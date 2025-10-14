<?php

namespace App\Services;

use App\Data\Client\ClientData;
use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

class ClientManagementService
{
    public function createClient(ClientData $data, int $userId): Client
    {
        return Client::create([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'address' => $data->address,
            'user_id' => $userId,
        ]);
    }

    public function getClient(int $clientId): ?Client
    {
        return Client::find($clientId);
    }

    public function getClientsByUser(int $userId): Collection
    {
        return Client::where('user_id', $userId)->get();
    }

    public function updateClient(Client $client, ClientData $data): Client
    {
        $client->update([
            'name' => $data->name,
            'email' => $data->email,
            'phone' => $data->phone,
            'address' => $data->address,
        ]);

        return $client->fresh();
    }

    public function deleteClient(Client $client): bool
    {
        return $client->delete();
    }
}
