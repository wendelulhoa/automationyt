<?php

namespace App\Console\Commands;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Instance;
use Illuminate\Console\Command;

class SetWebhookSocketCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:set-webhook-socket-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seta o webhook para as instâncias que estão conectadas no socket';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Busca as instâncias conectadas
        $instances = Instance::where(['connected' => true])->get();
        $webhookUrl = 'https://y3280oikdc.execute-api.us-east-1.amazonaws.com/default/webhook-wuapi?x-api-key=c07422a6-5e18-4e1d-af6d-e50d152ef5d2';

        foreach ($instances as $instance) {
            // Cria uma nova página e navega até a URL
            try {
                $page = (new Puppeteer)->init($instance->session_id, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

                // Caso for socket será enviado da própria instância
                if($page->isSocket && $instance->webhook) {
                    $page->sendActionSocket($instance->session_id, 'setWebhookSettings', ['url' => $webhookUrl, 'enabled' => $instance->webhook]);
                    continue;
                }

                // Caso não for webhook não envia o webhook.
                if(!$instance->webhook) {
                    // Deleta os evento
                    $page->evaluate("window.WUAPI.clearWebhooks()");
                    continue;
                }
            } catch (\Throwable $th) {
                continue;
            }
        }
    }
}
