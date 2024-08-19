import { WebSocketServer } from 'ws';
import execFunction from './functions-whatsapp.js';

const wss = new WebSocketServer({ port: 8080 });

wss.on('connection', (ws) => {
    console.log('Novo cliente conectado');

    ws.on('message', async (message) => {        
        var request = JSON.parse(message);
        const sessionId = request.sessionId;
        const action    = request.action;
        const params    = (request.params || {});

        // Busca o qrcode
        const body = await execFunction(action, sessionId, params);

        ws.send(JSON.stringify(body));
        try {
        } catch (error) {
            console.log(error)
            ws.send(JSON.stringify({ success: false, message: 'Erro ao processar a requisição', error: error }));
        }
        
    });

    ws.on('close', () => {
        console.log('Cliente desconectado');
    });

    // ws.send('Bem-vindo ao servidor WebSocket!');
});

console.log('Servidor WebSocket rodando na porta 8080');