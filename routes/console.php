<?php

use App\Console\Commands\CheckInstancesCommand;
use App\Console\Commands\RemoveFilesSendCommand;
use App\Console\Commands\SendWebookCommand;
use Illuminate\Support\Facades\Schedule;

// Registra os comando de console
Schedule::command(CheckInstancesCommand::class)->everyFifteenSeconds();

// Remove os arquivos desnecessarios
Schedule::command(RemoveFilesSendCommand::class)->everyFiveSeconds();

// Envia os webhooks a cada 1 minuto
// Schedule::command(SendWebookCommand::class)->everyFiveSeconds();
