<?php

namespace App\Console\Commands;

use App\Http\Controllers\Puppeter\Puppeteer;
use Illuminate\Console\Command;

class SendWebookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-webook-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia os eventos para o webhook';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verifica se o processo está ativo
        $directory = public_path('chrome-sessions');
        $instances = glob($directory . '/*');

        foreach ($instances as $instance) {
            // Extrai o nome da sessão
            $sessionId = basename($instance);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta os grupos
            $events = $page->evaluate("window.WAPIWU.webhookEvents")['result']['result']['value'];
            dd($events);
            // Envia os eventos para o webhook
            foreach ($events as $id => $event) {
                // Deleta o evento
                $page->evaluate("delete window.WAPIWU.webhookEvents['$id']")['result']['result']['value'];
            }
            dd($events);
        }
    }
}
