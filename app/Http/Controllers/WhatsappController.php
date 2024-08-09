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
            // Url que pega o qrcode
            $urlApi = "http://localhost:3001/api/{$sessionId}/getqrcode";

            // Pega o qrcode
            $_response = Http::get($urlApi);

            // Caso ocorra falha
            if($_response->failed()) {
                return response()->json(['sucess' => false, 'message' => 'Ops! ocorreu um erro ao gerar qrcode.']);
            }

            // Conteúdo
            $content = $_response->json();

            // Gera o qrcode
            $qrCode = QRCode::text(trim($content['code']))
            ->setSize(100)
            ->setMargin(2)
            ->svg();

            return response()->make($qrCode, 200, ['Content-Type' => 'image/svg+xml']);
       } catch (\Throwable $th) {
            return response()->json(['sucess' => false, 'message' => $th->getMessage()]);
       }
    }
}
