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
        // Define o caminho do diretório público
        $publicPath = public_path('');

        // Pega o caminho do arquivo que contém a porta
        $pathPort = "$publicPath/chrome-sessions/$this->sessionId/port.txt";

        // Cria os diretórios caso não existam
        if (!file_exists($pathPort)) {
            $this->start();
        }

        // Faz a requisição para obter a URL do socket
        $tries = 0;
        $response = null;
        
        // Pega a porta do arquivo
        $this->port = file_get_contents($pathPort);

        while (true) {
            try {
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
            // Define o caminho do diretório público
            $publicPath = public_path('chrome-sessions');

            exec("chmod -R 777 $publicPath/");
            $pathData = "$publicPath/{$this->sessionId}/userdata";
            $pathLogs = "$publicPath/{$this->sessionId}/logs";
            $pathPids = "$publicPath/{$this->sessionId}/pids";
            $pathPort = "$publicPath/{$this->sessionId}/port.txt";

            // Cria os diretórios caso não existam
            if (!file_exists($pathLogs)) {
                mkdir($pathLogs, 0777, true);
            }
            if (!file_exists($pathPids)) {
                mkdir($pathPids, 0777, true);
            }

            // Define uma porta e um número de display disponíveis
            $port = $this->getAvailablePort();
            $this->port = $port;

            // Armazena a porta e o display em arquivos
            file_put_contents($pathPort, $port);

            exec("chmod -R 777 $publicPath/");
            exec("chmod -R 777 $publicPath/{$this->sessionId}/");
            exec("chown -R root:root $publicPath/");
            exec("chmod -R 777 /root/.local");
            
            // Pega a versão instalada no momento
            $versionChrome = explode(" ", shell_exec("google-chrome --version"))[2];

            // Remove os dados do usuário que antes estavam em execução
            exec("rm -rf $publicPath/{$this->sessionId}/userdata/SingletonLock");
            exec("rm -rf $publicPath/{$this->sessionId}/userdata/SingletonSocket");
            exec("rm -rf $publicPath/{$this->sessionId}/userdata/SingletonCookie");

            // Comando para iniciar o navegador
            $command = "
               nohup google-chrome --headless \
                --disable-gpu \
                --disable-software-rasterizer \
                --disable-cache \
                --user-agent='Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/$versionChrome Safari/537.36' \
                --remote-debugging-port=$port \
                --disable-dev-shm-usage \
                --remote-allow-origins=* \
                --user-data-dir='$pathData' \
                --no-sandbox \
                --lang=pt-BR \
                --no-first-run \
                --window-size=1920,1080 \
                --disable-features=Translate,BackForwardCache,MediaRouter,OptimizationHints,UseDBus \
                --disable-background-networking \
                --disable-domain-reliability \
                --disable-renderer-backgrounding \
                --disable-background-timer-throttling \
                --disable-client-side-phishing-detection \
                --disable-component-extensions-with-background-pages \
                --disable-breakpad \
                --metrics-recording-only \
                --disable-gl-drawing-for-tests \
                --disable-web-security \
                > '$pathLogs/chrome-{$this->sessionId}.log' 2>&1 & \
                echo $! > '$pathPids/chrome-{$this->sessionId}.pid'
            ";

            // Caso tenha um processo em execução, mata o processo
            if (file_exists("$pathPids/chrome-{$this->sessionId}.pid")) {
                $pid = file_get_contents("$pathPids/chrome-{$this->sessionId}.pid");

                // Verifica se o processo está em execução
                $psOutput = shell_exec("ps -p $pid");
                $psArray = explode(" ", $psOutput);
                $psArray = array_values(array_filter($psArray, function($value) {
                    return $value !== '';
                }));

                if (isset($psArray[4]) && $psArray[4] == $pid) {
                    // Mata o processo se estiver em execução
                    exec("kill $pid");
                    // Aguarde um momento para garantir que o processo foi finalizado
                    sleep(1);
                }
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
    private function getAvailablePort(): int
    {
        do {
            $port = random_int(9224, 9499);
        } while ($this->isPortInUse($port));

        return $port;
    }

    /**
     * Verifica se a porta está em uso
     *
     * @param integer $port
     * 
     * @return boolean
     */
    private function isPortInUse(int $port): bool
    {
        $connection = @fsockopen('localhost', $port);
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        return false;
    }
}