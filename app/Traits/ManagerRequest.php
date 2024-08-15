<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Redis;

trait ManagerRequest
{
    /**
     * Aguarda a request ser executada
     *
     * @return void
     */
    public function waitRequest()
    {
       // Verifica se a request ja foi executada
       $requestCurrent = json_decode(Redis::get('request-wapiwu'), true);

       if(!empty($requestCurrent) && $requestCurrent['run'] === true) {
           // Tempo inicial
           $startTime = time();

           // Verifica se tem job em execução.
           while (true) {
               // Verifica se a request já foi executada
               $requestCurrent = json_decode(Redis::get('request-wapiwu'), true);

               // Ao finalizar o outra request sai do loop
               if (!empty($requestCurrent) && !$requestCurrent['run'] || empty($requestCurrent)) {
                   break;
               }

               // Verifica o tempo atual
               $currentTime = time();

               // Verifica se passaram mais de 30 segundos
               if ($currentTime - $startTime > 30) {
                    // Finaliza a request
                    $this->finishRequest();

                    // Seta a request como não sendo executada
                    throw new Exception("Timeout: na request");

                    // Encerra o loop
                    break;
               }

               // Aguarde um pouco antes de verificar novamente
               usleep(500000);
           }
       }

       // Seta a request como sendo executada
       Redis::setex('request-wapiwu', 20, json_encode(['run' => true])); 
    }

    /**
     * Finaliza a request
     * 
     * @return void
     */
    public function finishRequest(): void
    {
        // Seta o job como não sendo executado
        Redis::setex('request-wapiwu', 20, json_encode(['run' => false]));
    }
}
