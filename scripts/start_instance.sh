#!/bin/bash

# Variáveis
SESSION_ID=$1
PORT=$2
DEST_DIR="/root/chrome-sessions/$SESSION_ID"
WUAPI_DIR="/root/wuapi/chrome-sessions/$SESSION_ID"

# Verifica se os argumentos SESSION_ID e PORT foram passados
if [ -z "$1" ] || [ -z "$2" ]; then
  echo "Uso: $0 SESSION_ID PORT"
  exit 1
fi

# Verifica se o container com o SESSION_ID exato já está em execução
if docker ps --filter "name=^/${SESSION_ID}$" --format '{{.Names}}' | grep -q "^${SESSION_ID}$"; then
  echo "O container com SESSION_ID=$SESSION_ID já está em execução."
  exit 0
fi

# Remove a pasta existente se não for um repositório Git válido
if [ -d "$DEST_DIR" ] && [ ! -d "$DEST_DIR/.git" ]; then
  echo "Diretório existente, mas não é um repositório Git. Removendo..."
  rm -rf "$DEST_DIR"
fi

# Exclui a pasta existente
if [ -d "$DEST_DIR" ]; then
  # Entra no diretório e inicia o Docker Compose
  cd "$DEST_DIR" || exit
  git pull && docker compose build && docker compose up -d
else 
  # Clona o repositório para o diretório especificado
  git clone git@github.com:wendelulhoa/docker-chrome-wuapi.git "$DEST_DIR"
  cd "$DEST_DIR"
  git checkout beta-proxy

  # Remove o arquivo de configuração do Docker
  rm -rf ~/.docker/config.json

  # Cria o arquivo .env com SESSION_ID e PORT no diretório clonado
  echo "SESSION_ID=$SESSION_ID" > "$DEST_DIR/.env"
  echo "PORT=$PORT" >> "$DEST_DIR/.env"
  
  mkdir -p "$DEST_DIR/userdata"
  chmod -R 777 "$DEST_DIR/userdata"

  # cria a pasta onde vai ficar o .env
  mkdir -p $WUAPI_DIR
  chmod -R 777 $WUAPI_DIR

  # Adiciona na wuapi tbm
  echo "SESSION_ID=$SESSION_ID" > "$WUAPI_DIR/.env"
  echo "PORT=$PORT" >> "$WUAPI_DIR/.env"

  # Entra no diretório e inicia o Docker Compose
  cd "$DEST_DIR" || exit
  docker compose build && docker compose up -d

  echo "Container iniciado com SESSION_ID=$SESSION_ID e PORT=$PORT"
fi

