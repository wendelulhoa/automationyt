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
}
