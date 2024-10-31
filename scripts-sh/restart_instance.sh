#!/bin/bash

# Variáveis
SESSION_ID=$1
DEST_DIR="/root/chrome-sessions/$SESSION_ID"

# Verifica se os argumentos SESSION_ID e PORT foram passados
if [ -z "$1" ]; then
  echo "Uso: $0 SESSION_ID PORT"
  exit 1
fi

# Exclui a pasta do chrome existente
if [ -d "$DEST_DIR" ]; then
  # Entra no diretório e inicia o Docker Compose
  cd "$DEST_DIR" || exit
  docker compose down && docker compose build && docker compose up -d
fi

