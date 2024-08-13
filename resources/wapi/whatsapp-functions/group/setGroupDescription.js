import { getPage } from "../../puppeteer-functions/browser.js";

// Busca todos os grupos
export async function setGroupDescription(sessionId, groupId, description) {
    try {
        const page = await getPage(sessionId);
        
        // Busca os grupos
        const response = await page.evaluate(async (groupId, description) => {
            return await window.WAPIWU.setGroupDescription(groupId, description);
        }, groupId, description);

        return response;
    } catch (error) {
        return {success: false, 'message': 'Erro ao setar a descrição', error: error};
    }
}