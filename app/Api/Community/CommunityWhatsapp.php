<?php

namespace App\Api\Community;

use App\Api\Group\GroupWhatsapp;
use App\Http\Controllers\Puppeter\Puppeteer;
use App\Traits\UtilWhatsapp;
use Illuminate\Support\Str;

class CommunityWhatsapp
{
    use UtilWhatsapp;

    /**
     * Tempo de espera 2s
     */
    private $sleepTime = 2;

     /**
      * Cria uma comunidade
      *
      * @param string $sessionId = Id da sessão
      * @param string $subject   = Título da comunidade
      *
      * @return array
      */
    public function createCommunity(string $sessionId, string $subject): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->sleepTime);
           
            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Verifica a conexão
            $content = $page->evaluate("window.WUAPI.checkConnection();")['result']['result']['value'];

            // Verifica a conexão a instância.
            if (!$content['success']) {
                return [
                    'message' => 'Ops! A instância está desconectada.',
                    'success' => false,
                    'metadata' => []
                ];
            }

            // Busca se está habilitado para criação
            $enabled = $this->checkCreateGroup("create_community_{$sessionId}");

            // Verifica se ainda pode criar novos grupos.
            if(!$enabled) {
                return [
                    'message' => 'Você atingiu o limite diário de criação de grupos.',
                    'success' => false,
                    'metadata' => []
                ];
            }

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.createCommunity('$subject');")['result']['result']['value'];

            // Caso dê sucesso, pega a comunidade pai.
            if($content['success']) {
                $attempts = 0;
                $parentGroupFound = false;
                while ($attempts < 10 && !$parentGroupFound) {
                    $groups = (new GroupWhatsapp)->getAllGroups($sessionId);
                    foreach (($groups['groups'] ?? []) as $group) {
                        if(strpos($group['id'], str_replace('@g.us', '', $content['metadata']['id'])) !== false) {
                            $content['metadata']['id'] = str_replace("@g.us", "", ($group['id']));
                            $parentGroupFound = true;
                            break;
                        }
                    }

                    // Espera 1 segundo antes da próxima tentativa
                    if (!$parentGroupFound) {
                        sleep(1); 
                        $attempts++;
                    }
                }
            }

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
