<?php

namespace App\Console\Commands;

use App\Http\Controllers\Puppeter\Puppeteer;
use Illuminate\Console\Command;
use App\Models\Instance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * Pega a ação
     *
     * @param string $subtype
     * 
     * @return string
     */
    private function getAction(string $subtype): string
    {
        return match ($subtype) {
            'leave'   => 'leave',
            'invite'  => 'entry',
            'message' => 'msgreceived',
            default   => 'invalid'
        };
    }

    /**
     * Pega os participantes e pega só os números
     *
     * @param array $participants
     * 
     * @return array
     */
    private function getParticipantsNumber(array $participants): array
    {
        // Pega só os números dos participantes
        foreach ($participants as $key => $participant) {
            $participants[$key] = $participant['user'];
        }

        return $participants;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Busca as instâncias conectadas
        $instances = Instance::where(['connected' => true])->get();
        
        // Seta o log de inicio
        Log::channel('daily')->info("Começou o envio de webhook");

        foreach ($instances as $instance) {
            // Extrai o nome da sessão
            $sessionId = $instance->session_id;

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta os grupos
            $events = $page->evaluate("window.WAPIWU.webhookEvents")['result']['result']['value'];

            // Envia os eventos para o webhook
            foreach ($events as $id => $event) {
                
                try {
                    // Deleta o evento
                    $page->evaluate("delete window.WAPIWU.webhookEvents['$id']")['result']['result']['value'];
                    
                    // Sempre reseta os paramêtros
                    $params = [];
                    $params['chatid']      = $event['id']['remote']['user'];
                    $params['author']      = $event['author']['user'];
                    $params['action']      = $this->getAction($event['subtype']);
                    $params['participant'] = $this->getParticipantsNumber($event['recipients'])[0];
                    $params['msgid']       = $params['action'] == 'msgreceived' ? $event['id']['_serialized'] : null;
                    $params['content']     = $params['action'] == 'msgreceived' ? $event['body'] : null;
                    $params['session']     = $sessionId;

                    
                    // Monta os paramêtros do webhook
                    if(isset($event['recipients'][0]) && !is_null($event['recipients'][0])) {
                        // Faz o envio do webhook
                        $response = Http::post('https://y3280oikdc.execute-api.us-east-1.amazonaws.com/default/webhook-wuapi?x-api-key=c07422a6-5e18-4e1d-af6d-e50d152ef5d2', $params);
                    }
                } catch (\Throwable $th) {
                    Log::error("Erro webhook: {$th->getMessage()}");
                }
            }
        }
    }
}
