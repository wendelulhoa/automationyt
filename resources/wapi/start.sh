#!/bin/bash

# Função para verificar se a porta está em uso
is_port_in_use() {
  local port=$1
  if lsof -i:$port > /dev/null; then
    return 0
  else
    return 1
  fi
}

# Encontrar uma porta disponível
find_available_port() {
  local port=8080
  while is_port_in_use $port; do
    port=$((port + 1))
  done
  echo $port
}

# Encontrar uma porta disponível
PORT=$(find_available_port)

# Exportar a porta como variável de ambiente
export PORT

# Iniciar o servidor WebSocket com Puppeteer usando --no-sandbox
node websocket.js