<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Sys\UserToken;

class Authenticate
{
    public function handle(Request $request, Closure $next)
    {
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Token não informado ou mal formatado.'], 401);
        }

        $token = trim(str_replace('Bearer', '', $authHeader));
        $userToken = UserToken::with('user.client')->where('token', $token)->first();

        if (!$userToken) {
            return response()->json(['error' => 'Token inválido.'], 401);
        }

        if ($userToken->isExpired()) {
            $userToken->delete();
            return response()->json(['error' => 'Token expirado.'], 401);
        }

        $user = $userToken->user;

        if (!$user || !$user->is_active) {
            return response()->json(['error' => 'Usuário inativo ou não encontrado.'], 403);
        }

        if (!$user->is_super_admin) {
            if (!$user->client || !$user->client->is_active) {
                return response()->json(['error' => 'Cliente inativo ou não encontrado.'], 403);
            }
        }

        $request->merge([
            'auth_user' => $user,
            'login' => $user->email,
            'client_id' => $user->client_id,
        ]);

        return $next($request);
    }
}
