<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Cria um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function createGroup(Request $request, string $sessionId)
    {
        try {
            $params = $request->validate([
                'name' => 'required|string',
                'participants' => 'required|array'
            ]);

            // Variável para armazenar o resultado
            $result = (new WebsocketWhatsapp($sessionId, 'createGroup', $params))->connWebSocket();

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $result['success'], 'message' => $result['response']]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Pega todos os grupos da instância
     *
     * @param string $sessionId 
     * 
     * @return JsonResponse
     */
    public function getAllGroups(string $sessionId) 
    {
        try {
            // Variável para armazenar o resultado
            $result = (new WebsocketWhatsapp($sessionId, 'getAllGroups'))->connWebSocket();

            // Retorna a resposta JSON com os grupos obtidos
            return response()->json(['success' => $result['success'], 'message' => 'Grupos obtidos com sucesso.', 'groups' => $result['response']['groups']]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Seta uma propriedade de um grupo
     *
     * @param string $sessionId
     * @param string $groupId
     * 
     * @return JsonResponse
     */
    public function setGroupProperty(Request $request, string $sessionId) 
    {
        try {
            $params = $request->validate([
                'groupId'  => 'required|string',
                'property' => 'required|string',
                'active'   => 'required|string'
            ]);

            // Variável para armazenar o resultado
            $result = (new WebsocketWhatsapp($sessionId, 'setGroupProperty', $params))->connWebSocket();

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $result['success'], 'message' => $result['response']]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Seta a descrição de um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * @return void
     */
    public function setGroupSubject(Request $request, string $sessionId)
    {
        try {
            $params = $request->validate([
                'groupId'  => 'required|string',
                'subject' => 'required|string'
            ]);

            // Variável para armazenar o resultado
            $result = (new WebsocketWhatsapp($sessionId, 'setGroupSubject', $params))->connWebSocket();

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $result['success'], 'message' => $result['response']]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Seta a descrição de um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * @return void
     */
    public function setGroupDescription(Request $request, string $sessionId)
    {
        try {
            $params = $request->validate([
                'groupId'  => 'required|string',
                'description' => 'required|string'
            ]);

            // Variável para armazenar o resultado
            $result = (new WebsocketWhatsapp($sessionId, 'setGroupDescription', $params))->connWebSocket();

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $result['success'], 'message' => $result['response']]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Busca o link de convite de um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * @return void
     */
    public function getGroupInviteLink(Request $request, string $sessionId, string $groupId)
    {
        try {
            // Variável para armazenar o resultado
            $result = (new WebsocketWhatsapp($sessionId, 'getGroupInviteLink', ['groupId' => $groupId]))->connWebSocket();

            // Retorna a resposta JSON com os grupos obtidos
            return response()->json(['success' => $result['success'], 'message' => 'Link de convite obtido com sucesso.', 'link' => $result['response']['link']]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
