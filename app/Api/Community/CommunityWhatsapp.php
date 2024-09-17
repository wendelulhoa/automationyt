<?php

namespace App\Api\Chat;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Traits\GroupWhatsapp;
use App\Traits\UtilWhatsapp;
use Illuminate\Support\Str;

class CommunityWhatsapp
{
    use UtilWhatsapp;

    /**
     * Tempo de espera 0,5s
     */
    private $sleepTime = 500000;

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
            usleep($this->sleepTime);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');
            
            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$subject`);");

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.createCommunity(localStorage.getItem('$randomNameVar'));")['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

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
