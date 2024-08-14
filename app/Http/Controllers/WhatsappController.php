<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use LaravelQRCode\Facades\QRCode;
use Illuminate\Support\Facades\File;


class WhatsappController extends Controller
{
    /**
     * Pega o QRCode para autenticação
     *
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function getQrcode(string $sessionId): JsonResponse
     {
          try {
               // Conecta ao WebSocket e obtém o conteúdo da resposta
               $result = (new WebsocketWhatsapp($sessionId, 'getQrcode'))->connWebSocket();

               // Conteúdo do QR code
               $content = $result['response'];

               // Caminho completo para o arquivo
               $fullPath = public_path("$sessionId-qrcode.svg");

               // Gera o QR code em SVG
               $qrCode = QRCode::text(trim($content['qrCode']))
                    ->setSize(6)
                    ->setMargin(2)
                    ->setOutfile($fullPath)
                    ->svg();
               
               // Lê o conteúdo do arquivo
               $fileContent = File::get($fullPath);

               // Obtém o tipo MIME do arquivo
               $mimeType = File::mimeType($fullPath);

               // Codifica o conteúdo do arquivo para base64
               $base64Content = base64_encode($fileContent);

               // Cria o Data URI
               $qrCode = 'data:' . $mimeType . ';base64,' . $base64Content;

               // Exclui o arquivo
               unlink($fullPath);

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => true,
                    'qrcode' => $qrCode,
                    'status' => 'QR code gerado com sucesso.'
               ]);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'status' => $th->getMessage(),
                    'qrcode' => null
               ]);
          }
     }
}
