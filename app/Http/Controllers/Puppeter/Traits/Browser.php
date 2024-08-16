<?php

namespace App\Http\Controllers\Puppeter\Traits;
use App\Http\Controllers\Puppeter\Websocketpuppeteer;
use Illuminate\Support\Facades\Http;

Class Browser {

    /**
     * URL do socket
     *
     * @var string
     */
    private string $urlSocket;

    /**
     * Porta do navegador
     *
     * @var integer
     */
    private int $port;

    /**
     * Construtor da classe
     *
     * @param string $sessionId
     */
    public function __construct(private string $sessionId)
    {
        // Define a URL do socket
        $this->urlSocket = $this->getUrlSocket();
    }

    /**
     * Método para obter a URL do socket
     *
     * @return string
     */
    public function getUrlSocket(): string 
    {
        // Pega o caminho do arquivo que contém a porta
        $pathPort = "../chrome-sessions/$this->sessionId/port.txt";

        // Cria os diretórios caso não existam
        if (!file_exists($pathPort)) {
            $this->start();
        }

        // Faz a requisição para obter a URL do socket
        $tries = 0;
        $response = null;
        while (true) {
            try {
                // Pega a porta do arquivo
                $this->port = file_get_contents($pathPort);

                // Faz a requisição para obter a URL do socket
                $response = Http::get("http://127.0.0.1:{$this->port}/json/version");
            } catch (\Throwable $th) {
                $this->start();
                sleep(1);
            }

            // Verifica se a resposta foi bem sucedida
            if($tries > 5) {
                break;
            }

            // Verifica se tentou mais de 3 vezes
            $tries++;
        }

        return $response->json()['webSocketDebuggerUrl'];
    }

    /**
     * Método para obter a conexão
     *
     * @return Websocketpuppeteer
     */
    public function connection(): Websocketpuppeteer
    {
        return new Websocketpuppeteer($this->urlSocket);
    }

    /**
     * Método para abrir uma nova aba
     *
     * @param string $url
     * 
     * @return Page
     */
    public function createPage(string $url): Page
    {
        $result = $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Target.createTarget',
            'params' => [
                'url' => $url
            ]
        ]);

        // Pega o ID da aba
        $targetId = $result['result']['targetId'];

        return (new Page("ws://127.0.0.1:{$this->port}/devtools/page/{$targetId}"));
    }

    /**
     * Inicia o navegador
     *
     * @param string $sessionId
     * @return boolean
     */
    public function start()
    {
        try {
            $pathData = "../chrome-sessions/{$this->sessionId}/userdata";
            $pathLogs = "../chrome-sessions/{$this->sessionId}/logs";
            $pathPids = "../chrome-sessions/{$this->sessionId}/pids";
            $pathPort = "../chrome-sessions/{$this->sessionId}/port.txt";
            $pathDisplay = "../chrome-sessions/{$this->sessionId}/display.txt";

            // Cria os diretórios caso não existam
            if (!file_exists($pathLogs)) {
                mkdir($pathLogs, 0777, true);
            }
            if (!file_exists($pathPids)) {
                mkdir($pathPids, 0777, true);
            }

            // Define a porta e o número do display base
            $basePort = 9224;
            $baseDisplay = 100;

            // Define uma porta e um número de display disponíveis
            $port = $this->getAvailablePort($basePort, $this->sessionId);
            $display = $this->getAvailableDisplay($baseDisplay, $this->sessionId);

            // Armazena a porta e o display em arquivos
            file_put_contents($pathPort, $port);
            file_put_contents($pathDisplay, $display);

            // Comando para iniciar o navegador
            $command = "nohup xvfb-run --server-args=\"-screen 0 1920x1080x24 -ac -nolisten tcp\" --server-num=$display google-chrome --disable-gpu --remote-debugging-port=$port --remote-allow-origins=* --user-data-dir=$pathData --no-sandbox > $pathLogs/chrome-{$this->sessionId}.log 2>&1 & echo $! > $pathPids/chrome-{$this->sessionId}.pid";

            // Caso tenha um processo em execução, mata o processo
            if (file_exists("$pathPids/chrome-{$this->sessionId}.pid")) {
                $pid = file_get_contents("$pathPids/chrome-{$this->sessionId}.pid");
                exec("kill $pid");
                // Aguarde um momento para garantir que o processo foi finalizado
                sleep(2);
            }

            // Sobe o navegador
            exec($command);

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }


    /**
     * Metodo para pega a porta disponível
     *
     * @param integer $basePort
     * @param string $sessionId
     * 
     * @return integer
     */
    private function getAvailablePort(int $basePort, string $sessionId): int
    {
        return $basePort + intval(substr($sessionId, -1));
    }

    /**
     * Método para obter um display disponível
     *
     * @param integer $baseDisplay
     * @param string $sessionId
     * @return integer
     */
    private function getAvailableDisplay(int $baseDisplay, string $sessionId): int
    {
        return $baseDisplay + intval(substr($sessionId, -1));
    }
}