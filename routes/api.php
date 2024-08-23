<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WhatsappController;
use App\Http\Middleware\WaitRequestMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => '/{sessionId}', 'middleware' => [WaitRequestMiddleware::class]], function() {
    Route::get('/getqrcode', [WhatsappController::class, 'getQrcode'])->name('wapiwu.getqrcode');
    Route::get('/checkconnection', [WhatsappController::class, 'checkConnection'])->name('wapiwu.checkconnection');
    Route::get('/getphonenumber', [WhatsappController::class, 'getPhoneNumber'])->name('wapiwu.getphonenumber');

    Route::group(['prefix' => 'group'], function() {
        Route::post('/create', [GroupController::class, 'createGroup'])->name('wapiwu.group.create');
        Route::post('/getallgroups', [GroupController::class, 'getAllGroups'])->name('wapiwu.group.getallgroups');
        Route::post('/findgroupinfo', [GroupController::class, 'findGroupInfo'])->name('wapiwu.group.findgroupinfo');
        Route::get('/group-invite-link/{groupId}', [GroupController::class, 'getGroupInviteLink'])->name('wapiwu.group.getgroupinvitelink');
        Route::put('/setgroupsubject', [GroupController::class, 'setGroupSubject'])->name('wapiwu.group.setgroupsubject');
        Route::put('/setgroupdescription', [GroupController::class, 'setGroupDescription'])->name('wapiwu.group.setgroupdescription');
    });

    Route::group(['prefix' => 'message'], function() {
        Route::any('/send-image', [MessageController::class, 'sendImage']);
        Route::post('/send-text', [MessageController::class, 'sendText']);
        Route::post('/send-poll', [MessageController::class, 'sendPoll']);
    });
});
