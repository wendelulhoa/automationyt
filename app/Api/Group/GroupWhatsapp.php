<?php

namespace App\Api\Group;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Traits\UtilWhatsapp;
use Illuminate\Support\Str;

class GroupWhatsapp
{
    use UtilWhatsapp;

    /**
     * Tempo de espera 2s
     */
    private $sleepTime = 2;

    /**
     * Cria um grupo
     *
     * @param string $sessionId   = Id da sessão
     * @param string $subject     = Título do grupo
     * @param array $participants = Participantes
     * 
     * @return array
     */
    public function createGroup(string $sessionId, string $subject, array $participants = []): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');
            
            // Verifica a conexão
            $content = $page->evaluate("window.WUAPI.checkConnection();")['result']['result']['value'];

            // Verifica a conexão a instância.
            if (!$content['success']) {
                return [
                    'message' => 'Ops! A instância está desconectada.',
                    'success' => false,
                    'metadata' => []
                ];
            }

            // Busca se está habilitado para criação
            $enabled = $this->checkCreateGroup("create_group_{$sessionId}");
            
            // Verifica se ainda pode criar novos grupos.
            if(!$enabled) {
                return [
                    'message' => 'Você atingiu o limite diário de criação de grupos.',
                    'success' => false,
                    'metadata' => []
                ];
            }

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.createGroup('$subject');")['result']['result']['value'];

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Pega todos os grupos da instância
     *
     * @param string $sessionId = Id da sessão
     * 
     * @return array
     */
    public function getAllGroups(string $sessionId): array
    {
        try {
            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.getAllGroups();")['result']['result']['value'];

            foreach (($content['groups'] ?? []) as $key => $group) {
                // Seta que não é comunidade
                $content['groups'][$key]['isCommunity'] = false;

                // Se tem o parentGroup então é uma comunidade.
                if(isset($group['parentGroup'])) {
                    $content['groups'][$key]['id'] = str_replace("@g.us", "", ($group['id'] ."_". $group['parentGroup']));
                    $content['groups'][$key]['isCommunity']    = true;
                }

                // Os grupos de aviso não são retornados
                if((isset($group['isParentGroup']) && $group['isParentGroup']) || !isset($group['restrict'])) {
                    unset($content['groups'][$key]);
                }
            }

            // Retorna a resposta JSON com os grupos obtidos
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Seta uma propriedade de um grupo
     *
     * @param string $sessionId = Id da sessão
     * @param string $groupId   = Id do grupo
     * @param string $property  = Propriedade
     * @param integer $active   = Ativo
     * 
     * @return array
     */
    public function setGroupProperty(string $sessionId, string $groupId, string $property, int $active): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Pega o chatId
            $groupId = $this->getWhatsappGroupId($groupId, false, true);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.setGroupProperty('$groupId', '$property', $active)")['result']['result']['value'];

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Seta a descrição de um grupo
     *
     * @param string $sessionId  = Id da sessão
     * @param string $groupId    = Id do grupo
     * @param string $subject    = Título do grupo
     * 
     * @return array
     */
    public function setGroupSubject(string $sessionId, string $groupId,  string $subject): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Pega o groupId
            $groupId = $this->getWhatsappGroupId($groupId, true, true);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta os grupos
            // A descrição do grupo
            if($page->isSocket) {
                $content = $page->sendActionSocket($sessionId, 'setGroupSubject', ['chatId' => $groupId, 'subject' => $subject]);
            } else {
                $content = $page->evaluate("window.WUAPI.setGroupSubject('$groupId', '$subject');")['result']['result']['value'];
            }

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Seta a descrição de um grupo
     *
     * @param string $sessionId   = Id da sessão
     * @param string $groupId     = Id do grupo
     * @param string $description = Descrição do grupo
     * 
     * @return array
     */
    public function setGroupDescription(string $sessionId, string $groupId, string $description): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Pega o groupId
            $groupId = $this->getWhatsappGroupId($groupId, true, true);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // A descrição do grupo
            if($page->isSocket) {
                $content = $page->sendActionSocket($sessionId, 'setGroupDescription', ['chatId' => $groupId, 'description' => $description]);
            } else {
                $content = $page->evaluate("window.WUAPI.setGroupDescription('$groupId', '$description');")['result']['result']['value'];
            }

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Busca o link de convite de um grupo
     *
     * @param string $sessionId = Id da sessão
     * @param string $groupId   = Id do grupo
     * 
     * @return array
     */
    public function getGroupInviteLink(string $sessionId, string $groupId): array
    {
        try {
            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');
            
            // Formata o groupId
            $groupId = $this->getWhatsappGroupId($groupId, true, true);

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.getGroupInviteLink('$groupId');")['result']['result']['value'];

            // Retorna a resposta JSON com os grupos obtidos
            return ['success' => $content['success'], 'message' => $content['message'], 'link' => $content['link']];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Busca informações de um grupo
     *
     * @param string $sessionId = Id da sessão
     * @param string $groupId   = Id do grupo
     * 
     * @return array
     */
    public function findGroupInfo(string $sessionId, string $groupId): array
    {
        try {
            // Pega o groupId
            $groupId = $this->getWhatsappGroupId($groupId, false, true);

            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Verifica a conexão
            $content = $page->evaluate("window.WUAPI.findGroupInfo('{$groupId}');")['result']['result']['value'];

            // Se tem o parentGroup então é uma comunidade.
            if(isset($content['metadata']['parentGroup'])) {
                $content['metadata']['isCommunity'] = true;
                $content['metadata']['id']          = str_replace("@g.us", "", ($content['metadata']['id'] ."_". $content['metadata']['parentGroup']));
            }

            // Retorna o resultado em JSON
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Promove participante de um grupo
     *
     * @param string $sessionId = Id da sessão
     * @param string $groupId   = Id do grupo
     * @param string $number    = Número do participante
     * 
     * @return array
     */
    public function promoteParticipant(string $sessionId, string $groupId, string $number): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Verifica se é comunidade
            $isCommunity = (strpos($groupId, '_') !== false) ? 1 : 0;

            // Pega o groupId/number
            [$groupId, $number] = [$this->getWhatsappGroupId($groupId, true, true), $number];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.promoteParticipants('$groupId', '$number', $isCommunity);")['result']['result']['value'];

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Despromove participante de um grupo
     *
     * @param string $sessionId = Id da sessão
     * @param string $groupId   = Id do grupo
     * @param string $number    = Número do participante
     * 
     * @return array
     */
    public function demoteParticipant(string $sessionId, string $groupId, string $number): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Verifica se é comunidade
            $isCommunity = (strpos($groupId, '_') !== false) ? 1 : 0;

            // Pega o groupId/number
            [$groupId, $number] = [$this->getWhatsappGroupId($groupId, true, true), $number];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.demoteParticipants('$groupId', '$number', $isCommunity);")['result']['result']['value'];

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Adiciona um participante a um grupo
     *
     * @param string $sessionId = Id da sessão
     * @param string $groupId   = Id do grupo
     * @param string $number    = Número do participante
     * 
     * @return array
     */
    public function addParticipant(string $sessionId, string $groupId, string $number): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Pega o groupId/number
            [$groupId, $number] = [$this->getWhatsappGroupId($groupId, false, true), $number];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.addParticipant('$groupId', '$number');")['result']['result']['value'];

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Remove um participante a um grupo
     *
     * @param string $sessionId = Id da sessão
     * @param string $groupId   = Id do grupo
     * @param string $number    = Número do participante
     * 
     * @return array
     */
    public function removeParticipant(string $sessionId, string $groupId, string $number): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Verifica se é comunidade
            $isCommunity = (strpos($groupId, '_') !== false) ? 1 : 0;

            // Pega o groupId/number
            [$groupId, $number] = [$this->getWhatsappGroupId($groupId, true, true), $number];

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.removeParticipant('$groupId', '$number', $isCommunity);")['result']['result']['value'];

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Altera a foto de um grupo
     *
     * @param string $sessionId = Id da sessão
     * @param string $groupId   = Id do grupo
     * @param string $path      = Caminho da imagem
     * 
     * @return array
     */
    public function changeGroupPhoto(string $sessionId, string $groupId, string $path): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Pega o groupId
            $groupId = $this->getWhatsappGroupId($groupId, true, true);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Pega o nome do arquivo e caso não exista, baixa o arquivo
            $fileName = $this->downloadFileAndSet($path);

            // Verifica a conexão
            $content = $page->evaluate("window.WUAPI.checkConnection();")['result']['result']['value'];

            // Versão do via navegador
            if(!isset($content['isSocket']) || isset($content['isSocket']) && !$content['isSocket']) {
                // Adiciona o input file no DOM
                [$backendNodeId, $nameFileInput] = $this->addInputFile($page);

                // Seta o arquivo no input
                $page->setFileInput($backendNodeId, "/storage/$fileName");

                // Executa o script no navegador
                $content = $page->evaluate("window.WUAPI.changeGroupPhoto(\"$groupId\", \"[data-$nameFileInput]\");")['result']['result']['value'];

                // Deleta a variável temporária e o input file
                $page->evaluate("window.WUAPI.removeInputFile('$nameFileInput');");
            }
            // Versão do via socket
            else {
                $content = $page->evaluate("window.WUAPI.changeGroupPhoto(\"$groupId\", \"/storage/$fileName\");")['result']['result']['value'];
            }

            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }

    /**
     * Busca informações de um grupo a partir de um inviteCode
     *
     * @param string $sessionId  = Id da sessão
     * @param string $inviteCode = InviteCode
     * 
     * @return array
     */
    public function getGroupInfoFromInviteCode(string $sessionId, string $inviteCode): array
    {
        try {
            // Seta um tempo de espera
            sleep($this->getRamdomSleep($this->sleepTime));

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI');

            // Seta os grupos
            $content = $page->evaluate("window.WUAPI.getGroupInfoFromInviteCode('$inviteCode');")['result']['result']['value'];

            // Coloca o serialized
            $content['owner'] = ($content['owner']['_serialized'] ?? $content['owner'] ?? $content['descOwner']['_serialized'] ?? null);

            // Retorna a resposta JSON com a mensagem de sucesso
            return $content;
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
