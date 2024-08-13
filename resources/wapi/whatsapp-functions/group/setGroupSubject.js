import { getPage } from "../../puppeteer-functions/browser.js";

// Busca todos os grupos
export async function setGroupSubject(sessionId, groupId, subject) {
    try {
        const page = await getPage(sessionId);
        
        // Busca os grupos
        const response = await page.evaluate(async (groupId, subject) => {
            return await window.WAPIWU.setGroupSubject(groupId, subject);
        }, groupId, subject);

        return response;
    } catch (error) {
        return {success: false, 'message': 'Erro ao setar o título', error: error};
    }
}