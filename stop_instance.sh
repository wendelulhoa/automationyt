#!/bin/bash

# Verifica se um PID foi passado como argumento
if [ -z "$1" ]; then
  echo "Uso: $0 <PID>"
  exit 1
fi

PID=$1

# Identificar o processo pai (PPID) e quem o disparou
echo "Detalhes do processo $PID:"
ps -o pid,ppid,cmd -p $PID

# Identificar processos dependentes (filhos)
echo "Processos dependentes (filhos) do PID $PID:"
ps --ppid $PID

# Finalizar os processos filhos (dependentes)
echo "Finalizando processos dependentes..."
pkill -TERM -P $PID

# Finalizar o processo principal
echo "Finalizando o processo principal $PID..."
kill -9 $PID

echo "Processo $PID e seus dependentes foram deletados."