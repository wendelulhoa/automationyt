# #!/bin/bash
# docker compose build && docker compose down && docker compose up -d

# # Reinicia o apache
# systemctl restart apache2

# Configura o pm2 para rodar o server de controle de instâncias
pm2 stop instance-controller
pm2 delete instance-controller
pm2 start ./scripts/server.js --name instance-controller