<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Puppeteer;
use Illuminate\Http\JsonResponse;
use LaravelQRCode\Facades\QRCode;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;

class WhatsappController extends Controller
{
     /**
      * Obtém o QR Code
      *
      * @param string $sessionId
      * 
      * @return JsonResponse
      */
     public function getQrcode(string $sessionId)
     {
          try {
               // Cria uma nova página e navega até a URL
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU', false);

               // Pega o qrcode
               $content = $page->evaluate("window.WAPIWU.getQrCode();")['result']['result']['value'];

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
      * Verifica a conexão com o WhatsApp
      *
      * @param string $sessionId
      * 
      * @return JsonResponse
      */
     public function checkConnection(string $sessionId)
     {
          try {
               // Cria uma nova página e navega até a URL
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

               // Verifica a conexão
               $content = $page->evaluate("window.WAPIWU.checkConnection();")['result']['result']['value'];

               // Define o status code da resposta
               $statusCode = $content['success'] ? 200 : 500;

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => $content['success'],
                    'message' => $content['message'],
                    'status'  => $content['status']
               ], $statusCode);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'message' => $th->getMessage(),
                    'status'  => null
               ], 400);
          }
     }

     /**
      * Desconecta do WhatsApp
      *
      * @param string $sessionId

      * @return JsonResponse
      */
     public function disconnect(string $sessionId): JsonResponse
     {
          try {
               // Cria uma nova página e navega até a URL
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

               // Verifica a conexão
               $content = $page->evaluate("window.WAPIWU.disconnect();")['result']['result']['value'];
               
               // Aguarde um momento para garantir que o processo foi finalizado
               sleep(2);

               // Define o caminho do diretório público
               $publicPath = public_path('');

               // Caso tenha um processo em execução, mata o processo
               if (file_exists("$publicPath/chrome-sessions/{$sessionId}/pids/chrome-{$sessionId}.pid")) {
                    $pid = file_get_contents("$publicPath/chrome-sessions/{$sessionId}/pids/chrome-{$sessionId}.pid");
                    exec("kill $pid");
                    // Aguarde um momento para garantir que o processo foi finalizado
                    sleep(1);
               }

               // Exclui a pasta da sessão
               exec("rm -rf $publicPath/chrome-sessions/{$sessionId}");
               exec("chmod -R 777 $publicPath/chrome-sessions/");
               exec("chown -R root:root $publicPath/chrome-sessions/");

               // Define o status code da resposta
               $statusCode = $content['success'] ? 200 : 400;

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => $content['success'],
                    'message' => $content['message']
               ], $statusCode);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'message' => $th->getMessage()
               ], 400);
          }
     }

     /**
      * Tira um screenshot da tela
      *
      * @param string $sessionId
      * 
      * @return JsonResponse
      */
     public function screenShot(string $sessionId)
     {
          try {
               // Cria uma nova página e navega até a URL
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');
               $content = [];

               // Define o status code da resposta
               $statusCode = $content['success'] ? 200 : 400;

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => $content['success'],
                    'message' => $content['message'],
                    'status'  => $content['status']
               ], $statusCode);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'message' => $th->getMessage(),
                    'status'  => null
               ], 400);
          }
     }

     /**
      * Obtém o número de telefone
      *
      * @param string $sessionId
      * 
      * @return JsonResponse
      */
     public function getPhoneNumber(string $sessionId)
     {
          try {
               // Cria uma nova página e navega até a URL
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

               // Verifica a conexão
               $content = $page->evaluate("window.WAPIWU.getPhoneNumber();")['result']['result']['value'];

               // Define o status code da resposta
               $statusCode = $content['success'] ? 200 : 400;

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => $content['success'],
                    'message' => $content['message'],
                    'number'  => $content['phoneNumber']
               ], $statusCode);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'message' => $th->getMessage()
               ], 400);
          }
     }

     /**
      * Inicia a sessão
      *
      * @param string $sessionId
      * 
      * @return JsonResponse
      */
     public function startSession(string $sessionId)
     {
          try {
               // Cria uma nova página e navega até a URL
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU.startSession');

               // Verifica a conexão
               $content = $page->evaluate("window.WAPIWU.startSession();")['result']['result']['value'];

               // Coloca para esperar 1 segundo
               sleep(1);

               // Define o status code da resposta
               $statusCode = $content['success'] ? 200 : 400;

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => $content['success'],
                    'message' => $content['message'],
                    'hash'    => ['apikey' => 'teste']
               ], $statusCode);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'message' => $th->getMessage()
               ], 400);
          }
     }

     /**
      * Verifica o número é válido
      *
      * @param Request $request
      * @param string $sessionId
      * @return Json
      */
     public function checkNumber(Request $request, string $sessionId)
     {
          try {
               $data = $request->validate([
                    'number' => 'required|string'
               ]);

               // Pega o número
               [$number] = [$data['number']];

               // Cria uma nova página e navega até a URL
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

               // Verifica a conexão
               $content = $page->evaluate("window.WAPIWU.checkNumber('$number');")['result']['result']['value'];

               // Define o status code da resposta
               $statusCode = $content['success'] ? 200 : 400;

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => $content['success'],
                    'message' => $content['message']
               ], $statusCode);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'message' => $th->getMessage()
               ], 400);
          }
     }
}
