<?php

namespace App\Console\Commands;

use App\Models\Instance;
use App\Traits\UtilWhatsapp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RebootInstancesCommand extends Command
{
    use UtilWhatsapp;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reboot-instances-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reinicia as instâncias que estão ativas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verifica se o processo está ativo
        $instances = Instance::where(['connected' => true])->get();

        // Reinicia as instâncias
        foreach ($instances as $instance) {
            // Extrai o nome da sessão
            $sessionId = $instance->session_id;
            
            try {
                // Reinicia a sessão das instâncias conectadas
                $this->restartSession($sessionId);
            } catch (\Throwable $th) {
                Log::channel('daily')->error('Erro ao reiniciar instância: ' . $instance->session_id . ' - ' . $th->getMessage());
            }
        }
    }
}
