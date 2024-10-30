<?php

namespace App\Console\Commands;

use App\Models\Instance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RecoveryInstancesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:recovery-instances-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recupera as instâncias que estão com problemas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Verifica se o processo está ativo
        $instances = Instance::where(['connected' => true])->pluck('session_id')->toArray();

        // Caminho base.
        $basePath = base_path();
        
        // Log de informação.
        // Log::channel('daily')->info('Resolvendo problemas de permissão');

        foreach ($instances as $sessionId) {
            shell_exec("$basePath/scripts-sh/recovery_instance.sh $sessionId");

            shell_exec("chmod -R 777 $basePath/chrome-sessions/{$sessionId}/userdata/");
        }
    }
}
