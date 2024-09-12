<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyServerToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Obtém o token da requisição
        $tokenServer = $request->header('Authorization');
    
        // Verifica se o token existe
        if ((!$tokenServer || $tokenServer != env('API_KEY'))) {
            return response()->json(['error' => 'Acesso negado verificar token do servidor.'], 401);
        }

        return $next($request);
    }
}
