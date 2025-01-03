<?php

namespace App\Console\Commands;

use App\Http\Controllers\Puppeter\Puppeteer;
use Illuminate\Console\Command;
use App\Models\Instance;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Api\Group\GroupWhatsapp;
use Exception;

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
            try {
                $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

                // Seta os grupos
                $events = $page->evaluate("window.WUAPI.webhookEvents")['result']['result']['value'];
            } catch (\Throwable $th) {
                continue;
            }

            // Envia os eventos para o webhook
            foreach ($events as $id => $event) {
                try {
                    // Caso não for webhook não envia o webhook.
                    if(!$instance->webhook) {
                        // Deleta o evento
                        $page->evaluate("delete window.WUAPI.webhookEvents['$id']");
                        continue;
                    }

                    // Caso tenha no cache é por está em uso
                    if(cache()->has($id) || empty($event['recipients'][0])) continue;

                    // Adiciona o prefixo base64 correto, incluindo o tipo MIME
                    cache()->put($id, $id, now()->addMinutes(2));

                    // Sempre reseta os paramêtros
                    $params = [];
                    $groupId = $event['id']['remote']['_serialized'];

                    // Busca o grupo caso seja comunidade vem o id completo
                    $group = Cache::remember("group-{$groupId}", now()->addMinutes(30), function () use($sessionId, $groupId) {
                        return (new GroupWhatsapp)->findGroupInfo($sessionId, $groupId);
                    }); 

                    // Seta os parametros do webhook
                    $params['chatid']      = $group['metadata']['id'] ?? $event['id']['remote']['user'];
                    $params['author']      = isset($event['author']) ? $event['author']['user'] : null;
                    $params['action']      = $this->getAction($event['subtype']);
                    $params['participant'] = $this->getParticipantsNumber($event['recipients'])[0];
                    $params['msgid']       = $params['action'] == 'msgreceived' ? $event['id']['_serialized'] : null;
                    $params['content']     = $params['action'] == 'msgreceived' ? $event['body'] : null;
                    $params['session']     = $sessionId;

                    // Retira o conteúdo para salvar o log
                    unset($event['body']);

                    // Caso seja entrada e saída entra para verifica se já foi enviado
                    if(cache()->has("participant-{$params['action']}-{$params['chatid']}-{$params['participant']}")) {
                        continue;
                    }

                    // Monta os paramêtros do webhook e envia o webhook
                    if (!in_array($event['id']['remote']['user'], ['status'])) {
                        // Faz o envio do webhook
                        $response = Http::post('https://y3280oikdc.execute-api.us-east-1.amazonaws.com/default/webhook-wuapi?x-api-key=c07422a6-5e18-4e1d-af6d-e50d152ef5d2', $params);
                        
                        // Verifica se a chave "message" existe e contém "Internal Server Error"
                        if (isset($response['message']) && trim($response['message']) === trim('Internal Server Error')) {
                            throw new Exception("Erro interno no servidor: " . $response['message']);
                        }

                        // Seta o log de inicio
                        Log::channel('whatsapp-webhook')->info("Enviou o webhook: {$params['action']}, Grupo: {$params['chatid']}, Instância: {$sessionId}, evento:",['params' => $params, 'response' => $response->body()]);

                        // Seta o cache para não enviar novamente
                        if(in_array($params['action'], ['entry', 'leave'])) {
                            cache()->put("participant-{$params['action']}-{$params['chatid']}-{$params['participant']}", "participant-{$params['action']}-{$params['chatid']}-{$params['participant']}", now()->addMinutes(120));
                        }
                    }
                } catch (\Throwable $th) {
                    Log::channel('whatsapp-webhook')->error("Erro webhook: {$th->getMessage()}, Instância: {$sessionId}");
                    continue;
                }

                // Deleta o evento
                $page->evaluate("delete window.WUAPI.webhookEvents['$id']");
            }
        }
    }
}
