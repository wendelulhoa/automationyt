<?php

namespace App\Console\Commands;

use App\Models\Instance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RebootInstancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reboot-instances-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reinicia as instâncias que estão ativas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verifica se o processo está ativo
        $instances = Instance::where(['connected' => true])->get();

        // Sobe as instâncias via url com o checkconnection
        foreach ($instances as $instance) {
            // Extrai o nome da sessão
            $sessionId = $instance->session_id;
            
            try {
                // Caso tenha no cache é por está em uso
                if(cache()->has("{$sessionId}:reboot")) continue;

                // Verifica se a sessão está marcada para reiniciar
                cache()->put("{$sessionId}:reboot", "{$sessionId}:reboot", now()->addMinutes(2));

                // Define o caminho do diretório público
                $publicPath = public_path('chrome-sessions');

                // Caminho base.
                $basePath = base_path();

                // Caso tenha um processo em execução, mata o processo
                if (file_exists("$publicPath/{$sessionId}/pids/chrome-{$sessionId}.pid")) {
                    $pid = file_get_contents("$publicPath/{$sessionId}/pids/chrome-{$sessionId}.pid");

                    // Mata o processo se estiver em execução
                    shell_exec("$basePath/stop_instance.sh $pid");
                    shell_exec("ps aux | grep $sessionId | grep -v grep | awk '{print $2}' | xargs kill -9");
                }

                // Inicia a instância
                Http::withHeaders([
                    'Authorization' => env('API_KEY'),
                    'api-key' => $instance->token
                ])->get(route('wapiwu.checkconnection', ['sessionId' => $instance->session_id]));
            } catch (\Throwable $th) {
                Log::channel('daily')->error('Erro ao reiniciar instância: ' . $instance->session_id . ' - ' . $th->getMessage());
            }

            // Remove a instância do cache
            cache()->forget("{$sessionId}:reboot");
        }
    }
}
