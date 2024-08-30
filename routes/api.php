<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WhatsappController;
use App\Http\Middleware\WaitRequestMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/{sessionId}', 'middleware' => [WaitRequestMiddleware::class]], function() {
    // Rotas gerais
    Route::get('/getqrcode', [WhatsappController::class, 'getQrcode'])->name('wapiwu.getqrcode');
    Route::get('/start-session', [WhatsappController::class, 'startSession'])->name('wapiwu.startsession');
    Route::get('/checkconnection', [WhatsappController::class, 'checkConnection'])->name('wapiwu.checkconnection');
    Route::get('/getphonenumber', [WhatsappController::class, 'getPhoneNumber'])->name('wapiwu.getphonenumber');

    // Rotas de grupos
    Route::group(['prefix' => 'group'], function() {
        // Criação de grupo
        Route::post('/create', [GroupController::class, 'createGroup'])->name('wapiwu.group.create');
        
        // Informações do grupo
        Route::post('/getallgroups', [GroupController::class, 'getAllGroups'])->name('wapiwu.group.getallgroups');
        Route::post('/findgroupinfo', [GroupController::class, 'findGroupInfo'])->name('wapiwu.group.findgroupinfo');
        Route::get('/group-invite-link/{groupId}', [GroupController::class, 'getGroupInviteLink'])->name('wapiwu.group.getgroupinvitelink');
       
        // Alterar dados do grupo
        Route::put('/setgroupsubject', [GroupController::class, 'setGroupSubject'])->name('wapiwu.group.setgroupsubject');
        Route::put('/setgroupdescription', [GroupController::class, 'setGroupDescription'])->name('wapiwu.group.setgroupdescription');
        Route::put('/setgroupproperty', [GroupController::class, 'setGroupProperty'])->name('wapiwu.group.setgroupproperty');
        Route::put('/changephoto', [GroupController::class, 'changeGroupPhoto'])->name('wapiwu.group.changegroupphoto');
        
        // Participantes Adicionar/Remover
        Route::post('/add-participant-group', [GroupController::class, 'addParticipant'])->name('wapiwu.group.addparticipant');
        Route::delete('/remove-participant-group', [GroupController::class, 'removeParticipant'])->name('wapiwu.group.removeparticipant');
        
        // Participantes promove/despromove
        Route::put('/demote-participant-group', [GroupController::class, 'demoteParticipant'])->name('wapiwu.group.demoteparticipant');
        Route::put('/promote-participant-group', [GroupController::class, 'promoteParticipant'])->name('wapiwu.group.promoteparticipant');
    });

    // Envio de mensagens
    Route::group(['prefix' => 'message'], function() {
        Route::any('/send-file', [MessageController::class, 'sendFile']);
        Route::post('/send-text', [MessageController::class, 'sendText']);
        Route::post('/send-linkpreview', [MessageController::class, 'sendLinkPreview']);
        Route::post('/send-poll', [MessageController::class, 'sendPoll']);
        Route::post('/send-vcard', [MessageController::class, 'sendVcard']);
    });
});
