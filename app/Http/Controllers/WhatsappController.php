<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use LaravelQRCode\Facades\QRCode;
use Illuminate\Support\Facades\File;


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



     /**
      * Download de arquivos
      *
      * @param string $filename
      * 
      * @return File
      */
     public function downloadFile(string $sessionId, string $filename)
     {
          try {
               // Caminho do arquivo no MinIO
               $filePath = "$sessionId/$filename";

               // Verificar se o arquivo existe no MinIO
               if (!Storage::exists($filePath)) {
                    return response()->json(['error' => 'Arquivo não encontrado'], 404);
               }

               // Retornar o arquivo para download
               return Storage::download($filePath);
          } catch (\Throwable $th) {
               return response()->json(['error' => $th->getMessage()], 500);
          }
     }
}
