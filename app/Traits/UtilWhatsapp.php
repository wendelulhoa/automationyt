<?php

namespace App\Traits;

use App\Http\Controllers\Puppeter\Page;
use App\Http\Controllers\Puppeter\Puppeteer;
use App\Models\Filesend;
use finfo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Support\Facades\Cache;
use App\Models\Instance;
use Illuminate\Support\Facades\Redis;
use App\Api\Group\GroupWhatsapp;
use Carbon\Carbon;
use App\Models\Leavemember;

trait UtilWhatsapp
{
    /**
     * URL dos containers de fora
     *
     * @var string
     */
    private string $urlInstance = 'host.docker.internal';

    /**
     * Função para baixar um arquivo e setar na pasta storage
     *
     * @param string $path
     * 
     * @return string
     */
    public function downloadFileAndSet(string $path): string
    {
        // Verificar se o arquivo já foi enviado anteriormente
        $fileSend = Filesend::where('hash', md5($path))->first();
        $fileName = $fileSend->path ?? null;
        
        // Verifica se o arquivo existe
        if(!file_exists("/storage/$fileName")) {
            Filesend::where('hash', md5($path))->delete();
            $fileName = null;
        }

        // Verificar se o arquivo já foi enviado anteriormente
        if(empty($fileSend) || empty($fileName)) {
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

            // Salva o arquivo na raiz do container
            file_put_contents("/storage/$fileName", $fileContent);

            // Define as permissões para 777
            chmod("/storage/$fileName", 0777);

            // Faz a converção para sempre ficar no padrão do whatsapp.
            if(in_array($extension, ['mp3', 'ogg', 'mpeg', 'opus'])) {
                // Converte para o formato m4a para depois renomear para ogg
                $command = "ffmpeg -i " . escapeshellarg("/storage/$fileName") . " -b:a 192k " . escapeshellarg("/storage/$randomFileName.mp3");

                // Executa o comando
                exec($command, $output, $returnCode);

                // Remove o arquivo original
                unlink("/storage/$fileName");

                $fileName = "$randomFileName.mp3";

                // Define as permissões para 777
                chmod("/storage/$fileName", 0777);
            }

            Filesend::create([
                'path' => $fileName,
                'hash' => md5($path),
                'type' => $mimeType,
                'forget_in' => now()->addMinutes(120)
            ]);
        }

        return $fileName;
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
        $page->evaluate("window.WUAPI.addInputFile('$randomNameVar');");
        $document = $page->getDocument();

        // Pega o body 
        $body = ($document['result']['root']['children'][1]['children'][1] ?? $document['result']['root']['children'][0]['children'][1]);
        
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

    /**
     * Função para obter a extensão do arquivo com base no mimetype
     *
     * @param string $mimeType
     * 
     * @return string
     */
    public function getExtensionFromMimeType(string $mimeType)
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
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'video/quicktime' => 'mov',
            'audio/mpeg' => 'mpeg',
        ];

        return $mimeTypes[$mimeType] ?? 'bin';
    }

    /**
     * Pega o id do grupo conforme a regra.
     *
     * @param string|array $whatsappGroupId
     * @param boolean $getChildren
     * @param boolean $setJid
     * 
     * @return string|array
     */
    function getWhatsappGroupId($whatsappGroupId, bool $getChildren = false, bool $setJid = false)
    {
        // Busca o id do grupo conforme a regra.
        $fn_getId = function($id) use ($getChildren, $setJid) {
            // Caso tenha o "_" é comunidade
            $id = str_replace('@g.us', '', $id);
            $id = strpos($id, '_') !== false ? explode('_', $id) : $id;
        
            // Caso seja array pega o pai ou o filho
            if(is_array($id)) {
                $id = $getChildren ? $id[1] : $id[0];
            }

            // Caso seja c.us é por que é número de telefone
            if(strpos($id, '@c.us') !== false) {
                $id = str_replace('-', '', $id);
                $id = $this->removeNineDigit($id);
            }

            return $setJid && !(strpos($id, '@c.us') !== false) ? "$id@g.us" : $id;
        };

        // Convertendo o id para o formato correto
        $whatsappGroupId = $fn_getId($whatsappGroupId);

        return $whatsappGroupId;
    }


    /**
     * Remover o nono digito de um telefone caso ele seja o número 9
     *
     * @param string $fullNumber = número do telefone
     * @return string
     */
    function removeNineDigit($fullNumber, $jid = true)
    {
        $fullNumber  = str_replace('@c.us', '', $fullNumber);
        $codeCountry = substr($fullNumber, 0, 2);
        $codeDdd     = substr($fullNumber, 2, 2);
        $nineDigit   = substr($fullNumber, 4, 1);
        $number      = substr($fullNumber, 5);
        
        if ($nineDigit == '9' && $codeCountry == '55' && strlen($fullNumber) == 13) {
            $fullNumber = $codeCountry . $codeDdd . $number;
        }

        return ($codeCountry == '55' ? $fullNumber : $fullNumber) . ($jid ? "@c.us" : "");
    }

    /**
      * Para a execução do container
      *
      * @param string $sessionId
      *
      * @return void
      */
    public function stopInstance(string $sessionId): void
    {
        try {
            // Desconecta do whatsapp
            $content = Http::post('http://172.17.0.1:8080/stop-instance', [
                'session_id' => $sessionId
            ])->json();
        } catch (\Throwable $th) {
            Log::channel('daily')->error("Sessão: {$sessionId}, Erro finalizar o container: ". $th->getMessage());
        }
    }

    /**
    * Coloca para fazer o reinicio do container
    *
    * @param string $sessionId

    * @return void
    */
    public function restartSession($sessionId) 
    {
        try {
            // Reinicia a sessão
            $content = Http::post('http://172.17.0.1:8080/restart-instance', [
                'session_id' => $sessionId
            ])->json();
        } catch (\Throwable $th) {
            Log::channel('daily')->error("Sessão: {$sessionId}, Erro ao reiniciar a sessão: {$th->getMessage()}");
        }
    }

    /**
     * Verifica a conexão da instância
     *
     * @return bool
     */
    public function checkConnection($sessionId)
    {
        try {
            // Verifica se está conectada.
            $connected = Cache::remember("instance-{$sessionId}", now()->addMinutes(5), function () use($sessionId) {
                return Instance::where(['connected' => true, 'session_id' => $sessionId])->exists();
            }); 
            
            return $connected;
        } catch (\Throwable $th) {
            return true;
        }
    }

    /**
     * Incremeta na criação de grupos
     *
     * @param string $cacheKey
     * 
     * @return void
     */
    public function incrementGroupCount(string $cacheKey): void
    {
        $expiresAt = now()->addHours(6)->timestamp; // Expira no final do dia

        // Incrementa o contador ou inicializa com expiração
        if (Redis::exists($cacheKey)) {
            Redis::incr($cacheKey);
        } else {
            Redis::set($cacheKey, 1, 'EX', $expiresAt - time());
        }
    }

    /**
     * Verifica se está habilitado para criar grupos
     *
     * @param string $cacheKey
     * @return bool
     */
    public function checkCreateGroup(string $cacheKey) 
    {
        try {
            // Obtém o contador atual do Redis
            $currentCount = Redis::get($cacheKey);
            $enabled      = true;

            // Verifica se atingiu o limite diário
            if ($currentCount && $currentCount >= 20) {
                $enabled = false;
            }

            // Incrementa o contador ou inicializa com expiração até o fim do dia
            $this->incrementGroupCount($cacheKey);

            return $enabled;
        } catch (\Throwable $th) {
            return true;
        }
    }

    /**
     * Pega um sleep random
     *
     * @param integer $sleep
     * 
     * @return integer
     */
    public function getRamdomSleep(int $sleep = 1): int
    {
        try {
            $sleep = rand($sleep, $sleep * 2);

            return $sleep;
        } catch (\Throwable $th) {
            return $sleep;
        }
    }

    /**
     * Remove as mensagens 
     *
     * @param Page $page
     * @param string $sessionId
     * @return void
     */
    public function removeMessages(Page $page, string $sessionId): void
    {
        try {
            // Deleta as mensagens
            $page->evaluate("window.WUAPI.fetchAndDeleteMessagesFromIndexedDB();");

            // Seta o log
            Log::channel('whatsapp-removemessages')->info("Removeu as mensagens da instância: {$sessionId}");
        } catch (\Throwable $th) {
            Log::channel('whatsapp-removemessages')->error("Erro ao remover mensagens: {$th->getMessage()}, Instância: {$sessionId}");
        }
    }

    /**
     * Busca a porcentagem da CPU
     *
     * @return float
     */
    public function getPercentageCpu()
    {
        try {
            // Pega a porcentagem do CPU
            $output = shell_exec("top -bn1 | grep 'Cpu(s)'");
            $cpuUsage = 0;

            // Processa o resultado
            preg_match('/(\d+\.\d+)\s*id/', $output, $matches);

            // Calcula o uso da CPU (100% menos a porcentagem de idle)
            if (isset($matches[1])) {
                $cpuUsage = 100 - (float)$matches[1];
            }

            return $cpuUsage;
        } catch (\Throwable $th) {
            // Retorna 0 em caso de falha
            return 0;
        }
    }

    /**
     * Verifica se o arquivo existe
     *
     * @param Page $page
     * @param string $fileName
     * 
     * @return array
     */
    public function checkExistFile(Page $page, string $fileName): array
    {
        try {
            // Pega se tem o arquivo em backup
            $body = $page->evaluate("window.WUAPI.existsFile('$fileName');");
            $content = $body['result']['result']['value'];

            return ['success' => $content['success'], 'message' => $content['message']];
        } catch (\Throwable $th) {
            return ['success' => false, 'message' => $th->getMessage()];
        }
    }
}
