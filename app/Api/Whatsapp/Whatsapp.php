<?php

namespace App\Api\Whatsapp;

use App\Http\Controllers\Puppeter\Puppeteer;
use App\Traits\UtilWhatsapp;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Instance;
use LaravelQRCode\Facades\QRCode;
use Illuminate\Support\Facades\File;

class Whatsapp {
    use UtilWhatsapp;

    /**
     * Busca o qrcode
     *
     * @param string $sessionId
     * @return string
     */
    public function getQrcode(string $sessionId)
    {
        try {
            // Seta um novo proxy
            $this->setNewProxy($sessionId);

            // Cria uma nova página e navega até a URL
            $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WUAPI', false);

            // Pega o qrcode
            $body = $page->evaluate("window.WUAPI.getQrCode();");

            // Grava o que foi gerado
            $this->setLog("Gerou qrcode: {$sessionId}", [$body]);

            // Pega o conteúdo do qrCode
            $content = $body['result']['result']['value'];

            // Adiciona o prefixo base64 correto, incluindo o tipo MIME
            cache()->put("generate-qrcode-$sessionId", "generate-qrcode-$sessionId", now()->addMinutes(1));

            // Caso venha vazio da restart no container
            if(empty($content['qrCode'])) {
                // Grava o erro ao gerar qr code
                Log::channel('daily')->error("Qrcode vazio: {$sessionId}", [$body]);

                // Se der erro da restart no qrcode.
                $this->restartSession($sessionId);
            }

            $qrCode = null;
            if($content['success']) {
                // Caminho completo para o arquivo
                $fullPath = public_path("$sessionId-qrcode.svg");

                // Cria ou atualiza a instância
                Instance::initInstance(['session_id' => $sessionId, 'newconnection' => true]);

                // Seta como nova conexão
                cache()->put("newconnection-{$sessionId}", "newconnection-{$sessionId}", now()->addMinutes(30));

                // Gera o QR code em SVG
                $qrCode = QRCode::text(trim($content['qrCode']))
                        ->setSize(6)
                        ->setMargin(2)
                        ->setOutfile($fullPath)
                        ->svg();
                
                // Lê o conteúdo do arquivo
                $fileContent = File::get($fullPath);
    
                // Obtém o tipo MIME do arquivo
                $mimeType = File::mimeType($fullPath);
    
                // Codifica o conteúdo do arquivo para base64
                $base64Content = base64_encode($fileContent);
    
                // Cria o Data URI
                $qrCode = 'data:' . $mimeType . ';base64,' . $base64Content;
    
                // Exclui o arquivo
                unlink($fullPath);
            }

            return ['qrCode' => $qrCode, 'message' => $content['message'] ?? $content];
        } catch (\Throwable $th) {
            // Se der erro da restart no qrcode.
            $this->restartSession($sessionId);

            // Grava o erro ao gerar qr code
            $this->setLog("Erro ao gerar qrcode: {$sessionId} {$th->getMessage()}", [$body ?? []]);

            return ['qrCode' => null, 'error' => $th->getMessage()];
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
            Log::channel('daily')->info($log, $data);
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}