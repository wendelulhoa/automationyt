<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Page;
use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Filesend;
use finfo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

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
            return response()->json(['success' => $content['success'], 'message' => $content['message'] ?? '', 'groups' => $content['groups']], $statusCode);
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
            return response()->json(['success' => $content['success'], 'message' => $content['response']], $statusCode);
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
            $params = $request->validate([
                'groupId'  => 'required|string',
                'description' => 'required|string'
            ]);

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
            $params = $request->validate([
                    'groupId' => 'required|string'
            ]);

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
            $params = $request->validate([
                'groupId' => 'required|string',
                'number'  => 'required|string'
            ]);

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
            $params = $request->validate([
                'groupId' => 'required|string',
                'number'  => 'required|string'
            ]);

            // Adiciona o id e o participante do grupo
            [$groupId, $number] = [$params['groupId'], $params['number']];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

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
    public function removeParticipant(Request $request, string $sessionId)
    {
        try {
            $params = $request->validate([
                'groupId' => 'required|string',
                'number'  => 'required|string'
            ]);

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
            $data = $request->validate([
                'groupId' => 'required|string',
                'path' => 'required|string'
            ]);

            // Aguarda 1 segundos
            sleep(1);

            // Pega o groupId e a legenda
            [$groupId, $path] = [$data['groupId'], $data['path'] ?? ''];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

            // Verificar se o arquivo já foi enviado anteriormente
            $fileSend = Filesend::where('hash', md5($path))->first();
            $fileName = $fileSend->path ?? null;
            
            // Verifica se o arquivo existe
            if(!file_exists("/storage/$fileName")) {
                Filesend::where('hash', md5($path))->delete();
                $fileName = null;
            }

            // Verificar se o arquivo já foi enviado anteriormente
            if(empty($fileSend)) {
                // Baixar o conteúdo do arquivo
                $fileContent = file_get_contents($path);
        
                // Verificar se o conteúdo foi baixado com sucesso
                if ($fileContent === FALSE) {
                    return response()->json(['error' => 'Não foi possível baixar o arquivo'], 500);
                }
        
                // Determinar o mimetype do arquivo
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($fileContent);
        
                // Determinar a extensão do arquivo com base no mimetype
                $extension = $this->getExtensionFromMimeType($mimeType);
        
                // Gerar um nome aleatório para o arquivo
                $randomFileName = strtolower(Str::random(10));
        
                // Nome completo do arquivo com extensão
                $fileName = "$randomFileName.$extension";
                Filesend::create([
                    'path' => $fileName,
                    'hash' => md5($path),
                    'type' => $mimeType,
                    'forget_in' => now()->addMinutes(120)
                ]);

                // Salva o arquivo na raiz do container
                file_put_contents("/storage/$fileName", $fileContent);

                // Define as permissões para 777
                chmod("/storage/$fileName", 0777);
            }

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

       /**
     * Função para obter a extensão do arquivo com base no mimetype
     *
     * @param string $mimeType
     * 
     * @return string
     */
    private function getExtensionFromMimeType(string $mimeType)
    {
        $mimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            'audio/ogg' => 'ogg',
            'application/zip' => 'zip', // Adicionado mimetype de zip
            'application/msword' => 'doc', // Adicionado mimetype de doc
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx', // Adicionado mimetype de docx
            'application/vnd.ms-excel' => 'xls', // Adicionado mimetype de xls
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx', // Adicionado mimetype de xlsx
            'image/webp' => 'webp', // Adicionado mimetype de webp
        ];

        return $mimeTypes[$mimeType] ?? 'bin';
    }

    /**
     * Adiciona um input file no DOM
     *
     * @param Page $page
     * @param string $nameFIleInput
     * 
     * @return array
     */
    public function addInputFile(Page $page): array
    {
        // Deleta a variável temporária
        $randomNameVar = strtolower(Str::random(6));
        $page->evaluate("window.WAPIWU.addInputFile('$randomNameVar');");

        // Pega o body 
        $body = $page->getDocument()['result']['root']['children'][1]['children'][1];
        
        // Pego todos inputs
        $auxInputs = [];
        foreach($body['children'] as $children) {
            if($children['nodeName'] == "INPUT") {
                foreach ($children['attributes'] as $attribute) {
                    if(strpos($attribute, "data-$randomNameVar") !== false) {
                            $auxInputs[$attribute] = $children;
                    }
                }
            }
        }

        // Pega o id do elemento
        return [$auxInputs["data-$randomNameVar"]['backendNodeId'], $randomNameVar];
    }
}
