<?php

namespace App\Http\Controllers;

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
            // Url que pega o qrcode
            $urlApi = "http://localhost:3001/api/{$sessionId}/sendtext";

            // Pega os dados
            $data = $request->all();

            // Pega o qrcode
            $_response = Http::post($urlApi, [
                'chatid'  => $data['chatid'],
                'text' => $data['text']
            ]);

            // Caso ocorra falha
            if($_response->failed()) {
                return response()->json(['sucess' => false, 'message' => 'Ops! ocorreu um erro ao enviar a mensagem.']);
            }

            // Conteúdo
            $content = $_response->json();

            return response()->json(['sucess' => $content['sucess'], 'message' => 'Mensagem enviada com sucesso.']);
        } catch (\Throwable $th) {
            return response()->json(['sucess' => false, 'message' => $th->getMessage()]);
        }
    }

    public function sendVcard(Request $request) 
    {}

    public function sendImage(Request $request) 
    {
       // URL do arquivo a ser baixado
        // $imageUrl = 'https://meugrupovip.s3.amazonaws.com/whatsappmessage/38234/38234_700199_876474.webm?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAS34RYNXL7VOUBIYV%2F20240807%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20240807T004600Z&X-Amz-SignedHeaders=host&X-Amz-Expires=3600&X-Amz-Signature=5495d55f9a674f4abc08df97d5754e794e3bb03904d9a1491cb6c4f2f6ce57c4';
        $urlFile = 'https://e7.pngegg.com/pngimages/450/269/png-clipart-space-gray-iphone-x-showing-ios-and-iphone-4-iphone-8-plus-iphone-5-iphone-x-iphone-apple-gadget-electronics-thumbnail.png';

        // Baixar o arquivo
        $imageContent = file_get_contents($urlFile);

        // Salvar temporariamente para obter o tipo MIME
        $tempFilePath = storage_path('app/tempfile');
        file_put_contents($tempFilePath, $imageContent);

        // Obter o tipo MIME
        $mimeType = mime_content_type($tempFilePath);

        // Mapeamento de tipos MIME para extensões
        $mimeToExtension = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'video/webm' => 'mp4', // Forçar vídeos como mp4
            'video/mp4' => 'mp4',
            // Adicione mais mapeamentos conforme necessário
        ];

        // Obter a extensão com base no tipo MIME
        $extension = $mimeToExtension[$mimeType] ?? 'bin'; // 'bin' como fallback para tipos desconhecidos

        // Gerar um nome de arquivo único
        $filename = 'downloaded_file_.' . $extension;

        // Salvar o arquivo no storage local
        Storage::disk('local')->put($filename, $imageContent);

        return response()->download(Storage::disk('local')->path($filename));
    }

    public function sendPoll(Request $request) 
    {}
}
