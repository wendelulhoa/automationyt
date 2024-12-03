<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Instance;
use App\Traits\UtilWhatsapp;
use App\Http\Controllers\Puppeter\Puppeteer;
use Illuminate\Support\Facades\Log;

class OptimizeNewConnectionCommand extends Command
{
    use UtilWhatsapp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize-new-connection-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otimiza as novas instâncias';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verifica se o processo está ativo
        $instances = Instance::where(['connected' => true, 'newconnection' => true])->pluck('session_id')->toArray();

        foreach ($instances as $sessionId) {
            try {
                // Se tiver acima de 70 só retorna.
                if($this->getPercentageCpu() >= 70) continue;

                // Pega a página ativa
                $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

                // Verifica a conexão
                $content = $page->evaluate("window.WUAPI.checkConnection();")['result']['result']['value'];

                // Remove as mensagens da instância
                if ($content['success']) {
                    // Remove as mensagens
                    $this->removeMessages($page, $sessionId);

                    // Salva os membros removidos para evitar enviar duplicados
                    $this->sendWebhookCommunity($sessionId, false);
                    
                    // Retira que é uma nova instância
                    cache()->forget("newconnection-{$sessionId}");

                    // Seta que não é mais uma nova conexão.
                    Instance::initInstance(['session_id' => $sessionId, 'newconnection' => false]);
                }

                // Espera 30s
                sleep(30);
            } catch (\Throwable $th) {
                Log::channel('daily')->info("Erro na otimização da instância: {$th->getMessage()}");
            }
        }
    }
}
