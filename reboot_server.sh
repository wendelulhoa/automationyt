#!/bin/bash
docker compose build && docker compose down && docker compose up -d

# Reinicia o apache
systemctl restart apache2