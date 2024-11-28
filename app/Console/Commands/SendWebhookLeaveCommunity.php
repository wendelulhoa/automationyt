<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Api\Group\GroupWhatsapp;
use App\Models\Instance;
use App\Models\Leavemember;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Traits\UtilWhatsapp;

class SendWebhookLeaveCommunity extends Command
{
    use UtilWhatsapp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-webhook-leave-community';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pega as saídas de comunidades.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Busca as instâncias conectadas
        $instances = Instance::where(['connected' => true])->get();
        
        // Seta o log de inicio
        Log::channel('daily')->info("Começou o envio de webhook de saída comunidades.");

        foreach ($instances as $instance) {
            // Extrai o nome da sessão
            $sessionId = $instance->session_id;

            // Caso não for webhook não envia o webhook.
            if(!$instance->webhook || cache()->has("newconnection-{$sessionId}")) continue;

            // Envia o webhook da comunidade
            $this->sendWebhookCommunity($sessionId, true);
        }
    }
}
