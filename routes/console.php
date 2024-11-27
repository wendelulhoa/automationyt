<?php

use App\Console\Commands\CheckInstancesCommand;
use App\Console\Commands\RebootInstancesCommand;
use App\Console\Commands\RecoveryInstancesCommand;
use App\Console\Commands\RemoveFilesSendCommand;
use App\Console\Commands\RemoveMessagesCommand;
use App\Console\Commands\SendWebookCommand;
use Illuminate\Support\Facades\Schedule;

// Registra os comando de console
Schedule::command(CheckInstancesCommand::class)->everyFiveMinutes()->withoutOverlapping();

// Remove as mensagens do whatsapp por instância
Schedule::command(RemoveMessagesCommand::class)->everyFiveMinutes()->withoutOverlapping();

// Remove os arquivos desnecessarios
Schedule::command(RemoveFilesSendCommand::class)->everyFiveMinutes()->withoutOverlapping();

// Envia os webhooks a cada 5s
Schedule::command(SendWebookCommand::class)->everyFiveSeconds()->withoutOverlapping();

// Reinicia as instâncias
Schedule::command(RebootInstancesCommand::class)->dailyAt('02:00')->withoutOverlapping();
