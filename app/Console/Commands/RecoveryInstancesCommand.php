<?php

namespace App\Console\Commands;

use App\Models\Instance;
use Illuminate\Console\Command;

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

        // Define o caminho do diretório público
        $publicPath = public_path('chrome-sessions');

        foreach ($instances as $sessionId) {
            shell_exec("$basePath/recovery_instance.sh $sessionId");

            shell_exec("chmod -R 777 $publicPath/{$sessionId}/userdata/");
        }
    }
}
