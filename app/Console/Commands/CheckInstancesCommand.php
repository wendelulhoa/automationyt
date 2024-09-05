<?php

namespace App\Console\Commands;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Instance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckInstancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-instances-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica se as instâncias estão funcionando corretamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verifica se o processo está ativo
        $directory = public_path('chrome-sessions');
        $instances = Instance::where(['connected' => true])->pluck('session_id')->toArray();

        foreach ($instances as $sessionId) {
            // Extrai o nome da sessão
            $pidFile = "$directory/$sessionId/pids/chrome-$sessionId.pid";

            // Verifica se o arquivo PID existe
            if (file_exists($pidFile)) {
                $pid = file_get_contents($pidFile);

                // Verifica se o processo está ativo e sobe a instância
                if (!$this->isProcessRunning($pid)) {
                    // Sobe a instância
                    $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');
                    
                    // Verifica a conexão
                    $content = $page->evaluate("window.WAPIWU.startSession();")['result']['result']['value'];
                    
                    // Atualiza o status da instância
                    $active = ($content['success'] ?? false) ? 'ativo' : 'inativo';

                    // Loga a ação
                    Log::info("Processo com PID $pid ($sessionId) está $active.");
                    sleep(2);
                }
            } else {
                Log::warning("Arquivo PID não encontrado para a sessão $sessionId.");
            }
        }
    }

    /**
     * Verifica se o processo está ativo no sistema
     *
     * @param int $pid
     * @return bool
     */
    private function isProcessRunning(int $pid): bool
    {
        // Verifica se o PID é válido (maior que zero)
        if ($pid <= 0) {
            return false;
        }

        // Executa o comando para verificar o processo
        $result = shell_exec(sprintf('ps -p %d 2>&1', $pid));

        // Verifica se a saída contém o PID e não retorna erro
        return (strpos($result, (string) $pid) !== false);
    }
}
