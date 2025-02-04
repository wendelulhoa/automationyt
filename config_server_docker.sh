# Definir WORKING_DIR como o diretório de trabalho atual
WORKING_DIR=$(pwd)

# Definir SCRIPT_PATH como o diretório onde o script está localizado
SCRIPT_PATH="$WORKING_DIR/scripts-py"

# Cria o volume de storage
docker volume create storage-wuapi

# Cria uma nova rede da wuapi
docker network create redewuapi

echo "SCRIPT_PATH: $SCRIPT_PATH"
echo "WORKING_DIR: $WORKING_DIR"

# Criar o arquivo de serviço systemd
SERVICE_FILE_START_SESSION="/etc/systemd/system/start-instance-python.service"
SERVICE_FILE_STOP_SESSION="/etc/systemd/system/stop-instance-python.service"
SERVICE_FILE_RESTART_SESSION="/etc/systemd/system/restart-instance-python.service"
SERVICE_FILE_RECOVERY_SESSION="/etc/systemd/system/recovery-instance-python.service"

echo "Criando o arquivo de serviço systemd em $SERVICE_FILE= $SCRIPT_PATH..."

cat <<EOL | sudo tee $SERVICE_FILE_START_SESSION
[Unit]
Description=start-instance-python
After=network.target

[Service]
ExecStart=/usr/bin/python3 "$SCRIPT_PATH/startInstance.py"
WorkingDirectory=$WORKING_DIR
Restart=always
User=$(whoami)
Group=$(whoami)
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
EOL

cat <<EOL | sudo tee $SERVICE_FILE_STOP_SESSION
[Unit]
Description=stop-instance-python
After=network.target

[Service]
ExecStart=/usr/bin/python3 "$SCRIPT_PATH/stopInstance.py"
WorkingDirectory=$WORKING_DIR
Restart=always
User=$(whoami)
Group=$(whoami)
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
EOL

cat <<EOL | sudo tee $SERVICE_FILE_RESTART_SESSION
[Unit]
Description=restart-instance-python
After=network.target

[Service]
ExecStart=/usr/bin/python3 "$SCRIPT_PATH/restartInstance.py"
WorkingDirectory=$WORKING_DIR
Restart=always
User=$(whoami)
Group=$(whoami)
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
EOL

cat <<EOL | sudo tee $SERVICE_FILE_RECOVERY_SESSION
[Unit]
Description=recovery-instance-python
After=network.target

[Service]
ExecStart=/usr/bin/python3 "$SCRIPT_PATH/recoveryInstance.py"
WorkingDirectory=$WORKING_DIR
Restart=always
User=$(whoami)
Group=$(whoami)
Environment=PYTHONUNBUFFERED=1

[Install]
WantedBy=multi-user.target
EOL

# Habilitar e iniciar o serviço
echo "Habilitando e iniciando o serviço..."

# Recarregar os serviços do systemd
sudo systemctl daemon-reload

# Habilitar e iniciar os serviços
sudo systemctl enable start-instance-python.service
sudo systemctl start start-instance-python.service
sudo systemctl enable stop-instance-python.service
sudo systemctl start stop-instance-python.service
sudo systemctl enable restart-instance-python.service
sudo systemctl start restart-instance-python.service
sudo systemctl enable recovery-instance-python.service
sudo systemctl start recovery-instance-python.service

sudo systemctl disable start-instance-python.service
sudo systemctl disable stop-instance-python.service
sudo systemctl disable restart-instance-python.service
sudo systemctl disable recovery-instance-python.service
sudo systemctl daemon-reload

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