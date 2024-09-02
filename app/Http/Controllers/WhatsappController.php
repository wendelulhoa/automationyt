<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Browser;
use App\Http\Controllers\Puppeter\Puppeteer;
use Illuminate\Http\JsonResponse;
use LaravelQRCode\Facades\QRCode;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

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
                    // return $qrCode = QRCode::text(trim($content['qrCode']))
                    //      ->setSize(6)
                    //      ->setMargin(2)
                    //      // ->setOutfile($fullPath)
                    //      ->svg();
                    
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
     public function disconnect(string $sessionId)
     {
          try {
               // Cria uma nova página e navega até a URL
               $browser = (new Browser($sessionId));

               // Pega a primeira página
               $page = $browser->getFirstPage();

               // Verifica se a URL atual é diferente da URL do WhatsApp
               if(!(strpos($page->getCurrentUrl(), 'https://web.whatsapp.com') !== false)) {
                    // Navega até a URL
                    $page->navigate('https://web.whatsapp.com');
               }

               // Seta o script que irá verificar a conexão
               $page->evaluate(view('whatsapp-functions.injected-functions-minified')->render());

               // Verifica a conexão
               $content = $page->evaluate("window.WAPIWU.disconnect();")['result']['result']['value'];

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
               $browser = (new Browser($sessionId));

               // Pega a primeira página
               $page = $browser->getFirstPage();

               // Verifica se a URL atual é diferente da URL do WhatsApp
               if(!(strpos($page->getCurrentUrl(), 'https://web.whatsapp.com') !== false)) {
                    // Navega até a URL
                    $page->navigate('https://web.whatsapp.com');
               }

               // Seta o script que irá verificar a conexão
               $page->evaluate(view('whatsapp-functions.injected-functions-minified')->render());

               // Verifica a conexão
               $content = $page->evaluate("window.WAPIWU.screenShot();")['result']['result']['value'];

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
