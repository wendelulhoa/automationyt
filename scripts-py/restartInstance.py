import json
import os
import time
import subprocess

# Caminho da pasta onde os arquivos JSON estão armazenados
data_folder = "sessions-configs/restart_sessions"

# Função para processar o arquivo JSON e chamar o script shell
def process_json_files():
    for filename in os.listdir(data_folder):
        if filename.endswith(".json"):
            file_path = os.path.join(data_folder, filename)
            
            # Lê o conteúdo do arquivo JSON
            with open(file_path, 'r') as file:
                data = json.load(file)
                session_id = data.get("session_id")
                
                # Verifica se session_id e port foram encontrados
                if session_id:
                    # Executa o script shell com os argumentos
                    try:
                        subprocess.run(["scripts-sh/restart_instance.sh", session_id], check=True)
                        # Exclui o arquivo JSON após iniciar a instância
                        os.remove(file_path)
                        
                        print(f"Instância reiniciada para sessão {session_id}")
                    except subprocess.CalledProcessError as e:
                        print(f"Erro ao reiniciar instância para sessão {session_id}: {e}")

# Loop para verificar e processar os arquivos JSON a cada 5 segundos
while True:
    process_json_files()
    time.sleep(5)
