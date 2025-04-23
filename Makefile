# Cores
GREEN := \033[0;32m
NC := \033[0m

.PHONY: all config start stop restart
all: config

config:
	@echo "${GREEN}Configurando o servidor${NC}"
	bash config_server_docker.sh

start:
	@echo "${GREEN}Iniciando uma instância${NC}"
	bash scripts/start_instance.sh $(word 2,$(MAKECMDGOALS)) $(word 3,$(MAKECMDGOALS))

stop:
	@echo "${GREEN}Parando uma instância${NC}"
	bash scripts/stop_instance.sh $(word 2,$(MAKECMDGOALS))
	
restart:
	@echo "${GREEN}Reiniciando uma instância${NC}"
	bash scripts/restart_instance.sh $(word 2,$(MAKECMDGOALS))

reboot:
	@echo "${GREEN}Reiniciando o servidor${NC}"
	bash reboot_server.sh

