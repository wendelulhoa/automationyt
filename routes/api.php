<?php

use App\Http\Controllers\CommunityController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WhatsappController;
use App\Http\Middleware\ControlRequestMessage;
use App\Http\Middleware\VerifyInstanceToken;
use App\Http\Middleware\VerifyServerToken;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/{sessionId}', 'middleware' => [VerifyServerToken::class]], function() {
    // Rotas de inicialização
    Route::get('/getqrcode', [WhatsappController::class, 'getQrcode'])->name('wapiwu.getqrcode');
    Route::post('/start-session', [WhatsappController::class, 'startSession'])->name('wapiwu.startsession');
    Route::get('/checkconnection', [WhatsappController::class, 'checkConnection'])->name('wapiwu.checkconnection');
    
    Route::group(['prefix' => '', 'middleware' => [VerifyInstanceToken::class]], function() {
        // Rotas gerais que precisam de autenticação
        Route::get('/getphonenumber', [WhatsappController::class, 'getPhoneNumber'])->name('wapiwu.getphonenumber');
        Route::delete('/disconnect', [WhatsappController::class, 'disconnect'])->name('wapiwu.disconnect');
        Route::post('/checknumber', [WhatsappController::class, 'checkNumber'])->name('wapiwu.checknumber');
        Route::get('/screenshot', [WhatsappController::class, 'screenShot'])->name('wapiwu.screenshot');

        // Rotas de grupos
        Route::group(['prefix' => 'group'], function() {
            // Criação de grupo
            Route::post('/create', [GroupController::class, 'createGroup'])->name('wapiwu.group.create');
            
            // Informações do grupo
            Route::post('/getallgroups', [GroupController::class, 'getAllGroups'])->name('wapiwu.group.getallgroups');
            Route::post('/findgroupinfo', [GroupController::class, 'findGroupInfo'])->name('wapiwu.group.findgroupinfo');
            Route::get('/group-invite-link/{groupId}', [GroupController::class, 'getGroupInviteLink'])->name('wapiwu.group.getgroupinvitelink');
            Route::post('/group-info-from-invite-link', [GroupController::class, 'getGroupInfoFromInviteCode'])->name('wapiwu.group.getgroupinfofrominvitecode');
            
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

        // Rotas de comunidades
        Route::group(['prefix' => 'community'], function() {
            Route::post('/create', [CommunityController::class, 'createCommunity'])->name('wapiwu.community.createcommunity');
        });
    
        // Envio de mensagens
        Route::group(['prefix' => 'message', 'middleware' => [ControlRequestMessage::class]], function() {
            Route::any('/send-file', [MessageController::class, 'sendFile'])->name('wapiwu.message.sendfile');
            Route::post('/send-text', [MessageController::class, 'sendText'])->name('wapiwu.message.sendtext');
            Route::post('/send-linkpreview', [MessageController::class, 'sendLinkPreview'])->name('wapiwu.message.sendlinkpreview');
            Route::post('/send-poll', [MessageController::class, 'sendPoll'])->name('wapiwu.message.sendpoll');
            Route::post('/send-vcard', [MessageController::class, 'sendVcard'])->name('wapiwu.message.sendvcard');
            Route::post('/send-audio', [MessageController::class, 'sendAudio'])->name('wapiwu.message.sendaudio');
            Route::post('/send-event', [MessageController::class, 'sendEvent'])->name('wapiwu.message.sendevent');
            Route::delete('/delete', [MessageController::class, 'deleteMessage'])->name('wapiwu.message.deletemessage');
        });
    });

});
