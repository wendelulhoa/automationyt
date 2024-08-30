<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Puppeter\Puppeteer;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function sendWebhook(string $sessionId)
    {
        // Cria uma nova página e navega até a URL
        $page = (new Puppeteer)->init($sessionId, 'https://web.whatsapp.com', view('whatsapp-functions.injected-functions-minified')->render(), 'window.WAPIWU');

        // Seta os grupos
        $events = $page->evaluate("window.WAPIWU.webhookEvents")['result']['result']['value'];
        
        // Envia os eventos para o webhook
        foreach ($events as $id => $event) {
            // Deleta o evento
            $page->evaluate("delete window.WAPIWU.webhookEvents['$id']")['result']['result']['value'];
        }
        dd($events);

        return true;
    }
}
