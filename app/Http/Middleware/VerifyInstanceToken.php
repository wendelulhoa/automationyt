<?php
// app/Http/Middleware/VerifyInstanceToken.php
namespace App\Http\Middleware;

use App\Models\Instance;
use Closure;

class VerifyInstanceToken
{
    /**
     * Faz a verificação do token de autenticação das instâncias
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * 
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Obtém o token da requisição
        $tokenInstance = $request->header('api-key');
  
        // Verifica se o token existe
        if ((!$tokenInstance || !Instance::where('token', $tokenInstance)->exists())) {
            // return response()->json(['error' => 'Acesso negado token verificar token da instância.'], 401);
        }

        return $next($request);
    }
}
