<?php

namespace App\Http\Controllers;

use App\Api\Group\GroupWhatsapp;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Illuminate\Support\Facades\Log;

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
                'subject' => 'required|string',
                'participants' => 'array'
            ]);

            // Criar um grupo
            $content = (new GroupWhatsapp)->createGroup($sessionId, $params['subject'], $params['participants']);

            // Seta o log de criação de grupo
            $this->setLog("Criou o grupo na instância: {$sessionId}", $content);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message'], 'metadata' => $content['metadata']], ((bool) $content['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Pega todos os grupos da instância
     *
     * @param string $sessionId 
     * 
     * @return JsonResponse|Array
     */
    public function getAllGroups(string $sessionId, bool $returnArr = false): JsonResponse|Array
    {
        try {
            // Busca os grupos
            $content = (new GroupWhatsapp)->getAllGroups($sessionId);

            // Retorna a resposta JSON com os grupos obtidos
            return response()->json(['success' => $content['success'], 'message' => $content['message'], 'groups' => $content['groups']], ((bool) $content['success'] ? 200 : 400));
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
                'active'   => 'required|int'
            ]);

            // Seta a propriedade do grupo
            $content = (new GroupWhatsapp)->setGroupProperty($sessionId, $params['groupId'], $params['property'], (int) $params['active']);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
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

            // Seta o título do grupo
            $content = (new GroupWhatsapp)->setGroupSubject($sessionId, $params['groupId'], $params['subject']);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Seta a descrição de um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function setGroupDescription(Request $request, string $sessionId)
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'groupId'  => 'required|string',
                'description' => 'required|string'
            ]);

            // Seta a descrição do grupo
            $content = (new GroupWhatsapp)->setGroupDescription($sessionId, $params['groupId'], $params['description']);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
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
            // Pega o link de convite do grupo
            $content = (new GroupWhatsapp)->getGroupInviteLink($sessionId, $groupId);

            // Retorna a resposta JSON com os grupos obtidos
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Busca informações de um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * @return JsonResponse
     */
    public function findGroupInfo(Request $request, string $sessionId)
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'groupId' => 'required|string'
            ]);

            // Busca um grupo/comunidade
            $content = (new GroupWhatsapp)->findGroupInfo($sessionId, $params['groupId']);

            // Retorna o resultado em JSON
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * Promove participante de um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function promoteParticipant(Request $request, string $sessionId)
    {
        try {
            $params = $request->validate([
                'groupId' => 'required|string',
                'number'  => 'required|string'
            ]);

            // Promove o participante do grupo/comunidade
            $content = (new GroupWhatsapp)->promoteParticipant($sessionId, $params['groupId'], $params['number']);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Despromove participante de um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function demoteParticipant(Request $request, string $sessionId)
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'groupId' => 'required|string',
                'number'  => 'required|string'
            ]);

            // Despromove o participante do grupo/comunidade
            $content = (new GroupWhatsapp)->demoteParticipant($sessionId, $params['groupId'], $params['number']);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Adiciona um participante a um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function addParticipant(Request $request, string $sessionId)
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'groupId' => 'required|string',
                'number'  => 'required|string'
            ]);

            // Adiciona o participante ao grupo/comunidade
            $content = (new GroupWhatsapp)->addParticipant($sessionId, $params['groupId'], $params['number']);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : ($content['status'] == '403' ? 403 : 400)));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove um participante a um grupo
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function removeParticipant(Request $request, string $sessionId): JsonResponse
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'groupId' => 'required|string',
                'number'  => 'required|string'
            ]);

            // Remove o participante do grupo/comunidade
            $content = (new GroupWhatsapp)->removeParticipant($sessionId, $params['groupId'], $params['number']);

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
    public function changeGroupPhoto(Request $request, string $sessionId): JsonResponse
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'groupId' => 'required|string',
                'path' => 'required|string'
            ]);

            // Seta a imagem
            $content = (new GroupWhatsapp)->changeGroupPhoto($sessionId, $params['groupId'], $params['path']);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage(), 'response' => $response ?? null], 400);
        }
    }

    /**
     * Busca informações de um grupo a partir de um link de convite
     *
     * @param Request $request
     * @param string $sessionId
     * 
     * @return JsonResponse
     */
    public function getGroupInfoFromInviteCode(Request $request, string $sessionId): JsonResponse
    {
        try {
            // Valida os dados da requisição
            $params = $request->validate([
                'inviteCode' => 'required|string'
            ]);

            // Busca as informações do grupo
            $content = (new GroupWhatsapp)->getGroupInfoFromInviteCode($sessionId, $params['inviteCode']);

            return response()->json($content, ((bool) $content['success'] ? 200 : 400));
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage()], 400);
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
