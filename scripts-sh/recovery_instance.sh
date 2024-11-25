#!/bin/bash

# Caminho da pasta base
BASE_DIR="/root/chrome-sessions"

# Verifica se a pasta existe
if [ -d "$BASE_DIR" ]; then
    # Percorre cada subpasta dentro do diretório base
    for sessionId in "$BASE_DIR"/*; do
        # Garante que é um diretório
        if [ -d "$sessionId" ]; then
            echo "Configurando permissões para $sessionId..."
            chmod -R 777 "$sessionId/userdata/"
            chmod -R 777 "$sessionId/userdata/Default/"
        fi
    done
    echo "Permissões aplicadas a todas as sessões."
else
    echo "A pasta $BASE_DIR não foi encontrada."
fi
