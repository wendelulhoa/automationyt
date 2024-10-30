#!/bin/bash

# Verifica se os argumentos SESSION_ID e PORT foram passados
if [ -z "$1" ] || [ -z "$2" ]; then
  echo "Uso: $0 SESSION_ID PORT"
  exit 1
fi

# Define as variáveis SESSION_ID e PORT com base nos argumentos
SESSION_ID=$1
PORT=$2

# Define o diretório de destino usando a SESSION_ID
DEST_DIR="chrome-sessions/$SESSION_ID"

# Clona o repositório para o diretório especificado
git clone git@github.com:wendelulhoa/wuapi-docker-chromium.git "$DEST_DIR"

# Cria o arquivo .env com SESSION_ID e PORT no diretório clonado
echo "SESSION_ID=$SESSION_ID" > "$DEST_DIR/.env"
echo "PORT=$PORT" >> "$DEST_DIR/.env"

# Entra no diretório e inicia o Docker Compose
cd "$DEST_DIR" || exit
docker compose build && docker-compose up -d

echo "Container iniciado com SESSION_ID=$SESSION_ID e PORT=$PORT"
