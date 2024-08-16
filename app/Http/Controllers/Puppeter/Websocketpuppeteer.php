<?php

namespace App\Http\Controllers\Puppeter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;

class Websocketpuppeteer extends Controller
{
    /**
     * Construtor da classe
     *
     * @param string $url
     * @param string $sessionId
     */
    public function __construct(private string $url)
    {
    }

    /**
     * Conecta ao WebSocket
     *
     * @return array
     */
    public function connWebSocket(array $command): array
    {
        try {
            // Criar um loop de eventos
            $loop = Loop::get();
            $connector = new Connector($loop);

            // Variável para armazenar o resultado
            $_response = null;
            $_error    = null;

            // Conectar ao WebSocket
            $connector($this->url)->then(function($conn) use (&$_response, &$loop, $command) {
                // Envia o comando para o WebSocket
                $conn->send(json_encode($command));

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

            return $_response;
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'error'   => $th->getMessage()
            ];
        }
    }
}
