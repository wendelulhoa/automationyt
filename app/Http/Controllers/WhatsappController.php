<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
}
