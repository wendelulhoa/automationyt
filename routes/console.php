<?php

use App\Console\Commands\CheckInstancesCommand;
use App\Console\Commands\RecoveryInstancesCommand;
use App\Console\Commands\RemoveFilesSendCommand;
use App\Console\Commands\SendWebookCommand;
use Illuminate\Support\Facades\Schedule;

// Registra os comando de console
Schedule::command(CheckInstancesCommand::class)->everyTwentySeconds()->withoutOverlapping();

// Remove os arquivos desnecessarios
Schedule::command(RemoveFilesSendCommand::class)->everyFiveMinutes()->withoutOverlapping();

// Envia os webhooks a cada 5s
Schedule::command(SendWebookCommand::class)->everyFiveSeconds()->withoutOverlapping();

// Recupera as instâncias que estão com problemas
Schedule::command(RecoveryInstancesCommand::class)->everyFiveSeconds()->withoutOverlapping();
