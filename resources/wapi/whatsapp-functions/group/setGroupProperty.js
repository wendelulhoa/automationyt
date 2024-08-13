import { getPage } from "../../puppeteer-functions/browser.js";

// Busca todos os grupos
export async function setGroupProperty(sessionId, groupId) {
    try {
        const page = await getPage(sessionId);
        
        // Busca os grupos
        const response = await page.evaluate(async (groupId) => {
            return await window.WAPIWU.setGroupProperty(groupId);
        }, groupId);

        return response;
    } catch (error) {
        return {success: false, 'message': 'Erro ao buscar o link'};
    }
}