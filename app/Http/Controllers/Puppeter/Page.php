<?php

namespace App\Http\Controllers\Puppeter;
use App\Http\Controllers\Puppeter\Websocketpuppeteer;

Class Page {

    /**
     * Construtor da classe
     *
     * @param string $urlSocket
     */
    public function __construct(private string $urlSocket, private string $targetId)
    {
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
     * Método que insere um script na página
     *
     * @param string $expression
     * 
     * @return array
     */
    public function evaluate(string $expression): array
    {
        
        return $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Runtime.evaluate',
            'params' => [
                'expression' => $expression,
                'returnByValue' => true,
                'awaitPromise' => true,
            ]
        ]);
    }

    /**
     * Método para recarregar a página
     *
     * @return array
     */
    public function reload()
    {
        return $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Page.reload',
            'params' => [
                'ignoreCache' => true // opcional, define se o cache deve ser ignorado
            ]
        ]);
    }

    /**
     * Método para capturar um screenshot da página
     *
     * @return array
     */
    public function screenShot()
    {
        return $this->connection()->connWebSocket( [
            'id' => 1,
            'method' => 'Page.captureScreenshot',
            'params' => [
                'format' => 'png',  // Formato da imagem: 'jpeg' ou 'png'
                'quality' => 100,   // Qualidade da imagem (para 'jpeg'), de 0 a 100
                'clip' => [         // Captura apenas uma área específica (opcional)
                    'x' => 0,
                    'y' => 0,
                    'width' => 1920,
                    'height' => 1080,
                    'scale' => 1
                ]
            ]
        ]);
    }

    /**
     * Método para navegar para uma nova URL na aba atual
     *
     * @param string $url
     * 
     * @return array
     */
    public function navigate(string $url)
    {
        $result = $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Page.navigate',
            'params' => [
                'url' => $url
            ]
        ]);

        // Aguarda a navegação
        sleep(2);

        return $result;
    }

    /**
     * Método para obter a URL atual da aba
     * 
     * @return string|null
     */
    public function getCurrentUrl()
    {
        // Anexa ao alvo especificado
        $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Target.attachToTarget',
            'params' => [
                'targetId' => $this->targetId,
                'flatten' => true
            ]
        ]);

        // Executa o script para obter a URL atual
        $response = $this->connection()->connWebSocket([
            'id' => 2,
            'method' => 'Runtime.evaluate',
            'params' => [
                'expression' => 'window.location.href',
                'returnByValue' => true
            ]
        ]);

        // Retorna a URL ou null se não puder ser obtida
        return $response['result']['result']['value'] ?? null;
    }

    public function querySelector(string $selector)
    {
        return $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'DOM.querySelector',
            'params' => [
                'nodeId' => 106, // Geralmente 1 representa o document nodeId
                'selector' => $selector,
            ],
        ]);
    }

    public function getDocument()
    {
        return $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'DOM.getDocument',
            'params' => [
                'depth' => -1,
                'pierce' => true
            ]
        ]);
    }

    /**
     * Seta o arquivo no input informado
     *
     * @param int $backendNodeId
     * @param string $path
     * 
     * @return array
     */
    public function setFileInput(int $backendNodeId, string $path)
    {
        // $nodeId = $this->querySelector($selector)['result']['nodeId'];

        return $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'DOM.setFileInputFiles',
            'params' => [
                'backendNodeId' => $backendNodeId,
                'files' => [$path]
            ]
        ]);
    }

    /**
     * Método para limpar o cache do navegador
     *
     * @param int $nodeId
     * 
     * @return array
     */
    public function clearCache() {
        $clearCache = $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Network.clearBrowserCache',
        ]);
        
        $clearCookies = $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Network.clearBrowserCookies',
        ]);
    }
}