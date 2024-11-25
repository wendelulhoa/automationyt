import subprocess
import time

# Caminho do script shell
script_path = "./scripts-sh/recovery_instance.sh"

# Função para executar o script shell
def exec():
    try:
        print("Setando permissões...")
        subprocess.run(["bash", script_path], check=True)
        print("Permissões restauradas.")
    except subprocess.CalledProcessError as e:
        print(f"Erro ao setar permissões: {e}")
    except FileNotFoundError:
        print("O script não foi encontrado. Verifique o caminho.")

# Loop para executar o script a cada 30 segundos
while True:
    exec()
    print("Aguardando 30 segundos...")
    time.sleep(30)
