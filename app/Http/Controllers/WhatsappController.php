<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Instance;
use App\Traits\UtilWhatsapp;
use Illuminate\Http\JsonResponse;
use LaravelQRCode\Facades\QRCode;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Http\Controllers\Puppeter\Browser;
use App\Api\Whatsapp\Whatsapp;

class WhatsappController extends Controller
{
     use UtilWhatsapp;

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
               // Gera o qrcode
              $content = (new Whatsapp)->getQrcode($sessionId);

               // Caso for vazio tento gerar novamente
               if(empty($content['qrCode'])) {
                    $content = (new Whatsapp)->getQrcode($sessionId);
               }

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => true,
                    'qrcode' => $content['qrCode'] ?? null,
                    'status' => $content['message'] ?? $content
               ]);
          } catch (\Throwable $th) {
               // Se der erro da restart no qrcode.
               $this->restartSession($sessionId);

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
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

               // Verifica a conexão
               $content = $page->evaluate("window.WUAPI.checkConnection();")['result']['result']['value'];

               // Caso tenha erro recarrega a página
               if(isset($content['error'])) {
                    $page->reload();
               }

               // Caso dê erro, tenta reabrir o navegador
               if($content['status'] == 'OPENING' && !cache()->has("opening-{$sessionId}")) {
                    // Adiciona no cache para verificar daqui 10m
                    cache()->put("opening-{$sessionId}", "opening-{$sessionId}", now()->addMinutes(10));

                    // Reinicia o container
                    $this->restartSession($sessionId);
               }

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
               // Cria ou atualiza a instância
               Instance::initInstance(['session_id' => $sessionId, 'connected' => false]);

               // Só desconecta se o browser estiver ativo
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

               // Verifica a conexão
               $response = $page->evaluate("window.WUAPI.disconnect();");

               // Aguarda 10s
               sleep(10);

               // Retira da instância
               cache()->forget("instance-{$sessionId}");

               // Para a execução do container
               $this->stopInstance($sessionId);

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => true,
                    'message' => 'Desconectado com sucesso',
                    'result'  => $response
               ], 200);
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
      * @return File
      */
     public function screenShot(string $sessionId)
     {
          try {
               // Cria uma nova página e navega até a URL
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');
               $screenshot = base64_decode($page->screenShot()['result']['data']);

               // Retorna a imagem PNG como resposta
               return response($screenshot, 200)->header('Content-Type', 'image/png');
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
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

               // Verifica a conexão
               $content = $page->evaluate("window.WUAPI.getPhoneNumber();")['result']['result']['value'];

               // Define o status code da resposta
               $statusCode = (bool) $content['success'] ? 200 : 400;

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
     public function startSession(Request $request, string $sessionId)
     {
          try {
               // Cria uma nova página e navega até a URL
               $browser = (new Browser($sessionId));

               // Inicia o navegador
               $browser->start();

               // Gerar um token de autenticação
               $token = Hash::make(Str::random(50));

               // Cria ou atualiza a instância
               Instance::initInstance(['session_id' => $sessionId, 'token' => $token, 'webhook' => $request->webhook ?? false, 'connected' => true]);
               
               // Retira da instância
               cache()->forget("instance-{$sessionId}");

               // Inicia a sessão.
               $content = ['success' => true, 'message' => 'Sessão iniciada com sucesso'];
               
               // Define o status code da resposta
               $statusCode = (bool) $content['success'] ? 200 : 400;

               // Retorna o resultado em JSON
               return response()->json([
                    'success' => $content['success'],
                    'message' => $content['message'],
                    'hash'    => ['apikey' => $token]
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
               $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

               // Verifica a conexão
               $content = $page->evaluate("window.WUAPI.checkNumber('$number');")['result']['result']['value'];

               // Define o status code da resposta
               $statusCode = (bool) $content['success'] ? 200 : 400;

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
      * Reinicia sessão
      *
      * @param string $sessionId

      * @return Json
      */
     public function restartInstance(string $sessionId)
     {
          try {
               $this->restartSession($sessionId);

               return response()->json([
                    'success' => true,
                    'message' => 'Será reinicializado a instância'
               ], 200);
          } catch (\Throwable $th) {
               // Em caso de erro, retorna uma resposta de falha
               return response()->json([
                    'success' => false,
                    'message' => $th->getMessage()
               ], 400);
          }
     }
}
