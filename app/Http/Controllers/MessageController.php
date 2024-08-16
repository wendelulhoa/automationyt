<?php

namespace App\Http\Controllers;

use App\Models\Filesend;
use finfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
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

            // Variável para armazenar o resultado
            $result = (new WebsocketWhatsapp($sessionId, 'sendText', $params))->connWebSocket();

            // Conteúdo
            $content = $result['response'];

            return response()->json(['success' => $content['success'], 'message' => ($content['success'] ? 'Mensagem enviada com sucesso.' : 'Erro ao enviar a mensagem.')]);
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
    public function sendImage(Request $request, string $sessionId): JsonResponse
    {
        try {
            $data = $request->validate([
                'chatId' => 'required|string',
                'caption' => 'string',
                'path' => 'required|string'
            ]);

            // Verificar se o arquivo já foi enviado anteriormente
            // $fileSend = Filesend::where('hash', md5($data['path']))->first();
            $fileName = $fileSend->path ?? null;

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
                // Filesend::create([
                //     'path' => $fileName,
                //     'hash' => md5($data['path']),
                //     'type' => FILEINFO_MIME_TYPE,
                //     'forget_in' => now()->addMinutes(120)
                // ]);
        
                // Salvar o conteúdo baixado no armazenamento local do Laravel
                Storage::put($fileName, $fileContent);
    
                // Se o arquivo não foi encontrado após 15 segundos, lançar uma exceção
                if (!Storage::exists($fileName)) {
                    throw new \Exception("Erro ao salvar o arquivo no armazenamento.");
                }
            }

            // Enviar o arquivo
            $result = (new WebsocketWhatsapp($sessionId, 'sendFile', ['chatId' => $request->chatId, 'caption' => $request->caption, 'filename' => $fileName]))->connWebSocket();
            
            return response()->json(['success' => $result['response']['success'], 'message' => ($result['response']['success'] ? 'Imagem enviada com sucesso.' : 'Erro ao enviar a imagem.')]);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()]);
        }
    }

    public function sendPoll(Request $request) 
    {}

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
            // Adicione mais mimetypes conforme necessário
        ];

        return $mimeTypes[$mimeType] ?? 'bin';
    }
}
