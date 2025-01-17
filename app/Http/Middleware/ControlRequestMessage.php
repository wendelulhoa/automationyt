<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Cache;

class ControlRequestMessage
{
    /**
     * Número máximo de requisições simultâneas
     *
     * @var integer
     */
    private $maxRequests = 7;

    /**
     * Chave para controlar a fila
     *
     * @var string
     */
    private $lockKey = 'active_requests';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Caso ocorra algum erro, a resposta padrão é essa
        $response = ['status' => 'error', 'message' => 'Request not finished', 'success' => false];

        // Verifica se a sessão existe
        $sessionId = $request->route('sessionId');
        $routeName = $request->route()->getName();

        try {
            // Caso o processamento esteja acima de 80% espera 2s.
            if($this->getPercentageCpu() >= 95) {
                sleep(2);
            }

            // Grava o log de envio de mensagem
            $this->setLog("Rota: $routeName, sessão: $sessionId");
            
            // Aguarda a finalização da requisição se tiver mais de 7 requisições simultanea.
            $this->waitFinishRequest($sessionId, 'all');

            // Aguarda a finalização da requisição de mesma sessão.
            $this->waitFinishRequest($sessionId, 'session');

            // Adiciona no cache para verificar daqui 10m
            cache()->put("request-message-{$sessionId}", "request-message-{$sessionId}", now()->addSeconds(30));

            // Executa a próxima requisição
            $response = $next($request);

            // Libera para a próxima requisição
            cache()->forget("request-message-{$sessionId}");

            // Libera a vaga
            $this->releaseLock($sessionId);
        } catch (\Exception $e) {
            // Libera para a próxima requisição
            cache()->forget("request-message-{$sessionId}");

            // Libera a vaga
            $this->releaseLock($sessionId);

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
    private function waitFinishRequest(string $sessionId, $type = 'session') 
    {
        switch ($type) {
            case 'all':
                // Variáveis de controle
                $timeout    = 0;
                $maxTimeout = 5;
                $interval   = 1;
        
                // Aguarda a finalização da requisição
                while ($timeout < $maxTimeout) {
                    // Verifica se a requisição foi finalizada
                    if ($this->canProcessRequest($sessionId)) {
                        $lockAcquired = true;
                        break;
                    }
                    
                    // Aguarda 1 segundo
                    sleep($interval);
                    $timeout += $interval;
                }
                break;
            
            case 'session':
                // Variáveis de controle
                $timeout    = 0;
                $maxTimeout = 5;
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
                }
                break;
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

    /**
     * Verifica se a requisição pode ser processada (respeitando o limite).
     *
     * @param string $sessionId = sessão
     * @return boolean
     */
    private function canProcessRequest(string $sessionId): bool
    {
        // Obtém a contagem atual de requisições ativas
        $activeRequests = Cache::get($this->lockKey, []);

        // Verifica se já há espaço na fila
        if (count($activeRequests) < $this->maxRequests) {
            $activeRequests[$sessionId] = now();
            Cache::put($this->lockKey, $activeRequests, now()->addMinutes(5));
            return true;
        }

        return false; // Não há espaço na fila
    }

    /**
     * Libera uma vaga na fila de requisições.
     *
     * @param string $sessionId = sessão
     * 
     * @return void
     */
    private function releaseLock(string $sessionId): void
    {
        $activeRequests = Cache::get($this->lockKey, []);
        if (isset($activeRequests[$sessionId])) {
            unset($activeRequests[$sessionId]);
            Cache::put($this->lockKey, $activeRequests, now()->addMinutes(5));
        }
    }

    /**
     * Busca a porcentagem da CPU
     *
     * @return float
     */
    private function getPercentageCpu()
    {
        try {
            // Pega a porcentagem do CPU
            $output = shell_exec("top -bn1 | grep 'Cpu(s)'");
            $cpuUsage = 0;

            // Processa o resultado
            preg_match('/(\d+\.\d+)\s*id/', $output, $matches);

            // Calcula o uso da CPU (100% menos a porcentagem de idle)
            if (isset($matches[1])) {
                $cpuUsage = 100 - (float)$matches[1];
            }

            return $cpuUsage;
        } catch (\Throwable $th) {
            // Retorna 0 em caso de falha
            return 0;
        }
    }
}
