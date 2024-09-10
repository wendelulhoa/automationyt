<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Page;
use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Filesend;
use App\Traits\UtilWhatsapp;
use finfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
    use UtilWhatsapp;

    /**
     * Tempo de espera 0,5s
     */
    const SLEEP_TIME = 500000;

    /**
     * Envia uma mensagem de texto
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendText(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'text' => 'required|string',
                'mention' => 'int'
            ]);

            // Pega o chatId e o texto
            [$chatId, $text, $mention] = [$this->getWhatsappGroupId($params['chatId'], false, true), $params['text'], $params['mention'] ?? 0];

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$text`);");

            // Executa o script no navegador
            $content = $page->evaluate("window.WAPIWU.sendText('$chatId', localStorage.getItem('$randomNameVar'), $mention);")['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            // Define o status code da resposta
            $statusCode = (bool) $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => $content['message'], 'response' => $content], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Envia uma mensagem de linkpreview
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendLinkPreview(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'text' => 'required|string',
                'link' => 'required|string'
            ]);

            // Pega o chatId e o texto
            [$chatId, $text, $link] = [$this->getWhatsappGroupId($params['chatId'], false, true), $params['text'], $params['link']];

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$text \n\n $link`);");

            // Seta o script para enviar a imagem
            $script = "window.WAPIWU.sendLinkPreview('$chatId', localStorage.getItem('$randomNameVar'), '$link');";

            // Executa o script no navegador
            $content = $page->evaluate($script)['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            // Define o status code da resposta
            $statusCode = (bool) $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => ($content['success'] ? 'Mensagem enviada com sucesso.' : 'Erro ao enviar a mensagem.')], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Envia um contato
     *
     * @param Request $request
     * @param string $sessionId
     * @return JsonResponse
     */
    public function sendVcard(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId'  => 'required|string',
                'title'   => 'required|string',
                'contact' => 'required|string'
            ]);

            // Pega o chatId e o texto
            [$chatId, $title, $contact] = [$this->getWhatsappGroupId($params['chatId'], false, true), $params['title'], $params['contact']];

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Executa o script no navegador
            $content = $page->evaluate("window.WAPIWU.sendVcard('$chatId', '$title', '$contact');")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = (bool) $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => ($content['success'] ? 'Mensagem enviada com sucesso.' : 'Erro ao enviar a mensagem.')], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Envia uma imagem
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendFile(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'caption' => 'nullable|string',
                'path' => 'required|string'
            ]);

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Pega o chatId e a legenda
            [$chatId, $caption] = [$this->getWhatsappGroupId($params['chatId'], false, true), $params['caption'] ?? ''];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Pega o nome do arquivo e caso não exista, baixa o arquivo
            $fileName = $this->downloadFileAndSet($params['path']);

            // Adiciona o input file no DOM
            [$backendNodeId, $nameFileInput] = $this->addInputFile($page);
            
            // Seta o caption temporário
            $randomNameVar = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$caption`);");

            // Seta o arquivo no input
            $page->setFileInput($backendNodeId, "/storage/$fileName");

            // Seta o script para enviar a imagem
            $script = "window.WAPIWU.sendFile(\"$chatId\", localStorage.getItem('$randomNameVar'), \"[data-$nameFileInput]\");";

            // Executa o script no navegador
            $result   = $page->evaluate($script);
            $response = $result;
            $content  = $result['result']['result']['value'];

            // Deleta a variável temporária e o input file
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");
            $page->evaluate("window.WAPIWU.removeInputFile('$nameFileInput');");

            // Define o status code da resposta
            $statusCode = (bool) $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage(), 'response' => $response ?? null], 400);
        }
    }

    /**
     * Envia uma imagem
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendAudio(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'path'   => 'required|string',
            ]);

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Pega o chatId e a legenda
            [$chatId, $path] = [$this->getWhatsappGroupId($params['chatId'], false, true), $params['path']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Pega o nome do arquivo e caso não exista, baixa o arquivo
            $fileName = $this->downloadFileAndSet($path);

            // Adiciona o input file no DOM
            [$backendNodeId, $nameFileInput] = $this->addInputFile($page);

            // Seta o arquivo no input
            $page->setFileInput($backendNodeId, "/storage/$fileName");

            // Executa o script no navegador
            $content  = $page->evaluate("window.WAPIWU.sendAudio(\"$chatId\", \"[data-$nameFileInput]\");")['result']['result']['value'];

            // Deleta a variável temporária e o input file
            $page->evaluate("window.WAPIWU.removeInputFile('$nameFileInput');");

            // Define o status code da resposta
            $statusCode = (bool) $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage(), 'response' => $response ?? null], 400);
        }
    }

    /**
     * Faz o envio de enquete
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendPoll(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'poll' => 'required|array',
            ]);

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Pega o chatId e a legenda
            [$chatId, $poll] = [$this->getWhatsappGroupId($params['chatId'], false, true), $params['poll']];

            // Monta as opções
            $auxOptions = [];
            foreach ($poll['options'] as $option) {
                $auxOptions[] = ['name' => $option];
            }

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta o question temporário
            $randomNameVar = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVar', `{$poll['name']}`);");

            // Seta o options temporário
            $randomNameVarOptions = strtolower(Str::random(6));
            $page->evaluate("localStorage.setItem('$randomNameVarOptions', `" . json_encode($auxOptions) . "`);");

            // Seta o script para enviar a imagem
            $script = "window.WAPIWU.sendPoll('$chatId', localStorage.getItem('$randomNameVar'), JSON.parse(localStorage.getItem('$randomNameVarOptions')), {$poll['selectableCount']});";

            // Executa o script no navegador
            $content = $page->evaluate($script)['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");
            $page->evaluate("localStorage.removeItem(`$randomNameVarOptions`);");

            // Define o status code da resposta
            $statusCode = (bool) $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => $content['message'], "teste" => $page->evaluate($script)], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Deleta uma mensagem
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function deleteMessage(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'messageId' => 'required|string',
            ]);

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Pega o chatId e a legenda
            [$chatId, $messageId] = [$this->getWhatsappGroupId($params['chatId'], false, true), $params['messageId']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Executa o script no navegador
            $content = $page->evaluate("window.WAPIWU.deleteMessage('$chatId', '$messageId');")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = (bool) $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }
}
