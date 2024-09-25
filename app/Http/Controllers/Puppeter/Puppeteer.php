<?php

namespace App\Http\Controllers\Puppeter;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class Puppeteer extends Controller
{
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
            sleep(2);
        }

        // Verifica se a função existe e seta os scripts
        if(($page->evaluate("typeof $existsFn")['result']['result']['value'] ?? '') == 'undefined' || empty($existsFn)) {
            $page->evaluate($script);
        }

        return $page;
    }

    /**
     * Verifica se o navegador está ativo
     *
     * @param string $sessionId
     * 
     * @return boolean
     */
    public function browserIsActive(string $sessionId): bool
    {
        try {
            // Define o caminho do diretório público
            $publicPath = public_path('chrome-sessions');

            // Pega o caminho do arquivo que contém a porta
            $pathPort = "$publicPath/$sessionId/port.txt";

            // Cria os diretórios caso não existam
            if (!file_exists($pathPort)) {
                return false;
            }

            // Faz a requisição para obter a URL do socket
            $response = null;
            $success  = false;
            
            // Pega a porta do arquivo
            $port = file_get_contents($pathPort);

            try {
                // Faz a requisição para obter a URL do socket
                $response = Http::get("http://127.0.0.1:{$port}/json/version");
                $success  = $response->successful();
            } catch (\Throwable $th) {
                $success = false;
            }

            return $success;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
