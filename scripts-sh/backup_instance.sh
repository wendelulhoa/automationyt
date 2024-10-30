#!/bin/bash
SESSION_ID=$1

# Caminho para a pasta Default
DEFAULT_DIR="/var/www/html/wuapi/public/chrome-sessions/$SESSION_ID/userdata/Default"
BACKUP_DIR="/var/www/html/wuapi/public/chrome-sessions/$SESSION_ID/userdata/Default_backup"

# Verifica se a pasta de backup já existe
if [ ! -d "$BACKUP_DIR" ]; then
    # Cria a pasta de backup
    cp -r "$DEFAULT_DIR" "$BACKUP_DIR"
    echo "Backup da pasta Default criado em $BACKUP_DIR."
else
    echo "A pasta de backup já existe. Nenhuma ação foi realizada."
fi
