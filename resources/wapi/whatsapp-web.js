const puppeteer = require('puppeteer');
const path = require('path');

// Defina o caminho para armazenar o perfil do usuário
const userDataDir = path.join(__dirname, 'sessions/session01');

(async () => {
  const browser = await puppeteer.launch({
    headless: false, // Para ver o navegador em ação
    userDataDir: userDataDir
  });

  const page = await browser.newPage();
  await page.goto('https://web.whatsapp.com/');
  // Realize quaisquer ações necessárias no site

  //await browser.close();
})();
