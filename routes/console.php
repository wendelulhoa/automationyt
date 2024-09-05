<?php

use App\Console\Commands\RemoveFilesSendCommand;
use Illuminate\Support\Facades\Schedule;

// Registra os comando de console
// Schedule::command(CheckInstancesCommand::class)->everyFifteenSeconds();

Schedule::command(RemoveFilesSendCommand::class)->everySecond();
