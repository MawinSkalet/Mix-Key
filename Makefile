ifeq ($(OS),Windows_NT)
    # Windows Cmd settings
    COPY_ENV = if not exist .env copy .env.example .env
    COPY_BACK_ENV = if not exist backend\.env copy backend\.env.example backend\.env
    COPY_OVERRIDE = if not exist docker-compose.override.yml copy docker-compose.override.example.yml docker-compose.override.yml
    WAIT_BACKEND = powershell -Command "while (-not (Test-Path backend\vendor\autoload.php)) { Start-Sleep -Seconds 2 }"
    WAIT_FRONTEND = powershell -Command "while (-not (Test-Path frontend\node_modules)) { Start-Sleep -Seconds 2 }"
else
    # Linux / macOS settings
    COPY_ENV = test -f .env || cp .env.example .env
    COPY_BACK_ENV = test -f backend/.env || cp backend/.env.example backend/.env
    COPY_OVERRIDE = test -f docker-compose.override.yml || cp docker-compose.override.example.yml docker-compose.override.yml
    WAIT_BACKEND = until [ -f backend/vendor/autoload.php ]; do sleep 2; done
    WAIT_FRONTEND = until [ -d frontend/node_modules ]; do sleep 2; done
endif

.PHONY: help setup start stop restart update format logs shell-back shell-front shell-db clean-safe reset-db
.DEFAULT_GOAL := help

help:
	@echo "Available commands: setup, start, stop, update, format, logs, shell-back, shell-front, clean-safe, reset-db"

setup:
	@$(COPY_ENV)
	@$(COPY_BACK_ENV)
	@$(COPY_OVERRIDE)
	@docker-compose up -d --build
	@echo "Waiting for backend dependencies to be installed..."
	@$(WAIT_BACKEND)
	@echo "Backend dependencies installed successfully!"
	@docker-compose exec backend php artisan key:generate
	@docker-compose exec backend php artisan migrate:fresh --seed
	@echo "Waiting for frontend dependencies to be installed..."
	@$(WAIT_FRONTEND)
	@echo "Frontend dependencies installed successfully!"
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
