<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use LaravelQRCode\Facades\QRCode;
use Illuminate\Support\Facades\File;
use HeadlessChromium\BrowserFactory;

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
               // $this->stopWebSocket();
               // Verifica se o WebSocket está ativo
               if(!$this->checkWebsocket()) {
                    $this->startWebSocket();
                    sleep(2);
               }

               // Conecta ao WebSocket e obtém o conteúdo da resposta
               $result = (new WebsocketWhatsapp($sessionId, 'getQrcode'))->connWebSocket();

               // Conteúdo do QR code
               $content = $result['response'];

               $qrCode = null;
               if($content['success']) {
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
               }

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => true,
                    'qrcode' => $qrCode,
                    'status' => $content['message']
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
      * Inicia o projeto de WebSocket
      *
      * @return void
      */
     public function startWebSocket()
     {
          try {
               $command = 'nohup node /var/www/html/wapiwuphp/resources/wapi/websocket.js > output.log &';
               exec($command);

               return response()->json([
                    'success' => true,
                    'status' => 'Websocket iniciado com sucesso.'
               ]);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'status' => $th->getMessage()
               ]);
          }
     }

     /**
      * Para o projeto de WebSocket
      *
      * @return void
      */
     public function stopWebSocket()
     {
          try {
               // Comando para encontrar o PID do processo node
               $command = 'pgrep -f "node /var/www/html/wapiwuphp/resources/wapi/websocket.js"';
               $output = [];
               exec($command, $output);

               if (count($output) > 0) {
                    // Mata cada processo encontrado
                    foreach ($output as $pid) {
                         exec("kill $pid");
                    }
               } else {
                    return false;
               }

               return true;
          } catch (\Throwable $th) {
               return false;
          }
     }

     /**
      * Verifica se o WebSocket está ativo
      *
      * @return bool
      */
     public function checkWebsocket()
     {
          // Usar ps com grep para capturar o processo específico, evitando falsos positivos
          $command = 'ps aux | grep "node /var/www/html/wapiwuphp/resources/wapi/websocket.js" | grep -v grep';
          $output = [];
          exec($command, $output);

          if (count($output) > 0) {
               return true;
          } else {
               return false;
          }
     }
}
