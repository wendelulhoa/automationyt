<?php

namespace App\Http\Controllers\Puppeter\Traits;
use App\Http\Controllers\Puppeter\Websocketpuppeteer;

Class Page {

    /**
     * Construtor da classe
     *
     * @param string $urlSocket
     */
    public function __construct(private string $urlSocket)
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
     * @return void
     */
    public function evaluate(string $expression) 
    {
        return $this->connection()->connWebSocket([
            'id' => 1,
            'method' => 'Runtime.evaluate',
            'params' => [
                'expression' => $expression,
                'returnByValue' => true,
                'awaitPromise' => true
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
}