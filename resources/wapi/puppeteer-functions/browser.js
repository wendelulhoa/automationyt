import { saveSession, loadSession } from './saveSessions.js';
import path from 'path';
import { fileURLToPath } from 'url';
import puppeteer from 'puppeteer';
import { injectFunctions } from '../whatsapp-functions/wu-functions.js';
import fs from 'fs';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

export async function getPage(sessionId) {
    const userDataDir = path.join(__dirname, `sessions/${sessionId}`);
    let browser;
    
    // Verificar se o diretório de sessões existe, se não, criar
    if (!fs.existsSync(userDataDir)) {
        fs.mkdirSync(userDataDir, { recursive: true });
    }

    const wsEndpoint = await loadSession(sessionId);
    if (wsEndpoint) {
        try {
            browser = await puppeteer.connect({ browserWSEndpoint: wsEndpoint });
        } catch (error) {
            browser = await puppeteer.launch({
                headless: false,
                args: ['--no-sandbox', '--disable-setuid-sandbox'],
                product: 'chrome',
                protocol: 'webDriverBiDi',
                executablePath: process.env.PUPPETEER_EXECUTABLE_PATH,
                userDataDir: userDataDir
            });
            await saveSession(sessionId, browser.wsEndpoint());
        }
    } else {
        browser = await puppeteer.launch({
            headless: false,
            args: ['--no-sandbox', '--disable-setuid-sandbox'],
            product: 'chrome',
            protocol: 'webDriverBiDi',
            executablePath: process.env.PUPPETEER_EXECUTABLE_PATH,
            userDataDir: userDataDir
        });
        await saveSession(sessionId, browser.wsEndpoint());
    }

    // Recuperar todas as páginas abertas
    const pages = await browser.pages();
    let page;

    if (pages.length > 0) {
        // Se houver páginas abertas, use a primeira
        page = pages[0];
    } else {
        // Caso contrário, abra uma nova página
        page = await browser.newPage();
    }

    const url = await page.url();
    if (!url.includes('web.whatsapp.com')) {
        await page.goto('https://web.whatsapp.com');
    }

    // Injetar funções
    const isInjected = await page.evaluate(() => {
        return window.WAPIWU;
    });
    
    // Se as funções não foram injetadas, injete-as
    if(isInjected === undefined) {
        await injectFunctions(page, sessionId);
    }

    return page;
}