<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Page;
use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Filesend;
use finfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends Controller
{
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
                'text' => 'required|string'
            ]);

            // Pega o chatId e o texto
            [$chatId, $text] = [$params['chatId'], $params['text']];

            // Aguarda 1 segundo
            sleep(1);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$text`);");

            // Seta o script para enviar a imagem
            $script = "window.WAPIWU.sendTextMsgToChat('$chatId', localStorage.getItem('$randomNameVar'));";

            // Executa o script no navegador
            $content = $page->evaluate($script)['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => ($content['success'] ? 'Mensagem enviada com sucesso.' : 'Erro ao enviar a mensagem.'), 'response' => $content], $statusCode);
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
    // public function sendVcard(Request $request): JsonResponse
    // {}

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
            $data = $request->validate([
                'chatId' => 'required|string',
                // 'caption' => 'string',
                'path' => 'required|string'
            ]);

            // Aguarda 1 segundos
            sleep(1);

            // Pega o chatId e a legenda
            [$chatId, $caption] = [$data['chatId'], $data['caption'] ?? ''];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');


            // Verificar se o arquivo já foi enviado anteriormente
            $fileSend = Filesend::where('hash', md5($data['path']))->first();
            $fileName = $fileSend->path ?? null;
            
            // Verifica se o arquivo existe
            if(!file_exists("/storage/$fileName")) {
                Filesend::where('hash', md5($data['path']))->delete();
                $fileName = null;
            }

            // Verificar se o arquivo já foi enviado anteriormente
            if(empty($fileSend)) {
                // Baixar o conteúdo do arquivo
                $fileContent = file_get_contents($data['path']);
        
                // Verificar se o conteúdo foi baixado com sucesso
                if ($fileContent === FALSE) {
                    return response()->json(['error' => 'Não foi possível baixar o arquivo'], 500);
                }
        
                // Determinar o mimetype do arquivo
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($fileContent);
        
                // Determinar a extensão do arquivo com base no mimetype
                $extension = $this->getExtensionFromMimeType($mimeType);
        
                // Gerar um nome aleatório para o arquivo
                $randomFileName = strtolower(Str::random(10));
        
                // Nome completo do arquivo com extensão
                $fileName = "$randomFileName.$extension";
                Filesend::create([
                    'path' => $fileName,
                    'hash' => md5($data['path']),
                    'type' => $mimeType,
                    'forget_in' => now()->addMinutes(120)
                ]);

                // Salva o arquivo na raiz do container
                file_put_contents("/storage/$fileName", $fileContent);

                // Define as permissões para 777
                chmod("/storage/$fileName", 0777);
            }

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
            $statusCode = $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => ($content['success'] ? 'Imagem enviada com sucesso.' : 'Erro ao enviar a imagem.')], $statusCode);
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
            $data = $request->validate([
                'chatId' => 'required|string',
                'poll' => 'required|array',
            ]);

            // Aguarda 1 segundos
            sleep(1);

            // Pega o chatId e a legenda
            [$chatId, $poll] = [$data['chatId'], $data['poll']];

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
            $statusCode = $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => ($content['success'] ? 'Enquete enviada com sucesso.' : 'Erro ao enviar a enquete.'), "teste" => $page->evaluate($script)], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    /**
     * Função para obter a extensão do arquivo com base no mimetype
     *
     * @param string $mimeType
     * 
     * @return string
     */
    private function getExtensionFromMimeType(string $mimeType)
    {
        $mimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            'audio/ogg' => 'ogg',
            'application/zip' => 'zip', // Adicionado mimetype de zip
            'application/msword' => 'doc', // Adicionado mimetype de doc
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx', // Adicionado mimetype de docx
            'application/vnd.ms-excel' => 'xls', // Adicionado mimetype de xls
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx', // Adicionado mimetype de xlsx
        ];

        return $mimeTypes[$mimeType] ?? 'bin';
    }

    /**
     * Adiciona um input file no DOM
     *
     * @param Page $page
     * @param string $nameFIleInput
     * 
     * @return array
     */
    public function addInputFile(Page $page): array
    {
        // Deleta a variável temporária
        $randomNameVar = strtolower(Str::random(6));
        $page->evaluate("window.WAPIWU.addInputFile('$randomNameVar');");

        // Pega o body 
        $body = $page->getDocument()['result']['root']['children'][1]['children'][1];
        
        // Pego todos inputs
        $auxInputs = [];
        foreach($body['children'] as $children) {
            if($children['nodeName'] == "INPUT") {
                foreach ($children['attributes'] as $attribute) {
                    if(strpos($attribute, "data-$randomNameVar") !== false) {
                            $auxInputs[$attribute] = $children;
                    }
                }
            }
        }

        // Pega o id do elemento
        return [$auxInputs["data-$randomNameVar"]['backendNodeId'], $randomNameVar];
    }
}
