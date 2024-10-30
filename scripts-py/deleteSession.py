import os
import shutil
import argparse

def remove_folder(folder_path):
    """Remove a pasta e seu conteúdo."""
    if os.path.exists(folder_path):
        shutil.rmtree(folder_path)
        print(f"Folder {folder_path} removed successfully.")
    else:
        print(f"Folder {folder_path} does not exist.")

def set_permissions(folder_path):
    """Define permissões 777 para uma pasta."""
    if os.path.exists(folder_path):
        os.chmod(folder_path, 0o777)
        print(f"Permissions set to 777 for {folder_path}.")
    else:
        print(f"Folder {folder_path} does not exist.")

if __name__ == "__main__":
    # Configuração do argparse para aceitar argumentos via terminal
    parser = argparse.ArgumentParser(description="Remove uma pasta e define permissões 777.")
    parser.add_argument("folder", type=str, help="O caminho da pasta a ser removida e recriada com permissões 777")
    
    args = parser.parse_args()
    folder = args.folder

    # Remove a pasta se ela existir
    remove_folder(folder)

    # Cria uma nova pasta para aplicar permissões, se necessário
    os.makedirs(folder, exist_ok=True)

    # Define permissões 777 na nova pasta
    set_permissions(folder)
