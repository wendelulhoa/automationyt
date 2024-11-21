<?php

namespace App\Console\Commands;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Instance;
use App\Traits\UtilWhatsapp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RemoveMessagesCommand extends Command
{
    use UtilWhatsapp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-messages-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove as mensagens do whatsapp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Busca as instâncias conectadas
        $instances = Instance::where(['connected' => true])->get();

        foreach ($instances as $instance) {
            // Aguarda até que o uso da CPU seja menor que 50%
            while ($this->getPercentageCpu() >= 50) {
                // Dorme por 5 segundos antes de checar novamente
                sleep(5);
            }

            // Extrai o nome da sessão
            $sessionId = $instance->session_id;

            // Cria uma nova página e navega até a URL
            try {
                $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

                // Verifica a conexão
                $content = $page->evaluate("window.WUAPI.checkConnection();")['result']['result']['value'];

                // Verifica se está gerando qrCode
                $generateQr        = cache()->has("generate-qrcode-$sessionId");
                $isRestartInstance = cache()->has("restart-instance-$sessionId");

                if($content['status'] == 'CONNECTING' && !$generateQr && !$isRestartInstance) {
                    // Adiciona o prefixo base64 correto, incluindo o tipo MIME
                    cache()->put("restart-instance-$sessionId", "restart-instance-$sessionId", now()->addMinutes(15));

                    // Reinicia a instância
                    $this->restartSession($sessionId);

                    // Seta o log
                    Log::channel('whatsapp-removemessages')->info("Reiniciou a instância: {$sessionId}");

                    // Vai para o próximo registro
                    continue;
                }

                // Remove as mensagens da instância
                if ($content['success']) {
                    $page->evaluate("window.WUAPI.fetchAndDeleteMessagesFromIndexedDB();");

                    // Seta o log
                    Log::channel('whatsapp-removemessages')->info("Removeu as mensagens da instância: {$sessionId}");
                }
            } catch (\Throwable $th) {
                // Continua para a próxima instância em caso de erro
                continue;
            }
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
