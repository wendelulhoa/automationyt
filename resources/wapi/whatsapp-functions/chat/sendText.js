import { getPage } from "../../puppeteer-functions/browser.js";

// Busca todos os grupos
export async function sendText(sessionId, chatId, text) {
    try {
        const page = await getPage(sessionId);

        // Busca os grupos
        const response = await page.evaluate(async (chatId, text) => {
            return await window.WAPIWU.sendTextMsgToChat(chatId, text);
        }, chatId, text);

        return response;
    } catch (error) {
        console.log(error)
        return response;
    }
}