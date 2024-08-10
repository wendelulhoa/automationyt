<?php

namespace App\Http\Controllers;

use finfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    /**
     * Envia uma mensagem de texto
     *
     * @param Request $request
     * @param string $sessionId
     * @return void
     */
    public function sendText(Request $request, string $sessionId) 
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

    public function sendVcard(Request $request) 
    {}

    public function sendImage(Request $request, string $sessionId) 
    {
        $data = $request->validate([
            'chatId' => 'required|string',
            'caption' => 'string',
            'path' => 'required|string'
        ]);
        // URL do arquivo que você deseja baixar
        $urlFile = $data['path'];

        // Baixar o conteúdo do arquivo
        $fileContent = file_get_contents($data['path']);

        // Verificar se o conteúdo foi baixado com sucesso
        if ($fileContent === FALSE) {
            return response()->json(['error' => 'Não foi possível baixar o arquivo'], 500);
        }

        // Determinar o mimetype do arquivo
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($fileContent);

        // Gerar um hash MD5 da URL para usar como nome do arquivo
        $hashedFileName = md5($urlFile);

        // Determinar a extensão do arquivo com base no mimetype
        $extension = $this->getExtensionFromMimeType($mimeType);

        // Nome completo do arquivo com extensão
        $fileName = $hashedFileName . '.' . $extension;

        // Salvar o conteúdo baixado no armazenamento local do Laravel
        Storage::disk('local')->put($fileName, $fileContent);
        $savedFileContent = Storage::disk('local')->get($fileName);
        $savedFileHash = md5($urlFile);
        dd($savedFileHash, $hashedFileName);
        // $result = (new WebsocketWhatsapp($sessionId, 'sendFile', ['chatId' => $request->chatId, 'fileBase64' => '']))->connWebSocket();
    }

    public function sendPoll(Request $request) 
    {}

    public function decryptFileName($encryptedFileName)
    {
        // Chave de criptografia usada anteriormente
        $encryptionKey = 'chave_secreta'; // Deve ser a mesma chave usada para criptografar

        // Descriptografar o nome do arquivo para obter a URL original
        $urlFile = openssl_decrypt($encryptedFileName, 'AES-128-ECB', $encryptionKey);

        return response()->json([
            'message' => 'URL descriptografada com sucesso',
            'url_file' => $urlFile
        ]);
    }

    private function getExtensionFromMimeType($mimeType)
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
