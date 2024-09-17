<?php

namespace App\Http\Controllers;

use App\Api\Chat\CommunityWhatsapp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
