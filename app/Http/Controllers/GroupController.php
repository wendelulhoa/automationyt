<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Filesend;
use App\Traits\UtilWhatsapp;
use finfo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

class GroupController extends Controller
{
    use UtilWhatsapp;

    /**
     * Tempo de espera 0,5s
     */
    const SLEEP_TIME = 500000;

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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Adiciona o id e o participante do grupo
            [$subject, $participants] = [$params['subject'], $params['participants']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');
            
            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$subject`);");

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.createGroup(localStorage.getItem('$randomNameVar'));")['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message'], 'metadata' => $content['metadata']], $statusCode);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
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
            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.getAllGroups();")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com os grupos obtidos
            return response()->json(['success' => $content['success'], 'message' => $content['message'], 'groups' => $content['groups']], $statusCode);
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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Tipos de propriedades
            $typeProperties = [
                'announcement' => 1,
                'ephemeral'    => 2,
                'restrict'     => 3,
            ];

            // Adiciona o id e o título do grupo
            [$groupId, $property, $active] = [$params['groupId'], $params['property'], $params['active']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.setGroupProperty('$groupId', $typeProperties[$property], $active)")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Adiciona o id e o título do grupo
            [$groupId, $subject] = [$params['groupId'], $params['subject']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');
            
            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$subject`);");

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.setGroupSubject('$groupId', localStorage.getItem('$randomNameVar'));")['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Adiciona o id e a descrição do grupo
            [$groupId, $description] = [$params['groupId'], $params['description']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');
            
            // Seta o text temporário
            $randomNameVar = strtolower(Str::random(5));
            $page->evaluate("localStorage.setItem('$randomNameVar', `$description`);");

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.setGroupDescription('$groupId', localStorage.getItem('$randomNameVar'));")['result']['result']['value'];

            // Remove o item temporário
            $page->evaluate("localStorage.removeItem(`$randomNameVar`);");

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
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
            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');
            
            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.getGroupInviteLink('$groupId');")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com os grupos obtidos
            return response()->json(['success' => $content['success'], 'message' => $content['message'], 'link' => $content['link']], $statusCode);
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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Verifica a conexão
            $content = $page->evaluate("window.WAPIWU.findGroupInfo('{$params['groupId']}');")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna o resultado em JSON
            return response()->json([
                    'success'  => $content['success'],
                    'message'  => $content['message'],
                    'metadata' => $content['metadata']
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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Adiciona o id e o participante do grupo
            [$groupId, $number] = [$params['groupId'], $params['number']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');
            
            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.promoteParticipants('$groupId', '$number');")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Adiciona o id e o participante do grupo
            [$groupId, $number] = [$params['groupId'], $params['number']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.demoteParticipants('$groupId', '$number');")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Adiciona o id e o participante do grupo
            [$groupId, $number] = [$params['groupId'], $params['number']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU.a');

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.addParticipant('$groupId', '$number');")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
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

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Adiciona o id e o participante do grupo
            [$groupId, $number] = [$params['groupId'], $params['number']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Seta os grupos
            $content = $page->evaluate("window.WAPIWU.removeParticipant('$groupId', '$number');")['result']['result']['value'];

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            // Retorna a resposta JSON com a mensagem de sucesso
            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
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
            $data = $request->validate([
                'groupId' => 'required|string',
                'path' => 'required|string'
            ]);

            // Seta um tempo de espera
            usleep(self::SLEEP_TIME);

            // Pega o groupId e a legenda
            [$groupId, $path] = [$data['groupId'], $data['path'] ?? ''];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Pega o nome do arquivo e caso não exista, baixa o arquivo
            $fileName = $this->downloadFileAndSet($path);

            // Adiciona o input file no DOM
            [$backendNodeId, $nameFileInput] = $this->addInputFile($page);

            // Seta o arquivo no input
            $page->setFileInput($backendNodeId, "/storage/$fileName");

            // Executa o script no navegador
            $content = $page->evaluate("window.WAPIWU.changeGroupPhoto(\"$groupId\", \"[data-$nameFileInput]\");")['result']['result']['value'];

            // Deleta a variável temporária e o input file
            $page->evaluate("window.WAPIWU.removeInputFile('$nameFileInput');");

            // Define o status code da resposta
            $statusCode = $content['success'] ? 200 : 400;

            return response()->json(['success' => $content['success'], 'message' => $content['message']], $statusCode);
        } catch (\Throwable $th) {
            return response()->json(['success' => false, 'message' => $th->getMessage(), 'response' => $response ?? null], 400);
        }
    }
}
