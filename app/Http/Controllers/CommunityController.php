<?php

namespace App\Http\Controllers;

use App\Api\Community\CommunityWhatsapp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CommunityController extends Controller
{
    /**
     * Cria uma comunidade
     *
     * @param Request $request  = Requisição
     * @param string $sessionId = Id da sessão
     * 
     * @return JsonResponse
     */
    public function createCommunity(Request $request, string $sessionId): JsonResponse
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'subject' => 'required|string'
            ]);

            // Cria a comunidade
            $content = (new CommunityWhatsapp)->createCommunity($sessionId, $params['subject']);

            // Seta o log de criação de comunidade
            $this->setLog("Criou a comunidade na instância: {$sessionId}", $content);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Seta o log
     *
     * @param string $log
     * @return void
     */
    private function setLog(string $log, array $data = [])
    {
        try {
            // Grava o log de envio de mensagem
            Log::channel('whatsapp-creategroup')->info($log, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
