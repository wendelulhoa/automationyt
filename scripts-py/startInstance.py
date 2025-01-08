import json
import os
import time
import subprocess

# Caminho da pasta onde os arquivos JSON estão armazenados
data_folder = "sessions-configs/new_sessions"

# Função para obter o uso da CPU
def get_cpu_usage():
    try:
        # Executa o comando top para obter informações da CPU
        output = subprocess.check_output("top -bn1 | grep 'Cpu(s)'", shell=True).decode('utf-8')
        # Extrai o valor de idle (tempo ocioso)
        idle = float(output.split(",")[3].split()[0])
        # Calcula o uso da CPU (100% - tempo ocioso)
        return 100.0 - idle
    except Exception as e:
        print(f"Erro ao obter uso da CPU: {e}")
        return 100.0  # Retorna 100% como fallback para evitar processamento

# Função para processar os arquivos JSON e chamar o script shell
def process_json_files():
    for filename in os.listdir(data_folder):
        if filename.endswith(".json"):
            file_path = os.path.join(data_folder, filename)
            
            # Lê o conteúdo do arquivo JSON
            with open(file_path, 'r') as file:
                data = json.load(file)
                session_id = data.get("session_id")
                port = data.get("port")
                
                # Verifica se session_id e port foram encontrados
                if session_id and port:
                    # Verifica o uso da CPU antes de iniciar o processamento
                    while get_cpu_usage() >= 90.0:
                        print("Uso da CPU acima de 90%, aguardando...")
                        time.sleep(1)  # Aguarda 5 segundos antes de verificar novamente

                    # Executa o script shell com os argumentos
                    try:
                        subprocess.run(["scripts-sh/start_instance.sh", session_id, str(port)], check=True)
                        # Exclui o arquivo JSON após iniciar a instância
                        os.remove(file_path)
                        time.sleep(1)
                        print(f"Instância iniciada para sessão {session_id} na porta {port}")
                    except subprocess.CalledProcessError as e:
                        print(f"Erro ao iniciar instância para sessão {session_id}: {e}")

# Loop para verificar e processar os arquivos JSON a cada 7 segundos
while True:
    process_json_files()
    time.sleep(1)
