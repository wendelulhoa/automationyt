<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;

class WebsocketWhatsapp extends Controller
{
    // URL do servidor Node.js com Socket.IO
    private string $url = 'ws://localhost:8080';

    /**
     * Construtor da classe
     *
     * @param string $url
     * @param string $sessionId
     * @param string $fnAction
     * @param array $params
     */
    public function __construct(private string $sessionId, private string $fnAction, private array $params = [])
    {
    }

    /**
     * Conecta ao WebSocket
     *
     * @return array
     */
    public function connWebSocket(): array
    {
        try {
            // Criar um loop de eventos
            $loop = Loop::get();
            $connector = new Connector($loop);

            // Variável para armazenar o resultado
            $_response = null;
            $_error    = null;

            // Conectar ao WebSocket
            $connector($this->url)->then(function($conn) use (&$_response, &$loop) {
                // Enviar dados para o servidor Node.js
                $conn->send(json_encode(['sessionId' => $this->sessionId, 'action' => $this->fnAction, 'params' => $this->params]));

                $conn->on('message', function($msg) use($conn, &$_response, &$loop) {
                    $_response = json_decode($msg->getPayload(), true);
                    $conn->close();
                    $loop->stop(); // Para o loop após receber a resposta
                });

            }, function ($e) use (&$_error, &$loop) {
                $_error = $e->getMessage();
                $loop->stop(); // Para o loop em caso de erro
            });

            // Executa o loop até que ele seja interrompido
            $loop->run();

            // Verifica se houve um erro
            if ($_error) {
                throw new \Exception($_error);
            }

            return [
                'success'  => true,
                'response' => $_response,
                'error'    => null
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'error'   => $th->getMessage()
            ];
        }
    }
}
