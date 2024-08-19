<?php

namespace App\Http\Controllers\Puppeter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Puppeteer extends Controller
{
    /**
     * Inicializa o navegador
     *
     * @param string $sessionId
     * @param string $url
     * @param string $script
     * @param string $existsFn
     * 
     * @return Page
     */
    public function init(string $sessionId, string $url, string $script, string $existsFn = ''): Page
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

        // Verifica se a função existe
        if(($page->evaluate($existsFn)['result']['result']['type'] ?? '') == 'undefined' || empty($existsFn)) {
            // Seta o script que irá buscar o qrcode
            $page->evaluate($script);
        }

        return $page;
    }
}
