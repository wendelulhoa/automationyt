<?php

namespace App\Http\Controllers\Puppeter;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class Puppeteer extends Controller
{
    /**
     * URL dos containers de fora
     *
     * @var string
     */
    private string $url = 'host.docker.internal';

    /**
     * Inicializa o navegador
     *
     * @param string $sessionId
     * @param string $url
     * @param string $script
     * @param string $existsFn
     * @param bool   $reload
     * 
     * @return Page
     */
    public function init(string $sessionId, string $url, string $script, string $existsFn = '', bool $reload = false): Page
    {
        // Cria uma nova página e navega até a URL
        $browser = (new Browser($sessionId));

        // Pega a primeira página
        $page = $browser->getFirstPage();

        // Verifica se a URL atual é diferente da URL do WhatsApp
        if(!(strpos($page->getCurrentUrl(), $url) !== false)) {
            // Navega até a URL
            $page->navigate($url);
        }   

        // Verifica se é para recarregar a página
        if($reload) {
            $page->reload();
            sleep(4);
        }

        // Verifica se a função existe e seta os scripts
        if(($page->evaluate("typeof $existsFn")['result']['result']['value'] ?? '') == 'undefined' || empty($existsFn)) {
            $page->evaluate($script);
        }

        return $page;
    }
}
