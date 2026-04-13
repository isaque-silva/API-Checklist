<?php

namespace App\Http\Controllers\Api\V1\Sys;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sys\Client;

class ClientController extends Controller
{
    public function index()
    {
        return response()->json(Client::all());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'document' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
        ]);

        $client = Client::create($data);

        return response()->json($client, 201);
    }

    public function show(string $id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json(['error' => 'Cliente não encontrado.'], 404);
        }

        return response()->json($client);
    }

    public function update(Request $request, string $id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json(['error' => 'Cliente não encontrado.'], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'document' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'is_active' => 'sometimes|boolean',
        ]);

        $client->update($data);

        return response()->json($client);
    }

    public function destroy(string $id)
    {
        $client = Client::find($id);

        if (!$client) {
            return response()->json(['error' => 'Cliente não encontrado.'], 404);
        }

        $client->delete();

        return response()->json(['message' => 'Cliente removido com sucesso.']);
    }
}
