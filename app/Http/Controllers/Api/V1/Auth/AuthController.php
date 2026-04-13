<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Sys\User;
use App\Models\Sys\UserToken;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'client_id' => 'nullable|uuid|exists:sys_clients,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:sys_users,email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'client_id' => $data['client_id'] ?? null,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_active' => true,
            'is_super_admin' => false,
            'created_by' => 'self-register',
            'updated_by' => 'self-register',
        ]);

        return response()->json([
            'message' => 'Usuário registrado com sucesso.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'client_id' => $user->client_id,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json(['error' => 'Credenciais inválidas.'], 401);
        }

        if (!$user->is_active) {
            return response()->json(['error' => 'Usuário inativo.'], 403);
        }

        if (!$user->is_super_admin) {
            $client = $user->client;
            if (!$client || !$client->is_active) {
                return response()->json(['error' => 'Cliente inativo ou não encontrado.'], 403);
            }
        }

        $token = UserToken::create([
            'user_id' => $user->id,
            'token' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(7),
        ]);

        return response()->json([
            'message' => 'Login realizado com sucesso.',
            'token' => $token->token,
            'expires_at' => $token->expires_at,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'client_id' => $user->client_id,
                'is_super_admin' => $user->is_super_admin,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $authHeader = $request->header('Authorization');
        $token = trim(str_replace('Bearer', '', $authHeader));

        UserToken::where('token', $token)->delete();

        return response()->json(['message' => 'Logout realizado com sucesso.']);
    }

    public function me(Request $request)
    {
        $user = $request->get('auth_user');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'client_id' => $user->client_id,
            'is_super_admin' => $user->is_super_admin,
            'client' => $user->client,
        ]);
    }
}
