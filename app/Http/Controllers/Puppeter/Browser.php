<?php

namespace App\Http\Controllers\Puppeter;
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
     * Busca sempre a primeira página aberta
     *
     * @return Page
     */
    public function getFirstPage(): Page
    {
        // Pega as páginas abertas
        $pages = $this->getPages();

        // Verifica se existe alguma página aberta
        if(count($pages) > 0) {
            $page = $pages[0];
        } 
        // Cria a página caso não exista nenhuma aberta
        else {
            $page = $this->createPage('');
        }

        return $page;
    }

    /**
     * Método para obter a URL do socket
     *
     * @return string
     */
    public function getUrlSocket(): string 
    {
        // Pega o caminho do arquivo que contém a porta
        $pathPort = "./chrome-sessions/$this->sessionId/port.txt";

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

        return new Page("ws://127.0.0.1:{$this->port}/devtools/page/{$targetId}", $targetId);
    }

    /**
     * Busca as abas abertas
     *
     * @return array
     */
    public function getPages(): array
    {
        $result = $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Target.getTargets'
        ]);

        // Pega as abas abertas
        $pages = [];
        foreach ($result['result']['targetInfos'] as $value) {
            if($value['type'] == 'page' && !in_array($value['url'], ['chrome://privacy-sandbox-dialog/notice', 'about:blank', 'chrome-untrusted://new-tab-page/one-google-bar?paramsencoded="'])) {
                $pages[] = new Page("ws://127.0.0.1:{$this->port}/devtools/page/{$value['targetId']}", $value['targetId']);
            }
        }

        return $pages;
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
            exec("chmod -R 777 ./chrome-sessions/");
            $pathData = "./chrome-sessions/{$this->sessionId}/userdata";
            $pathLogs = "./chrome-sessions/{$this->sessionId}/logs";
            $pathPids = "./chrome-sessions/{$this->sessionId}/pids";
            $pathPort = "./chrome-sessions/{$this->sessionId}/port.txt";

            // Cria os diretórios caso não existam
            if (!file_exists($pathLogs)) {
                mkdir($pathLogs, 0777, true);
            }
            if (!file_exists($pathPids)) {
                mkdir($pathPids, 0777, true);
            }

            // Define a porta e o número do display base
            $basePort = 9224;

            // Define uma porta e um número de display disponíveis
            $port = $this->getAvailablePort($basePort, $this->sessionId);

            // Armazena a porta e o display em arquivos
            file_put_contents($pathPort, $port);

            exec("chmod -R 777 ./chrome-sessions/");
            exec("chmod -R 777 ./chrome-sessions/{$this->sessionId}/");
            exec("chown -R root:root ./chrome-sessions/");
            exec("chmod -R 777 /root/.local");
  
            // Comando para iniciar o navegador
            $command = "
                nohup google-chrome --headless \
                    --disable-gpu \
                    --user-agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36' \
                    --remote-debugging-port=$port \
                    --disable-dev-shm-usage \
                    --remote-allow-origins=* \
                    --user-data-dir=$pathData \
                    --no-sandbox \
                    --lang=pt-BR \
                    > $pathLogs/chrome-{$this->sessionId}.log 2>&1 & \
                    echo $! > $pathPids/chrome-{$this->sessionId}.pid
            ";

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
}