<?php

namespace App\Console\Commands;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Instance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
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
        $instances = Instance::where(['connected' => true])->get();

        // Sobe as instâncias via url com o checkconnection
        foreach ($instances as $instance) {
            try {
                // Caso esteja em envio de mensagens não faz a remoção de mensagens
                if(cache()->has("request-message-{$instance->session_id}")) {
                    continue;
                }

                // Verifica se a instância está conectada
                $response = Http::withHeaders([
                    'Authorization' => env('API_KEY'),
                    'api-key' => $instance->token
                ])->get(route('wapiwu.checkconnection', ['sessionId' => $instance->session_id]))->json();

                // Espera 30s para próxima verificação.
                sleep(10);
            } catch (\Throwable $th) {
                Log::channel('daily')->error('Erro ao verificar instância: ' . $instance->session_id . ' - ' . $th->getMessage());
            }
        }
    }
}
