<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use LaravelQRCode\Facades\QRCode;

class WhatsappController extends Controller
{
    /**
     * Pega o QRCode para autenticação
     *
     * @param string $sessionId
     * 
     * @return Image
     */
    public function getQrcode(string $sessionId)
    {
       try {
            // Variável para armazenar o resultado
            $result = (new WebsocketWhatsapp($sessionId, 'getQrcode'))->connWebSocket();

            // Conteúdo
            $content = $result['response'];

            // Gera o qrcode
            $qrCode = QRCode::text(trim($content['qrCode']))
            ->setSize(100)
            ->setMargin(2)
            ->svg();

            return response()->make($qrCode, 200, ['Content-Type' => 'image/svg+xml']);
       } catch (\Throwable $th) {
            return response()->json(['sucess' => false, 'message' => $th->getMessage()]);
       }
    }


     /**
      * Download de arquivos
      *
      * @param string $filename
      * 
      * @return File
      */
     public function downloadFile(string $filename)
     {
        $savedFileContent = Storage::disk('local')->get("whatsapp/files/{$filename}");

        

        return response()->download($savedFileContent);
     }
}
