<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VerifyExternalToken
{
    public function handle(Request $request, Closure $next)
    {
        // Extrai o token do header Authorization: Bearer xxx
        $authHeader = $request->header('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return response()->json(['error' => 'Token não informado ou mal formatado'], 401);
        }

        $token = trim(str_replace('Bearer', '', $authHeader));

        // Envia o token para validação externa
        $response = Http::post('http://201.55.107.93:9090/escalasoft/administracao/usuario/validar', [
            'token' => $token
        ]);

        $dados = $response->json();

        if (!isset($dados['codigo']) || $dados['codigo'] !== '200') {
            return response()->json([
                'error' => 'Token inválido',
                'codigo' => $dados['codigo'] ?? '401',
                'mensagem' => $dados['mensagem'] ?? 'Erro desconhecido'
            ], 401);
        }

        // Injeta o usuário no request
        $request->merge([
            'login' => $dados['usuario']
        ]);

        return $next($request);
    }
}
