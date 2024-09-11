<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Traits\UtilWhatsapp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CommunityController extends Controller
{
    use UtilWhatsapp;

    /**
     * Tempo de espera 0,5s
     */
    const SLEEP_TIME = 500000;

     /**
     * Cria uma comunidade
     *
     * @param Request $request  = Requisição
     * @param string $sessionId = Id da sessão
     * 
     * @return JsonResponse
     */
    public function createCommunity(Request $request, string $sessionId): JsonResponse
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'subject' => 'required|string'
            ]);

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Adiciona o id e o participante do grupo
            [$subject] = [$params['subject']];

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
                    $groups = (new GroupController)->getAllGroups($sessionId, true);
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

            // Define o status code da resposta
            $statusCode = (bool) $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message'], 'metadata' => $content['metadata']], $statusCode);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
