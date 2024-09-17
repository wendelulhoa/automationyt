<?php

namespace App\Traits;

use App\Http\Controllers\Puppeter\Page;
use App\Models\Filesend;
use finfo;
use Illuminate\Support\Str;

trait UtilWhatsapp
{
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

        if ($nineDigit == '9' && $codeCountry == '55') {
            $fullNumber = $codeCountry . $codeDdd . $number;
        }

        return ($codeCountry == '55' ? $fullNumber : $fullNumber) . ($jid ? "@c.us" : "");
    }
}
