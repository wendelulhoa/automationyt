<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ControlRequestMessage
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Caso ocorra algum erro, a resposta padrão é essa
        $response = ['status' => 'error', 'message' => 'Request not finished', 'success' => false];

        try {
            // Verifica se a sessão existe
            $sessionId = $request->route('sessionId');
            $routeName = $request->route()->getName();
            
            // Grava o log de envio de mensagem
            $this->setLog("Rota: $routeName, sessão: $sessionId");
            
            // Aguarda a finalização da requisição
            if(cache()->has("request-message-{$sessionId}")) {
                $this->waitFinishRequest($sessionId);
            }

            // Adiciona no cache para verificar daqui 10m
            cache()->put("request-message-{$sessionId}", "request-message-{$sessionId}", now()->addSeconds(30));

            // Executa a próxima requisição
            $response = $next($request);

            // Libera para a próxima requisição
            cache()->forget("request-message-{$sessionId}");

            // Aguarda 2 segundos para a próxima requisição
            sleep(2);
        } catch (\Exception $e) {
            // Libera para a próxima requisição
            cache()->forget("request-message-{$sessionId}");

            $response = ['status' => 'error', 'message' => $e->getMessage(), 'success' => false];

            // Loga o erro
            $this->setLog("Ocorreu um erro na requisição: " . $e->getMessage(), "error");
        }
        
        return $response;
    }

    /**
     * Aguarda a finalização da requisição
     *
     * @param string $sessionId
     * @return void
     */
    private function waitFinishRequest(string $sessionId) 
    {
        // Variáveis de controle
        $timeout    = 0;
        $maxTimeout = 30;
        $interval   = 1;

        // Aguarda a finalização da requisição
        while ($timeout < $maxTimeout) {
            // Verifica se a requisição foi finalizada
            if (!cache()->has("request-message-{$sessionId}")) {
                break;
            }
            
            // Aguarda 1 segundo
            sleep($interval);
            $timeout += $interval;
            $interval++;
        }
    }

    /**
     * Seta o log
     *
     * @param string $log
     * @return void
     */
    private function setLog(string $log, string $type = 'info')
    {
        try {
            // Grava o log de envio de mensagem
            Log::channel('daily')->$type($log);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
