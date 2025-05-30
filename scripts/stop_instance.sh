#!/bin/bash

# Variáveis
SESSION_ID=$1
DEST_DIR=$(dirname $(pwd))"/chrome-sessions/$SESSION_ID"
AUTOMATIONYT_DIR=$(pwd)"/chrome-sessions/$SESSION_ID"

# Verifica se os argumentos SESSION_ID foi passado
if [ -z "$1" ]; then
  echo "Uso: $0 SESSION_ID"
  exit 1
fi

# Exclui a pasta do chrome existente
if [ -d "$DEST_DIR" ]; then
  # Entra no diretório e inicia o Docker Compose
  cd "$DEST_DIR" || exit
  docker compose down --remove-orphans
  
  # Remove as pastas
  rm -rf "$AUTOMATIONYT_DIR"
  rm -rf "$DEST_DIR"

  # Remove o cache
  docker rmi "$SESSION_ID"-websocket
fi

