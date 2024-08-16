// Função de utilidade para pausar a execução
async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function getQrCode() {
    const maxWaitTime = 30000; // 30 segundos
    const interval = 1000; // 1 segundo
    let elapsedTime = 0;

    while (elapsedTime < maxWaitTime) {
        try {
            var path = `//*/div/div[2]/div[3]/div[1]/div/div/div[2]/div`;
            var elQrcode = document.evaluate(path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue;
            var qrCode = elQrcode ? elQrcode.getAttribute('data-ref') : null;

            if (qrCode !== null) {
                return { success: true, qrCode: qrCode, message: 'QR gerado com sucesso' };
            }

            await sleep(interval);
            elapsedTime += interval;
        } catch (error) {
            return { success: false, error: error.message, qrCode: null, 'aq': 'a' };
        }
    }

    return { success: false, error: 'QR code não encontrado em 30 segundos', qrCode: null };
}