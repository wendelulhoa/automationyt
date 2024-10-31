<?php

namespace App\Api\Message;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Traits\UtilWhatsapp;
use Illuminate\Support\Str;

class MessageWhatsapp {
    use UtilWhatsapp;

    /**
     * Tempo de espera 4s
     */
    private $sleepTime = 4;

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
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->sleepTime);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$text`);");

            // Executa o script no navegador
            $content = $page->evaluate("window.WUAPI.sendText('$chatId', localStorage.getItem('$randomNameVar'), $mention);")['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
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
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->sleepTime);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$text \n\n $link`);");

            // Seta o script para enviar a imagem
            $script = "window.WUAPI.sendLinkPreview('$chatId', localStorage.getItem('$randomNameVar'), '$link');";

            // Executa o script no navegador
            $content = $page->evaluate($script)['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
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
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->sleepTime);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Executa o script no navegador
            $content = $page->evaluate("window.WUAPI.sendVcard('$chatId', '$title', '$contact');")['result']['result']['value'];

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
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
            sleep($this->sleepTime);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Pega o nome do arquivo e caso não exista, baixa o arquivo
            $fileName = $this->downloadFileAndSet($path);

            // Adiciona o input file no DOM
            [$backendNodeId, $nameFileInput] = $this->addInputFile($page);
            
            // Seta o caption temporário
            $randomNameVar = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$caption`);");

            // Seta o arquivo no input
            $page->setFileInput($backendNodeId, "/storage/$fileName");

            // Executa o script no navegador
            $content = $page->evaluate("window.WUAPI.sendFile(\"$chatId\", localStorage.getItem('$randomNameVar'), \"[data-$nameFileInput]\");")['result']['result']['value'];

            // Deleta a variável temporária e o input file
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");
            $page->evaluate("window.WUAPI.removeInputFile('$nameFileInput');");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage(), 'response' => $response ?? null];
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
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->sleepTime);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Pega o nome do arquivo e caso não exista, baixa o arquivo
            $fileName = $this->downloadFileAndSet($path);

            // Adiciona o input file no DOM
            [$backendNodeId, $nameFileInput] = $this->addInputFile($page);

            // Seta o arquivo no input
            $page->setFileInput($backendNodeId, "/storage/$fileName");

            // Executa o script no navegador
            $content = $page->evaluate("window.WUAPI.sendAudio(\"$chatId\", \"[data-$nameFileInput]\");")['result']['result']['value'];

            // Deleta a variável temporária e o input file
            $page->evaluate("window.WUAPI.removeInputFile('$nameFileInput');");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
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
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->sleepTime);

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
            $content = $page->evaluate("window.WUAPI.sendPoll('$chatId', localStorage.getItem('$randomNameVar'), JSON.parse(localStorage.getItem('$randomNameVarOptions')), {$poll['selectableCount']});")['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");
            $page->evaluate("localStorage.removeItem(`$randomNameVarOptions`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
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
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->sleepTime);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Executa o script no navegador
            $content = $page->evaluate("window.WUAPI.deleteMessage('$chatId', '$messageId');")['result']['result']['value'];

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
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
            $chatId = $this->getWhatsappGroupId($chatId, false, true);

            // Seta um tempo de espera
            sleep($this->sleepTime);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta o options temporário
            $randomNameVarOptions = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVarOptions', `" . json_encode($options) . "`);");

            // Executa o script no navegador
            $content = $page->evaluate("window.WUAPI.sendMsgEvent('$chatId', JSON.parse(localStorage.getItem('$randomNameVarOptions')));")['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVarOptions`);");

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}