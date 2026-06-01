param (
    [string]$Action
)

function Show-Help {
    Write-Host "========================================================" -ForegroundColor Cyan
    Write-Host "   Flood Monitoring System - Windows DX Helper Script   " -ForegroundColor Cyan
    Write-Host "========================================================" -ForegroundColor Cyan
    Write-Host "Available commands:" -ForegroundColor Gray
    Write-Host "  ./run.ps1 setup       - Perform initial environment copy, builds, and package installs"
    Write-Host "  ./run.ps1 start       - Boot up all Docker containers"
    Write-Host "  ./run.ps1 stop        - Shut down all running containers"
    Write-Host "  ./run.ps1 reset-db    - Roll back, re-run migrations, and re-seed databases"
    Write-Host "  ./run.ps1 shell-back  - Open an interactive shell inside the Laravel container"
    Write-Host "  ./run.ps1 shell-front - Open an interactive shell inside the Next.js container"
    Write-Host "  ./run.ps1 shell-db    - Connect to TimescaleDB command-line client"
    Write-Host "  ./run.ps1 update      - Pull latest code and auto-install/build packages for dev alignment"
    Write-Host "========================================================" -ForegroundColor Cyan
}

if (-not $Action) {
    Show-Help
    exit
}

switch ($Action) {
    "setup" {
        Write-Host "Initializing Environment Configuration files..." -ForegroundColor Gray
        if (-not (Test-Path .env)) { 
            Copy-Item .env.example .env 
            Write-Host "Created .env" -ForegroundColor Green
        }
        if (-not (Test-Path backend\.env)) { 
            Copy-Item backend\.env.example backend\.env 
            Write-Host "Created backend/.env" -ForegroundColor Green
        }
        if (-not (Test-Path docker-compose.override.yml)) { 
            Copy-Item docker-compose.override.example.yml docker-compose.override.yml 
            Write-Host "Created docker-compose.override.yml" -ForegroundColor Green
        }

        Write-Host "Building and launching Docker containers..." -ForegroundColor Gray
        docker compose up -d --build

        Write-Host "Running Backend migrations and keys setup..." -ForegroundColor Gray
        docker compose exec backend composer install
        docker compose exec backend php artisan key:generate
        docker compose exec backend php artisan migrate:fresh --seed

        Write-Host "Installing Frontend dependencies..." -ForegroundColor Gray
        docker compose exec frontend npm install

        Write-Host "`nSetup Complete!" -ForegroundColor Green
    }

    "start" {
        Write-Host "Starting all containers..." -ForegroundColor Gray
        docker compose up -d
        Write-Host "Containers started successfully!" -ForegroundColor Green
    }

    "stop" {
        Write-Host "Stopping all containers..." -ForegroundColor Gray
        docker compose down
        Write-Host "Containers stopped gracefully!" -ForegroundColor Yellow
    }

    "reset-db" {
        Write-Host "Resetting database structures..." -ForegroundColor Gray
        docker compose exec backend php artisan migrate:fresh --seed
        Write-Host "Database reset and seeded!" -ForegroundColor Green
    }

    "shell-back" {
        docker compose exec backend bash
    }

    "shell-front" {
        docker compose exec frontend sh
    }

    "shell-db" {
        docker compose exec db psql -U postgres -d flood_alert
    }

    "update" {
        Write-Host "Pulling latest code from Git..." -ForegroundColor Gray
        git pull origin main
        
        Write-Host "Syncing and installing Composer packages..." -ForegroundColor Gray
        docker compose exec backend composer install
        
        Write-Host "Running database migrations..." -ForegroundColor Gray
        docker compose exec backend php artisan migrate
        
        Write-Host "Installing/Syncing frontend npm packages..." -ForegroundColor Gray
        docker compose exec frontend npm install
        
        Write-Host "`nProject packages successfully synced and updated!" -ForegroundColor Green
    }

    default {
        Show-Help
    }
}
