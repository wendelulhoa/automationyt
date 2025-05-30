<?php

namespace App\Api\Message;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Traits\UtilWhatsapp;
use Illuminate\Support\Str;

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

            // Normalizar texto para UTF-8 (se necessário)
            $text = str_replace(["´", "`", "'"], ["", "", ""], $text);

            // Caso seja um telefone não tem como mencionar
            $mention = strpos($chatId, '@c.us') !== false ? false : $mention;

            // Envia para o socket
            if($page->isSocket) {
                $content = $page->sendActionSocket($sessionId, 'sendText', ['chatId' => $chatId, 'text' => $text, 'mention' => $mention]);
            } else {
                // Seta como true ou false para enviar
                $mention = $mention ? 'true' : 'false';

                // Executa o script no navegador
                $body    = $page->evaluate("window.WUAPI.sendText('$chatId', `$text`, $mention);");
                $content = $body['result']['result']['value'];
            }

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
            
            // Envia para o socket
            if($page->isSocket) {
                $content = $page->sendActionSocket($sessionId, 'sendLinkPreview', ['chatId' => $chatId, 'text' => "$text \n \n $link", 'link' => $link]);
            } else {
                // Seta o script para enviar a imagem
                $script = "window.WUAPI.sendLinkPreview('$chatId', `$text \n \n $link`, '$link');";
    
                // Executa o script no navegador
                $body = $page->evaluate($script);
                $content = $body['result']['result']['value'];
            }


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

            // Envia para o socket
            if($page->isSocket) {
                $content = $page->sendActionSocket($sessionId, 'sendVcard', ['chatId' => $chatId, 'title' => $title, 'contact' => $contact]);
            } else {
                // Executa o script no navegador
                $body    = $page->evaluate("window.WUAPI.sendVcard('$chatId', '$title', '$contact');");
                $content = $body['result']['result']['value'];
            }

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
     * @param string|null $originalFilename  = Nome original do arquivo
     * 
     * @return array
     */
    public function sendFile(string $sessionId, string $chatId, string|null $caption, string $path, string|null $originalFilename): array
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

            // Envia para o socket
            if($page->isSocket) {
                $content = $page->sendActionSocket($sessionId, 'sendFile', ['chatId' => $chatId, 'caption' => $caption, 'path' => "/storage/$fileName", 'fileName' => $originalFilename]);
            } else {
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
            }

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

            // Envia para o socket
            if($page->isSocket) {
                $content = $page->sendActionSocket($sessionId, 'sendFile', ['chatId' => $chatId, 'path' => "/storage/$fileName"]);
            } else {
                // Adiciona o input file no DOM
                [$backendNodeId, $nameFileInput] = $this->addInputFile($page);
                
                // Seta o arquivo no input
                $page->setFileInput($backendNodeId, "/storage/$fileName");
    
                // Executa o script no navegador
                $body    = $page->evaluate("window.WUAPI.sendAudio(\"$chatId\", \"[data-$nameFileInput]\");");
                $content = $body['result']['result']['value'];
            }

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

            // Envia para o socket
            if($page->isSocket) {
                $content = $page->sendActionSocket($sessionId, 'sendPoll', ['chatId' => $chatId, 'title' => $poll['name'], 'options' => json_encode($auxOptions)]);
            } else {
                // Executa o script no navegador
                $body    = $page->evaluate("window.WUAPI.sendPoll('$chatId', '{$poll['name']}', " . json_encode($auxOptions) . ", {$poll['selectableCount']});");
                $content = $body['result']['result']['value'];
            }

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