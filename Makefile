.PHONY: all install-docker setup-env create-docker-resources prepare-env build

# Cores para saída formatada
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m

all: install-docker setup-env create-docker-resources prepare-env build

install-docker:
	@echo "$(GREEN)[1/5] Instalando Docker...$(NC)"
	@if ! command -v docker > /dev/null 2>&1; then \
		curl -fsSL https://get.docker.com -o get-docker.sh; \
		sh get-docker.sh; \
		rm get-docker.sh; \
	else \
		echo "Docker já instalado"; \
	fi

	@echo "$(GREEN)[1.1] Adicionando usuário atual ao grupo docker...$(NC)"
	@sudo usermod -aG docker $$USER

	@echo "$(GREEN)[1.2] Instalando Docker Compose plugin (caso necessário)...$(NC)"
	@if ! docker compose version > /dev/null 2>&1; then \
		sudo apt update && sudo apt install -y docker-compose-plugin; \
	else \
		echo "Docker Compose já instalado"; \
	fi

setup-env:
	@echo "$(GREEN)[2/5] Adicionando alias 'dev' e exportando COMPOSE_BAKE=true...$(NC)"

	@grep -qxF "alias dev='bash dev'" ~/.bashrc || \
		echo "\nalias dev='bash dev'" >> ~/.bashrc

	@grep -qxF "export COMPOSE_BAKE=true" ~/.bashrc || \
		echo "\nexport COMPOSE_BAKE=true" >> ~/.bashrc
	
	@echo "$(GREEN)[2.1] Recarregando bashrc...$(NC)"
	@bash -c "source ~/.bashrc" || echo "Erro ao tentar recarregar o bashrc. Execute manualmente 'source ~/.bashrc'."

create-docker-resources:
	@echo "$(GREEN)[3/5] Criando volume e rede do Docker...$(NC)"
	
	@docker volume inspect storage-automationyt > /dev/null 2>&1 || \
		docker volume create storage-automationyt

	@docker network inspect redeautomationyt > /dev/null 2>&1 || \
		docker network create redeautomationyt

prepare-env:
	@echo "$(GREEN)[4/5] Verificando arquivo .env...$(NC)"
	@if [ ! -f .env ]; then \
		cp .env.example .env; \
		echo ""; \
		echo "$(YELLOW)O arquivo .env foi criado com sucesso.$(NC)"; \
		echo "$(YELLOW)Se você precisar editar o .env antes de continuar, pressione ENTER após terminar, ou feche com Ctrl+C.$(NC)"; \
		bash -c 'read -p "Pressione ENTER para continuar..."'; \
	else \
		echo "Arquivo .env já existe."; \
	fi

build:
	@echo "$(GREEN)[5/5] Executando docker compose build...$(NC)"
	@docker compose build
