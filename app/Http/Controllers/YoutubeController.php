<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Browser;
use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Instance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Traits\UtilWhatsapp;

class YoutubeController extends Controller
{
    use UtilWhatsapp;

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

            // Para a execução do container
            $this->stopInstance($sessionId);

            // Retorna o resultado em JSON
            return response()->json([
                    'success' => true,
                    'message' => 'Desconectado com sucesso'
            ], 200);
        } catch (\Throwable $th) {
            // Em caso de erro, retorna uma resposta de falha
            return response()->json([
                    'success' => false,
                    'message' => $th->getMessage()
            ], 400);
        }
    }


    public function navigate(Request $request, string $sessionId) 
    {
        $data = $request->all();

        // Cria uma nova página e navega até a URL
        $page = (new Puppeteer)->init($sessionId, $data['url'], '');

        $screenshot = base64_decode($page->screenShot()['result']['data']);

        // Retorna a imagem PNG como resposta
        return response($screenshot, 200)->header('Content-Type', 'image/png');
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
            // Instance::all()->each(function($value) {
            //     // Cria uma nova página e navega até a URL
            //     $page = (new Puppeteer)->init($value->session_id, 'https://www.youtube.com/watch?v=ruUSLpOfGQ4', '');
            // });
            // dd('a');
            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, '', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');
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
}
