#!/bin/bash

# Variáveis
SESSION_ID=$1
DEST_DIR="/root/chrome-sessions/$SESSION_ID"
WUAPI_DIR="/root/wuapi/chrome-sessions/$SESSION_ID"

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
  docker stop "$SESSION_ID" && docker rm "$SESSION_ID"
  rm -r "$WUAPI_DIR"
  rm -r "$DEST_DIR"
  docker rmi "$SESSION_ID"-chrome:latest
fi

