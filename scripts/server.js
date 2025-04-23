const express = require('express');
const http = require('http');
const axios = require('axios');
const bodyParser = require('body-parser');
const { exec } = require('child_process');
const path = require('path');

const app = express();
const server = http.createServer(app);

// Configurando o body-parser para aceitar payloads maiores
app.use(bodyParser.json({ limit: '300mb' })); // Ajuste o tamanho conforme necessário
app.use(bodyParser.urlencoded({ limit: '300mb', extended: true })); // Ajuste o tamanho conforme necessário

// Middleware para JSON
app.use(express.json());

// Função para verificar se o Chrome está acessível
async function checkChrome(port) {
    try {
        const response = await axios.get(`http://127.0.0.1:${port}/json/version`);
        return response.data;
    } catch (error) {
        if (error.code === 'ECONNREFUSED') {
            return null; // Retorna null se a conexão for recusada
        }
    }
}

// Aguarda um tempo
async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Sobe uma nova instância
app.post('/start-instance', async (req, res) => {
    try {
        // pega a porta e o nome da sessão
        const {port, session_id} = req.body;

        // Sobe a instância
        const scriptPath = path.join(__dirname, 'start_instance.sh');
        exec(`${scriptPath} ${session_id} ${port} &`);

        // espera 30s
        for (let index = 0; index < 30; index++) {
            // Verifica se o Chrome está acessível
            var response = await checkChrome(port);

            // Caso venha algumas informações, a conexão foi estabelecida
            if(response != null) break;

            // Aguarda 1s para tentar novamente
            await sleep(1000);
        }

        // Log
        console.log(`Subiu a instância: ${session_id}`)

        res.json({success: true, response: response || null});
    } catch (error) {
        res.status(500).json({ error: error.message, response: response});
    }
});

// Reinicia uma instância
app.post('/restart-instance', async (req, res) => {
    try {
        // pega o nome da sessão
        const {session_id} = req.body;

        // Sobe a instância
        const scriptPath = path.join(__dirname, 'restart_instance.sh');
        exec(`${scriptPath} ${session_id} &`);

        // Log
        console.log(`Reiniciou a instância: ${session_id}`)

        res.json({success: true, response: null});
    } catch (error) {
        res.status(500).json({ error: error.message, response: null});
    }
});

// Desconecta uma instância
app.post('/stop-instance', async (req, res) => {
    try {
        // pega o nome da sessão
        const {session_id} = req.body;

        // Sobe a instância
        const scriptPath = path.join(__dirname, 'stop_instance.sh');
        exec(`${scriptPath} ${session_id} &`);

        // Log
        console.log(`desconectou a instância: ${session_id}`)

        res.json({success: true, response: null});
    } catch (error) {
        res.status(500).json({ error: error.message, response: null});
    }
});

// Inicia o servidor
server.listen(8080, () => {
    console.log(`Server is running on port ${8080}`);
});