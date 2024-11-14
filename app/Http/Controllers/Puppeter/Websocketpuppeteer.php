<?php

namespace App\Http\Controllers\Puppeter;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class Websocketpuppeteer extends Controller
{

    //
    private string $url = '';

    /**
     * Construtor da classe
     *
     * @param string $url
     * @param string $sessionId
     */
    public function __construct(string $url)
    {
        $this->url = str_replace('ws://', 'http://', $url);
    }

    /**
     * Conecta ao WebSocket
     *
     * @return array
     */
    public function connWebSocket(array $command): array
    {
        try {
            $response = Http::timeout(120)->post($this->url, $command);
            return $response->json();
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'error'   => $th->getMessage(),
                'command' => $command ?? 'não tem command'
            ];
        }
    }
}
