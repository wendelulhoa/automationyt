<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Ratchet\Client\Connector;
use React\EventLoop\Loop;

class GroupController extends Controller
{
    public function getAllGroups(string $sessionId) 
    {
        try {
            // Criar um loop de eventos
            $loop = Loop::get();
            $connector = new Connector($loop);

            // URL do servidor Node.js com Socket.IO
            $url = 'ws://localhost:8080';

            // Variável para armazenar o resultado
            $groups = null;
            $error = null;

            // Conectar ao WebSocket
            $connector($url)->then(function($conn) use ($sessionId, &$groups, &$loop) {
                // Enviar dados para o servidor Node.js
                $conn->send(json_encode(['sessionId' => $sessionId, 'action' => 'getAllGroups']));
                // $conn->send(json_encode(['sessionId' => $sessionId, 'action' => 'getQrcode']));

                $conn->on('message', function($msg) use($conn, &$groups, &$loop) {
                    $groups = json_decode($msg->getPayload(), true);
                    $conn->close();
                    $loop->stop(); // Para o loop após receber a resposta
                });

            }, function ($e) use (&$error, &$loop) {
                echo "Não foi possível conectar: {$e->getMessage()}\n";
                $error = $e->getMessage();
                $loop->stop(); // Para o loop em caso de erro
            });

            // Executa o loop até que ele seja interrompido
            $loop->run();

            // Verifica se houve um erro
            if ($error) {
                throw new \Exception($error);
            }

            // Retorna a resposta JSON com os grupos obtidos
            return response()->json(['success' => true, 'message' => 'Grupos obtidos com sucesso.', 'groups' => $groups]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Outros métodos...
}
