import { getPage } from "../../puppeteer-functions/browser.js";
import fs from 'fs'; // Importar o módulo fs padrão
import path from 'path';
import mime from 'mime-types';
import dotenv from 'dotenv';
import { fileURLToPath } from 'url';
// Carregar variáveis de ambiente do arquivo .env
dotenv.config();

// Função de utilidade para pausar a execução
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

// Busca todos os grupos
export async function sendFile(sessionId, chatId, caption, filename) {
    try {
        const page = await getPage(sessionId);

        // Caminho para o arquivo local
        const mimeType = mime.lookup(filename) || 'application/octet-stream';  // Determina o MIME type com base no nome do arquivo

        try {
            // Caminho relativo
            const relativePath = `/var/www/html/wapiwuphp/storage/app/${filename}`;

            // Caminho absoluto
            const filePath = path.join('', relativePath);
            console.log(filePath);

            // Lê o conteúdo do arquivo como base64
            const fileContent = await fs.promises.readFile(filePath, { encoding: 'base64' });

            // Transforma em um objeto File no contexto da página
            await page.evaluate((fileContent, filename, mimeType) => {
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
                window.fileSend = new File([blob], filename, { type: mimeType });
            }, fileContent, filename, mimeType);

            // Envia o arquivo
            var response = await page.evaluate(async (chatId, filename, caption) => {
                return await window.WAPIWU.sendFile(chatId, caption);
            }, chatId, filename, caption);

            return {success: response.success, message: response.message, response: response};
        } catch (error) {
            return {success: false, message: 'Erro ao enviar o arquivo', error: error.message};
        }
    } catch (error) {
        console.log(error);
        return {success: false, message: 'Erro ao enviar o arquivo', error: error};
    }
}