# Obtém o caminho do diretório das sessões do chrome
CHROME_PATH=$(dirname $(pwd))

# Instala o node e npm
sudo apt install nodejs npm -y

# Configura o js do server de controle de instâncias
cd scripts && npm install

# Configura o pm2 para rodar o server de controle de instâncias
sudo npm install -g pm2
pm2 stop instance-controller
pm2 delete instance-controller
pm2 start server.js --name instance-controller

# Cria uma nova pasta de instâncias
mkdir -p $CHROME_PATH/chrome-sessions
chmod -R 777 $CHROME_PATH/chrome-sessions