# Definir WORKING_DIR como o diretório de trabalho atual
WORKING_DIR=$(pwd)

# Definir SCRIPT_PATH como o diretório onde o script está localizado
SCRIPT_PATH="$WORKING_DIR/scripts-py"

# Cria o volume de storage
docker volume create storage-wuapi

# Cria uma nova rede da wuapi
docker network create redewuapi

#  Instala o node
curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Configura o js do server de controle de instâncias
cd /root/wuapi/scripts && npm install

# Configura o pm2 para rodar o server de controle de instâncias
npm install -g pm2
pm2 stop instance-controller
pm2 delete instance-controller
pm2 start server.js --name instance-controller

# Cria uma nova pasta de instâncias
mkdir -p /root/chrome-sessions
cd  && chmod -R 777 /root/chrome-sessions