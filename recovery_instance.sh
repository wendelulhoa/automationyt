#!/bin/bash
SESSION_ID=$1

LOG_FILE="/var/www/html/wuapi/public/chrome-sessions/$SESSION_ID/logs/chrome-$SESSION_ID.log"  # Substitua pelo caminho real do seu arquivo de log

# Verifica se o log contém o erro
if grep -q -e "Failed to open LevelDB database from" -e "Failed to write the temporary index file" "$LOG_FILE"; then
    # Atualiza a pasta Default com o backup
    chmod -R 777 /var/www/html/wuapi/public/chrome-sessions/{$sessionId}/
    chmod -R 777 /var/www/html/wuapi/public/chrome-sessions/{$sessionId}/userdata/
    chmod -R 777 /var/www/html/wuapi/public/chrome-sessions/{$sessionId}/userdata/Default/
    
    echo "Resolvido problema de permissão."
else
    echo "Nenhum erro encontrado no log."
fi
