<?php

use App\Http\Controllers\WhatsappController;
use App\Http\Controllers\YoutubeController;
use App\Http\Middleware\VerifyInstanceToken;
use App\Http\Middleware\VerifyServerToken;
use App\Http\Middleware\CheckConnection;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/{sessionId}', 'middleware' => [VerifyServerToken::class, CheckConnection::class]], function() {
    // Rotas de inicialização
    Route::post('/start-session', [YoutubeController::class, 'startSession'])->name('automationyt.startsession');
    Route::get('/restartsession', [YoutubeController::class, 'restartInstance'])->name('automationyt.restartsession');
    Route::delete('/disconnect', [YoutubeController::class, 'disconnect'])->name('automationyt.disconnect');
    
    Route::group(['prefix' => '', 'middleware' => [VerifyInstanceToken::class]], function() {
        Route::post('/navigate', [YoutubeController::class, 'navigate'])->name('automationyt.navigate');
        Route::get('/screenshot', [YoutubeController::class, 'screenShot'])->name('automationyt.screenshot');
    });

});
