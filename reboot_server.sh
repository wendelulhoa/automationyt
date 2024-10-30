#!/bin/bash
docker compose down && docker compose up -d

# Reinicia o apache
systemctl restart apache2

# Seta as permissões.
chmod -R 777 ./