import { getPage } from "../../puppeteer-functions/browser.js";
import fs from 'fs'; // Importar o módulo fs padrão
import path from 'path';
import { fileURLToPath } from 'url';
import mime from 'mime-types';
import http from 'http';
import https from 'https';
import dotenv from 'dotenv';
import axios from 'axios';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Carregar variáveis de ambiente do arquivo .env
dotenv.config();

// Função de utilidade para pausar a execução
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Função para baixar o arquivo usando http ou https
async function downloadFile(url, filePath) {
    try {
        const response = await axios({
            method: 'get',
            url: url,
            responseType: 'stream'
        });

        const file = fs.createWriteStream(filePath);
        response.data.pipe(file);

        file.on('finish', () => {
            file.close(() => {
                fs.readFile(filePath, (err, data) => {
                    if (err) {
                        console.error('Erro ao ler o arquivo:', err);
                        return;
                    }
                    const base64Data = data.toString('base64');
                });
            });
        });
    } catch (error) {
        console.error(`Erro ao baixar o arquivo: ${error.message}`);
    }
}


// Busca todos os grupos
export async function sendFile(sessionId, chatId, caption, filename) {
    try {
        const page = await getPage(sessionId);

        // Caminho para o arquivo local
        const mimeType = mime.lookup(filename) || 'application/octet-stream';  // Determina o MIME type com base no nome do arquivo

        try {
            // Exemplo de uso da função downloadFile
            const url = `${process.env.API_ENDPOINT}/dev-session5180/downloadfile/${filename}`;
            const filePath = path.join(__dirname, 'files', `video.mp4`);
            const auxFileName = 'video.mp4';

            // Baixa o arquivo
            await downloadFile(url, filePath);

            // Pausa a execução por 0.5 segundo
            await sleep(1000);

            // Lê o conteúdo do arquivo como base64
            const fileContent = await fs.promises.readFile(filePath, { encoding: 'base64' });

            // Transforma em um objeto File no contexto da página
            await page.evaluate((fileContent, auxFileName, mimeType) => {
                // Converte a string base64 em um ArrayBuffer
                const binaryString = window.atob(fileContent);
                const binaryLen = binaryString.length;
                const bytes = new Uint8Array(binaryLen);

                for (let i = 0; i < binaryLen; i++) {
                    bytes[i] = binaryString.charCodeAt(i);
                }

                // Cria um Blob a partir do ArrayBuffer
                const blob = new Blob([bytes], { type: mimeType });

                // Cria um objeto File a partir do Blob
                window.fileSend = new File([blob], auxFileName, { type: mimeType });
            }, fileContent, auxFileName, mimeType);

            // Envia o arquivo
            const response = await page.evaluate(async (chatId, filename, caption) => {
                return await window.WAPI.sendFile(chatId, caption);
            }, chatId, filename, caption);

        } catch (error) {
            return {success: true, message: 'Erro ao enviar o arquivo', error: error};
        }

        return {success: true, message: 'Arquivo enviado com sucesso', response: response};
    } catch (error) {
        return {success: false, message: 'Erro ao enviar o arquivo', error: error};
    }
}