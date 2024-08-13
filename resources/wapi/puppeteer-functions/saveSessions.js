import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const sessionsFilePath = path.join(__dirname, 'sessions.json');

export async function saveSession(sessionid, wsEndpoint) {
    let sessions = {};
    if (fs.existsSync(sessionsFilePath)) {
        try {
            sessions = JSON.parse(fs.readFileSync(sessionsFilePath));
        } catch (error) {
            console.error("Erro ao ler o arquivo de sessões:", error);
        }
    }
    sessions[sessionid] = wsEndpoint;

    fs.writeFileSync(sessionsFilePath, JSON.stringify(sessions, null, 2));
}

export async function loadSession(sessionid) {
    try {
        if (fs.existsSync(sessionsFilePath)) {
            const sessions = JSON.parse(fs.readFileSync(sessionsFilePath));
            return sessions[sessionid];
        }
    } catch (error) {
        console.error("Erro ao ler o arquivo de sessões:", error);
    }
    return null;
}
