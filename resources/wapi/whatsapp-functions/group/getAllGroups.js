import { getPage } from "../../puppeteer-functions/browser.js";

// Busca todos os grupos
export async function getAllGroups(sessionId) {
    try {
        const page = await getPage(sessionId);

        // Busca os grupos
        await page.evaluate(async () => {
            return  'a'
        }); 

        return {success: true, message: 'Grupos obtidos'};
        
        // Busca os grupos
        await page.evaluate(async () => {
            return  await window.WAPIWU.setGroups();
        }); 

        const response = await page.evaluate(async () => {
            return await window.WAPIWU.getAllGroups();
        });

        return response;
    } catch (error) {
        console.log(error)
        return {success: false, message: 'Erro ao buscar os grupos'};
    }
}