<?php

namespace App\Console\Commands;

use App\Models\Filesend;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemoveFilesSendCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-files-send-command';

    /**
     * The console command description.
     *
     * @var string
     */ 
    protected $description = 'Remove os arquivos enviados 2 horas atrás';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Define o tempo limite para remoção (2 horas atrás)
        $timeLimit = Carbon::now()->subHours(2);

        // Busca os registros que atendem à condição
        $filesToRemove = Filesend::where('forget_in', '<=', $timeLimit)->get();
        $filesToRemove = Filesend::all();
        Log::info("Arquivos encontrados: {$filesToRemove->count()} aqui");
        // Remove os arquivos e os registros do banco de dados
        foreach ($filesToRemove as $file) {

            // Remove o arquivo do sistema de arquivos
            if (file_exists("/storage/{$file->path}")) {
                // Adiciona que será removido
                Log::info("Removendo arquivo: {$file->path}");

                // Remove o arquivo do sistema de arquivos
                unlink("/storage/{$file->path}");

                // Remove o registro do banco de dados
                $file->delete();
            }
        }

        // Exibe uma mensagem de sucesso
        $this->info('Arquivos enviados há mais de 2 horas foram removidos com sucesso.');
    }
}
