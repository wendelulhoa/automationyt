<?php

use App\Http\Controllers\GroupController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\WhatsappController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => '/{sessionId}'], function() {
    Route::get('/getqrcode', [WhatsappController::class, 'getQrcode'])->name('wapiwu.getqrcode');

    Route::group(['prefix' => 'group'], function() {
        Route::post('/getallgroups', [GroupController::class, 'getAllGroups'])->name('wapiwu.group.getallgroups');
        Route::get('/group-invite-link/{groupId}', [GroupController::class, 'getGroupInviteLink'])->name('wapiwu.group.getgroupinvitelink');
        Route::post('/setgroupsubject', [GroupController::class, 'setGroupSubject'])->name('wapiwu.group.setgroupsubject');
        Route::post('/setgroupdescription', [GroupController::class, 'setGroupDescription'])->name('wapiwu.group.setgroupdescription');
    });

    Route::group(['prefix' => 'message'], function() {
        Route::any('/send-image', [MessageController::class, 'sendImage']);
        Route::post('/send-text', [MessageController::class, 'sendText']);
    });
    
    Route::get('/downloadfile/{filename}', [WhatsappController::class, 'downloadFile'])->name('wapiwu.downloadfile');
});
