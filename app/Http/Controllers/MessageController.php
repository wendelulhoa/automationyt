<?php

namespace App\Http\Controllers;

use App\Api\Message\MessageWhatsapp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Envia uma mensagem de texto
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendText(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'text' => 'required|string',
                'mention' => 'int'
            ]);

            // Faz o envio da mensagem
            $content = (new MessageWhatsapp)->sendText($sessionId, $params['chatId'], $params['text'], (bool) ($params['mention'] ?? 0));

            // Gera o log de envio de mensagem
            Log::channel('whatsapp-message')->info("sendText: Enviou a mensagem para {$params['chatId']}", $content);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
    }

    /**
     * Envia uma mensagem de linkpreview
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendLinkPreview(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'text' => 'nullable|string',
                'link' => 'required|string'
            ]);

            // Faz o envio da mensagem
            $content = (new MessageWhatsapp)->sendLinkPreview($sessionId, $params['chatId'], $params['text'] ?? '', $params['link']);

            // Gera o log de envio de mensagem
            Log::channel('whatsapp-message')->info("sendLinkPreview: Enviou a mensagem para {$params['chatId']}", $content);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
    }

    /**
     * Envia um contato
     *
     * @param Request $request
     * @param string $sessionId
     * @return JsonResponse
     */
    public function sendVcard(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId'  => 'required|string',
                'title'   => 'required|string',
                'contact' => 'required|string'
            ]);

            // Faz o envio da mensagem
            $content = (new MessageWhatsapp)->sendVcard($sessionId, $params['chatId'], $params['title'], $params['contact']);

            // Gera o log de envio de mensagem
            Log::channel('whatsapp-message')->info("sendVcard: Enviou a mensagem para {$params['chatId']}", $content);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
    }

    /**
     * Envia uma imagem
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendFile(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'caption' => 'nullable|string',
                'path' => 'required|string'
            ]);

            // Faz o envio da mensagem
            $content = (new MessageWhatsapp)->sendFile($sessionId, $params['chatId'], $params['caption'], $params['path']);

            // Gera o log de envio de mensagem
            Log::channel('whatsapp-message')->info("sendFile: Enviou a mensagem para {$params['chatId']}", $content);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage(), 'response' => $response ?? null], 400);
        }
    }

    /**
     * Envia uma imagem
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendAudio(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'path'   => 'required|string',
            ]);

            // Faz o envio da mensagem
            $content = (new MessageWhatsapp)->sendAudio($sessionId, $params['chatId'], $params['path']);

            // Gera o log de envio de mensagem
            Log::channel('whatsapp-message')->info("sendAudio: Enviou a mensagem para {$params['chatId']}", $content);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage(), 'response' => $response ?? null], 400);
        }
    }

    /**
     * Faz o envio de enquete
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendPoll(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'poll' => 'required|array',
            ]);

            // Faz o envio da mensagem
            $content = (new MessageWhatsapp)->sendPoll($sessionId, $params['chatId'], $params['poll']);

            // Gera o log de envio de mensagem
            Log::channel('whatsapp-message')->info("sendPoll: Enviou a mensagem para {$params['chatId']}", $content);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
    }

    /**
     * Deleta uma mensagem
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function deleteMessage(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId' => 'required|string',
                'messageId' => 'required|string',
            ]);

            // Faz o envio da mensagem
            $content = (new MessageWhatsapp)->deleteMessage($sessionId, $params['chatId'], $params['messageId']);

            // Gera o log de envio de mensagem
            Log::channel('whatsapp-message')->info("deleteMessage: Deletou a mensagem {$params['messageId']} no grupo {$params['chatId']}", $content);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
    }

    /**
     * Envia um evento
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function sendEvent(Request $request, string $sessionId): JsonResponse
    {
        try {
            $params = $request->validate([
                'chatId'  => 'required|string',
                'options' => 'required|array',
            ]);

            // Faz o envio da mensagem
            $content = (new MessageWhatsapp)->sendEvent($sessionId, $params['chatId'], $params['options']);

            // Gera o log de envio de mensagem
            Log::channel('whatsapp-message')->info("sendEvent: Enviou a mensagem para {$params['chatId']}", $content);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
        }
    }
}
