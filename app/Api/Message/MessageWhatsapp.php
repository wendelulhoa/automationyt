<?php

namespace App\Api\Message;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Traits\UtilWhatsapp;
use Illuminate\Support\Str;
use Hazaveh\LinkPreview\Client;
use Illuminate\Support\Facades\Cache;

class MessageWhatsapp {
    use UtilWhatsapp;

    /**
     * Tempo de espera 1s
     */
    private $sleepTime = 1;

    /**
     * Envia uma mensagem de texto
     *
     * @param string $sessionId = Id da sessão
     * @param string $chatId    = Id do chat
     * @param string $text      = Texto
     * @param boolean $mention  = Se é para mencionar
     * 
     * @return array
     */
    public function sendText(string $sessionId, string $chatId, string $text, bool $mention): array
    {
        try {
            // Pega o chatId
            $body   = []; 
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta como true ou false para enviar
            $mention = $mention ? 'true' : 'false';

            // Normalizar texto para UTF-8 (se necessário)
            $text = str_replace(["´", "`", "'"], ["", "", ""], $text);

            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$text`);");

            // Executa o script no navegador
            $body    = $page->evaluate("window.WUAPI.sendText('$chatId', localStorage.getItem('$randomNameVar'), $mention);");
            $content = $body['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'body' => $body ?? null];
        }
    }

    /**
     * Envia uma mensagem de linkpreview
     *
     * @param string $sessionId = Id da sessão
     * @param string $chatId    = Id do chat
     * @param string $text      = Texto
     * @param string $link      = Link
     * 
     * @return array
     */
    public function sendLinkPreview(string $sessionId, string $chatId, string $text, string $link): array
    {
        try {
            // Pega o chatId
            $body   = [];
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Normalizar texto para UTF-8 (se necessário)
            $text = str_replace(["´", "`", "'"], ["", "", ""], $text);

            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$text \n\n $link`);");
            
            // Desativado até encontrar uma solução para o linkpreview grande
            if(false) {
                // Pega a url do texto
                $infoUrl = $page->evaluate("require('WALinkify').findLink(localStorage.getItem('$randomNameVar'))")['result']['result']['value'];
    
                // Verifica quais urls não funcionam na nova modalidade
                $excludedDomains = ['shopee', 'instagram', 'facebook', 'temu', 'google'];
                $isExcluded = false;
                foreach ($excludedDomains as $domain) {
                    if (str_contains($infoUrl['url'], $domain)) {
                        $isExcluded = true;
                        break;
                    }
                }

                // Gera um preview do link
                $preview = Cache::remember(md5($infoUrl['url']), now()->addMinutes(120), function () use($infoUrl) {
                    return (new Client)->parse($infoUrl['url']);
                }); 

                // Pega a imagem
                $urlImage = $preview->image;

                // Verifica se a imagem é relativa
                if (substr($preview->image, 0, 1) === '/') {
                    $urlImage = "{$infoUrl['scheme']}{$infoUrl['domain']}{$preview->image}"; // Adiciona a base se for uma URL relativa
                }

                // Pega o nome do arquivo e caso não exista, baixa o arquivo
                $fileName = $this->downloadFileAndSet($urlImage);

                // Adiciona o input file no DOM
                [$backendNodeId, $nameFileInput] = $this->addInputFile($page);
                
                // Seta o arquivo no input
                $page->setFileInput($backendNodeId, "/storage/$fileName");

                // Executa o script no navegador
                $script = "window.WUAPI.sendCustomLinkPreview(\"$chatId\",  localStorage.getItem('$randomNameVar') , {url: '$preview->url', title: '$preview->title', 'description': '$preview->description'}, \"[data-$nameFileInput]\", \"$fileName\");";
            } else {
                // Seta o script para enviar a imagem
                $script = "window.WUAPI.sendLinkPreview('$chatId', localStorage.getItem('$randomNameVar'), '$link');";
            }
            

            // Executa o script no navegador
            $body    = $page->evaluate($script);
            $content = $body['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'body' => $body ?? null];
        }
    }

    /**
     * Envia um contato
     *
     * @param string $sessionId = Id da sessão
     * @param string $chatId    = Id do chat
     * @param string $title     = Título
     * @param string $contact   = Contato    
     * 
     * @return array
     */
    public function sendVcard(string $sessionId, string $chatId, string $title, string $contact): array
    {
        try {
            // Pega o chatId
            $body   = [];
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Executa o script no navegador
            $body    = $page->evaluate("window.WUAPI.sendVcard('$chatId', '$title', '$contact');");
            $content = $body['result']['result']['value'];

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'body' => $body ?? null];
        }
    }

    /**
     * Envia arquivos
     *
     * @param string      $sessionId = Id da sessão
     * @param string      $chatId    = Id do chat
     * @param string|null $caption   = Legenda
     * @param string      $path      = Caminho do arquivo
     * 
     * @return array
     */
    public function sendFile(string $sessionId, string $chatId, string|null $caption, string $path): array
    {
        try {
            // Pega o chatId
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Pega o nome do arquivo e caso não exista, baixa o arquivo
            $fileName = $this->downloadFileAndSet($path);

            // Normalizar texto para UTF-8 (se necessário)
            $caption = str_replace(["´", "`", "'"], ["", "", ""], $caption);
            
            // Seta o caption temporário
            $randomNameVar = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$caption`);");

            // Adiciona o input file no DOM
            [$backendNodeId, $nameFileInput] = $this->addInputFile($page);
            
            // Seta o arquivo no input
            $page->setFileInput($backendNodeId, "/storage/$fileName");

            // Executa o script no navegador
            $body    = $page->evaluate("window.WUAPI.sendFile(\"$chatId\", localStorage.getItem('$randomNameVar'), \"[data-$nameFileInput]\", \"$fileName\");");
            $content = $body['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'body' => $body ?? null];
        }
    }

   
    /**
     * Envio de arquivos de áudio
     *
     * @param string $sessionId = Id da sessão
     * @param string $chatId    = Id do chat
     * @param string $path      = Caminho do arquivo
     * 
     * @return array
     */
    public function sendAudio(string $sessionId, string $chatId, string $path): array
    {
        try {
            // Pega o chatId
            $body   = [];
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Pega o nome do arquivo e caso não exista, baixa o arquivo
            $fileName = $this->downloadFileAndSet($path);

            // Adiciona o input file no DOM
            [$backendNodeId, $nameFileInput] = $this->addInputFile($page);
            
            // Seta o arquivo no input
            $page->setFileInput($backendNodeId, "/storage/$fileName");

            // Executa o script no navegador
            $body    = $page->evaluate("window.WUAPI.sendAudio(\"$chatId\", \"[data-$nameFileInput]\");");
            $content = $body['result']['result']['value'];

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'body' => $body ?? null];
        }
    }

    /**
     * Faz o envio de enquete
     *
     * @param string $sessionId = Id da sessão
     * @param string $chatId    = Id do chat
     * @param array $poll       = Dados da enquete
     * 
     * @return array
     */
    public function sendPoll(string $sessionId, string $chatId, array $poll): array
    {
        try {
            // Pega o chatId
            $body   = [];
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Monta as opções
            $auxOptions = [];
            foreach ($poll['options'] as $option) {
                $auxOptions[] = ['name' => $option];
            }

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta o question temporário
            $randomNameVar = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVar', `{$poll['name']}`);");

            // Seta o options temporário
            $randomNameVarOptions = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVarOptions', `" . json_encode($auxOptions) . "`);");

            // Executa o script no navegador
            $body    = $page->evaluate("window.WUAPI.sendPoll('$chatId', localStorage.getItem('$randomNameVar'), JSON.parse(localStorage.getItem('$randomNameVarOptions')), {$poll['selectableCount']});");
            $content = $body['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'body' => $body ?? null];
        }
    }

    /**
     * Deleta uma mensagem
     *
     * @param string $sessionId = Id da sessão
     * @param string $chatId    = Id do chat
     * @param string $messageId = Id da mensagem
     * 
     * @return array
     */
    public function deleteMessage(string $sessionId, string $chatId, string $messageId): array
    {
        try {
            // Pega o chatId
            $body   = [];
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Executa o script no navegador
            $body    = $page->evaluate("window.WUAPI.deleteMessage('$chatId', '$messageId');");
            $content = $body['result']['result']['value'];

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'body' => $body ?? null];
        }
    }

    /**
     * Envia um evento
     *
     * @param string $sessionId = Id da sessão
     * @param string $chatId    = Id do chat
     * @param array  $options   = Opções
     * 
     * @return array
     */
    public function sendEvent(string $sessionId, string $chatId, array $options): array
    {
        try {
            // Pega o chatId
            $body   = [];
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta o options temporário
            $randomNameVarOptions = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVarOptions', `" . json_encode($options) . "`);");

            // Executa o script no navegador
            $body    = $page->evaluate("window.WUAPI.sendMsgEvent('$chatId', JSON.parse(localStorage.getItem('$randomNameVarOptions')));");
            $content = $body['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVarOptions`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'body' => $body ?? null];
        }
    }
}