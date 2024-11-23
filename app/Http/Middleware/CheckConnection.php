<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\UtilWhatsapp;

class CheckConnection
{
    use UtilWhatsapp;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        try {
            // Verifica se a sessão existe
            $sessionId = $request->route('sessionId');
            $routeName = $request->route()->getName();
            
            // Caso esteja iniciada retorna mensagem de erro.
            if(!in_array($routeName, ['wapiwu.getqrcode', 'wapiwu.startsession', 'wapiwu.restartsession', 'wapiwu.disconnect']) && !$this->checkConnection($sessionId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Instância não iniciada.'
                ], 401);
            }
        } catch (\Exception $e) {dd($e);}
        
        return $next($request);
    }
}
