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
     * URL dos containers de fora
     *
     * @var string
     */
    private string $url = 'host.docker.internal';

    /**
     * Porta do navegador
     *
     * @var integer
     */
    private int|null $port;

    /**
     * Portas em uso
     *
     * @var array
     */
    private array $portsInUse = [];

    /**
     * Construtor da classe
     *
     * @param string $sessionId
     */
    public function __construct(private string $sessionId)
    {
        // Define a URL do socket
        $this->port      = null;
        $this->portsInUse = $this->getPortsInUse();
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
        $basePath = base_path('chrome-sessions');

        // Pega o caminho do arquivo que contém a porta
        $pathPort = "$basePath/{$this->sessionId}/.env";

        // Faz a requisição para obter a URL do socket
        $tries = 0;
        $response = null;

        // Cria os diretórios caso não existam
        if (!file_exists($pathPort)) {
            $this->start();
        }

        // Só entra se for vazio
        if(empty($this->port)) {
            // Carrega apenas as variáveis sem sobrescrever o .env principal do Laravel
            $vars = $this->getEnvInstance("$basePath/{$this->sessionId}/.env");
    
            // Pega a porta do arquivo
            $this->port = $vars['PORT'];
        }

        while (true) {
            try {
                // Faz a requisição para obter a URL do socket
                $response = Http::get("{$this->url}:{$this->port}/json/version");
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

        return new Page("{$this->url}:{$this->port}/devtools/page/{$targetId}", $targetId);
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
                $pages[] = new Page("{$this->url}:{$this->port}/devtools/page/{$value['targetId']}", $value['targetId']);
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
            // Caminho base.
            $pathNewSession = base_path("sessions-configs/new_sessions");

            // Define uma porta e um número de display disponíveis
            if(empty($this->port)) {
                $port = $this->getAvailablePort();
                $this->port = $port;

                // Exclui o cache de start
                cache()->forget("{$this->sessionId}-startsession");
            }

            // Cadastra para subir a instância
            $newSession = [
                'port' => $this->port,
                'session_id' => $this->sessionId
            ];

            // Caso não tenha o cache faz o reload
            $initSession = true;
            if(cache()->has("{$this->sessionId}-startsession")) {
                while($initSession) {
                    $initSession = cache()->has("{$this->sessionId}-startsession");
                    sleep(2);
                }

                // retorna true ao finalizar a inicialização
                return true;
            }

            // Seta para criar a instância
            file_put_contents("$pathNewSession/{$this->sessionId}.json", json_encode($newSession));
            
            // Adiciona o cache para impedir de ficar fzd reload.
            cache()->put("{$this->sessionId}-startsession", "{$this->sessionId}-startsession", now()->addMinutes(2));

            // Caso dê sucesso é pq já subiu
            for ($i=0; $i <= 30; $i++) { 
                try {
                    // Faz a requisição para obter a URL do socket
                    Http::get("{$this->url}:{$this->port}/json/version");
                    break;
                } catch (\Throwable $th) {}

                // Espera 1s
                sleep(1);
            }
            
            // Exclui o cache de start
            cache()->forget("{$this->sessionId}-startsession");

            return true;
        } catch (\Throwable $th) {
            cache()->forget("{$this->sessionId}-startsession");
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
     * Busca as variáveis de ambiente de uma instância
     *
     * @param string $envPath
     * 
     * @return array
     */
    private function getEnvInstance($envPath)
    {
        // Lista de variaveis da instância
        $vars = [];

        // Verifica se o arquivo existe antes de tentar carregá-lo
        if (file_exists($envPath)) {
            // Abre o arquivo .env e lê seu conteúdo
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            // Itera sobre as linhas do arquivo .env
            foreach ($lines as $line) {
                // Ignora comentários
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }
                
                // Divide a linha em chave e valor
                list($key, $value) = explode('=', $line, 2);
                
                // Adiciona as variaveis
                $vars[$key] = $value;
            }
        }

        return $vars;
    }

    /**
     * Busca as portas em uso
     *
     * @return array
     */
    private function getPortsInUse(): array
    {
        $directoryPath = base_path('chrome-sessions');
        $ports = [];

        // Percorre o diretório verificando as portas em uso
        if (is_dir($directoryPath)) {
            // Lista das sessões
            $subfolders = scandir($directoryPath);
            foreach ($subfolders as $folder) {
                if ($folder !== '.' && $folder !== '..' && is_dir($directoryPath . '/' . $folder)) {
                    $ports[$folder] = $this->getEnvInstance("$directoryPath/$folder/.env")['PORT'] ?? null;
                }
            }
        }

        return $ports;
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
        return in_array($port, $this->portsInUse);
    }
}