<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRebootInstance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // try {
        //     // Verifica se a sessão existe
        //     $sessionId = $request->route('sessionId');
        //     $routeName = $request->route()->getName();

        //     $response = $next($request);
        // } catch (\Exception $e) {}
        
        return $next($request);
    }
}
