ifeq ($(OS),Windows_NT)
    # Windows Cmd settings
    COPY_ENV = if not exist .env copy .env.example .env
    COPY_OVERRIDE = if not exist docker-compose.override.yml copy docker-compose.override.example.yml docker-compose.override.yml
else
    # Linux / macOS settings
    COPY_ENV = test -f .env || cp .env.example .env
    COPY_OVERRIDE = test -f docker-compose.override.yml || cp docker-compose.override.example.yml docker-compose.override.yml
endif

.PHONY: help setup start stop restart update format logs shell-back shell-front shell-db clean-safe reset-db
.DEFAULT_GOAL := help

help:
	@echo "Available commands: setup, start, stop, update, format, logs, shell-back, shell-front, clean-safe, reset-db"

setup:
	@$(COPY_ENV)
	@$(COPY_OVERRIDE)
	@docker-compose up -d --build
	@docker-compose exec backend composer install
	@docker-compose exec backend php artisan key:generate
	@docker-compose exec backend php artisan migrate:fresh --seed
	@docker-compose exec frontend npm install
	@echo "Setup Complete!"

start:
	@docker-compose up -d

stop:
	@docker-compose down

update:
	git pull origin main
	@docker-compose exec backend composer install
	@docker-compose exec backend php artisan migrate
	@docker-compose exec frontend npm install
	@docker-compose up -d --build --force-recreate backend frontend nginx

rebuild:
	@docker-compose build backend frontend nginx
	@docker-compose up -d --force-recreate backend frontend nginx

format:
	@docker-compose exec backend ./vendor/bin/pint
	@docker-compose exec frontend npm run lint -- --fix

shell-back:
	@docker-compose exec backend bash

shell-front:
	@docker-compose exec frontend sh

shell-db:
	@docker-compose exec db psql -U postgres -d flood_alert

reset-db:
	@docker-compose exec backend php artisan migrate:fresh --seed

clean-safe:
	@read -p "Are you sure? Delete DB & containers? (y/n): " ans; \
	if [ "$$ans" = "y" ] || [ "$$ans" = "Y" ]; then docker-compose down -v; fi
