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
        // Log::channel('daily')->info("Começou o envio de webhook");

        foreach ($instances as $instance) {
            // Extrai o nome da sessão
            $sessionId = $instance->session_id;

            // Caso não for webhook não envia o webhook.
            if(!$instance->webhook) continue;

            // Cria uma nova página e navega até a URL
            try {
                $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');
    
                // Seta os grupos
                $events = $page->evaluate("window.WUAPI.webhookEvents")['result']['result']['value'];
            } catch (\Throwable $th) {
                continue;
            }

            // Envia os eventos para o webhook
            foreach ($events as $id => $event) {
                // Deleta o evento
                $page->evaluate("delete window.WUAPI.webhookEvents['$id']");

                try {
                    // Caso tenha no cache é por está em uso
                    if(cache()->has($id) || empty($event['recipients'][0])) continue;

                    // Adiciona o prefixo base64 correto, incluindo o tipo MIME
                    cache()->put($id, $id, now()->addMinutes(5));

                    // Sempre reseta os paramêtros
                    $params = [];

                    // Seta os parametros do webhook
                    $params['chatid']      = $event['id']['remote']['user'];
                    $params['author']      = isset($event['author']) ? $event['author']['user'] : null;
                    $params['action']      = $this->getAction($event['subtype']);
                    $params['participant'] = $this->getParticipantsNumber($event['recipients'])[0];
                    $params['msgid']       = $params['action'] == 'msgreceived' ? $event['id']['_serialized'] : null;
                    $params['content']     = $params['action'] == 'msgreceived' ? $event['body'] : null;
                    $params['session']     = $sessionId;

                    // Monta os paramêtros do webhook
                    if(!in_array($event['id']['remote']['user'], ['status']) && $event['id']['fromMe'] == false) {
                        // Faz o envio do webhook
                        Http::post('https://y3280oikdc.execute-api.us-east-1.amazonaws.com/default/webhook-wuapi?x-api-key=c07422a6-5e18-4e1d-af6d-e50d152ef5d2', $params);

                        // Seta o log de inicio
                        Log::channel('whatsapp-webhook')->info("Enviou o webhook: {$params['action']}, Instância: {$sessionId}, evento:", $event);
                    }
                } catch (\Throwable $th) {
                    Log::channel('whatsapp-webhook')->error("Erro webhook: {$th->getMessage()}, Instância: {$sessionId}");
                }
            }
        }
    }
}
