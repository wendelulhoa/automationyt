import { getPage } from '../puppeteer-functions/browser.js';

// Função de utilidade para pausar a execução
function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

export async function getQrcode(sessionId) {
    try {
        const page = await getPage(sessionId);

        await page.goto('https://web.whatsapp.com/');
        
        // Verificar se a conexão foi estabelecida
        var statusConnection = await window.WAPIWU.checkConnection();

        // Obter o QR Code
        let qrCode = null;
        if(!statusConnection) {
            for (let index = 0; index < 20; index++) {
                qrCode = await page.evaluate(() => {
                    try {
                        var path = `//*/div/div[2]/div[3]/div[1]/div/div/div[2]/div`;
                        var elQrcode = document.evaluate(path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
    
                        return elQrcode.dataset.ref;
                    } catch (error) {
                        return null;
                    }
                });
                
                // Se o QR Code foi obtido, saia do loop
                if(qrCode) break;
    
                // Pausar a execução por 1 segundo
                await sleep(1000);
            }
        }

        return {success: (qrCode != null), qrCode: qrCode, message: statusConnection ? 'Conexão estabelecida' : 'QR Code obtido'};
    } catch (error) {
        return {success: false, error: error, qrCode: null, message: 'Erro ao obter o QR Code'};
    }
}