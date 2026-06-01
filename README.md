# 🌊 Mix-Key Project

An IoT-based Time-series Flood Monitoring & Alert System designed for local municipalities in Thailand. This system integrates real-time telemetry from hardware sensors, Mosquitto (MQTT) broker, Node-RED transformers, a high-performance TimescaleDB database, a PHP Laravel API gateway, and a Next.js citizen/staff dashboard—all orchestrated securely via Docker.

---

## 🛠️ Tech Stack & Architecture

| Layer | Technology |
| :--- | :--- |
| **Frontend** | Next.js 14 (TypeScript, Tailwind CSS, Leaflet.js Mapping, Apache ECharts) |
| **Backend** | PHP Laravel 10 (REST API, Rate-limited Ingestion, Watchdog Cronjobs) |
| **IoT Stream** | Hardware Sensors → Mosquitto (MQTT) → Node-RED (Transformer) → TimescaleDB |
| **Database** | PostgreSQL 15 + TimescaleDB (Time-series partitioned Hypertables) |
| **Reverse Proxy** | Nginx (Layer 7 SSL & Routing Gateway) |
| **Infrastructure** | Docker & Docker Compose (12-Factor App) |

```
┌─────────────────────────────────────────────────────────────┐
│                     Nginx (Port 80/443)                     │
│                  Reverse Proxy & SSL Gateway                │
├──────────────────────────┬──────────────────────────────────┤
│     /  → Next.js :3000   │     /api  → Laravel :8000       │
├──────────────────────────┴──────────────────────────────────┤
│                                                             │
│   ┌─────────────┐   ┌──────────┐   ┌────────────────────┐  │
│   │  Mosquitto  │──▶│ Node-RED │──▶│ TimescaleDB (PG15) │  │
│   │  MQTT :1883 │   │   :1880  │   │       :5433        │  │
│   └──────▲──────┘   └──────────┘   └────────────────────┘  │
│          │                                                  │
│     IoT Sensors                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🚀 How to Set Up the Project

Follow these steps to get the entire multi-container environment running locally.

### 📋 Prerequisites

Before starting, ensure you have the following installed:

* [Git](https://git-scm.com/)
* [Docker Desktop](https://www.docker.com/products/docker-desktop/) *(Ensure it is opened and running)*
* **For Windows users (Recommended):** [WSL2 with Ubuntu](https://learn.microsoft.com/en-us/windows/wsl/install) — run all commands inside WSL2 for best performance
* **For terminal shortcuts (Optional):**
    * **Mac/Linux/WSL2:** `make` is pre-installed (or `sudo apt install make` on Ubuntu)
    * **Windows PowerShell:** Install `make` via `winget install ezwinports.make`, or use `./run.ps1` instead

---

### 💻 Step-by-Step Setup

#### **1. Fork & Clone the Repository**

Fork this repository to your own GitHub account, then clone it:

```bash
git clone https://github.com/YOUR_USERNAME/Mix-Key.git
cd Mix-Key
```

> **⚠️ Windows Users:** It is **strongly recommended** to clone and run this project inside WSL2's native filesystem (`~/Mix-Key`) instead of the Windows filesystem (`/mnt/c/...`). This provides 10–100x faster file I/O and proper `inotify` support for hot-reloading. See the [WSL2 Setup Guide](#-wsl2-setup-guide-windows-users) section below.

#### **2. Open in your IDE**

```bash
code .
```

Install the **Makefile Tools** extension by Microsoft in VS Code for syntax highlighting and sidebar run buttons.

#### **3. Ensure Docker Desktop is Active**

Open **Docker Desktop** and wait until the engine indicator turns **Green** (healthy).

> **Windows + WSL2:** Go to Docker Desktop → **Settings** → **Resources** → **WSL Integration** → enable your Ubuntu distro → **Apply & restart**.

#### **4. Configure Local Environments**

> ⚡ **Note:** If you use `make setup` in the next step, these files will be created automatically. You can skip this step.

```bash
# Root environment (DB & MQTT credentials)
cp .env.example .env

# Backend Laravel environment
cp backend/.env.example backend/.env

# Docker Compose dev overrides (exposes ports, enables hot-reload)
cp docker-compose.override.example.yml docker-compose.override.yml
```

Open `.env` and set your passwords:

```env
DB_PASSWORD=your_secure_db_password
MQTT_USER=your_mqtt_user
MQTT_PASSWORD=your_mqtt_password
```

#### **5. Launch the Stack**

Choose **one** of the following methods:

##### Method A: Makefile (Recommended & Automated)

```bash
make setup
```

*Windows PowerShell (without WSL2):* `./run.ps1 setup`

##### Method B: Manual Docker Commands

```bash
# 1. Build and boot all containers
docker compose up -d --build

# 2. Install backend dependencies & generate encryption keys
docker compose exec backend composer install
docker compose exec backend php artisan key:generate

# 3. Create database tables and seed sample data
docker compose exec backend php artisan migrate:fresh --seed

# 4. Install frontend libraries
docker compose exec frontend npm install
```

#### **6. MQTT Security (Important!)**

Encrypt the MQTT password file to protect against anonymous spoofing:

```bash
docker compose exec mqtt mosquitto_passwd -U /mosquitto/config/passwd
```

---

## 🌐 Local Developer Access Ports

Once started, open your browser and navigate to:

| Service | URL | Port | Description |
| :--- | :--- | :--- | :--- |
| **Unified Portal** | [http://localhost](http://localhost) | `80` / `443` | Nginx reverse proxy: `/api` → Laravel, `/` → Next.js |
| **Next.js Frontend** | [http://localhost:3000](http://localhost:3000) | `3000` | Dashboard with Hot Module Reload (HMR) |
| **Laravel API** | [http://localhost:8000](http://localhost:8000) | `8000` | Backend REST API endpoints |
| **Node-RED** | [http://localhost:1880](http://localhost:1880) | `1880` | IoT flow transformer dashboard |
| **TimescaleDB** | `127.0.0.1:5433` | `5433` | PostgreSQL (`postgres` / `your_password` / `flood_alert`) |
| **MQTT Broker** | `127.0.0.1:1883` | `1883` | Mosquitto MQTT broker |

---

## 🛠️ Developer Experience (DX) Commands

| Action | Make (Linux/Mac/WSL2) | PowerShell (Windows) | Docker Equivalent |
| :--- | :--- | :--- | :--- |
| **Initial Setup** | `make setup` | `./run.ps1 setup` | *(see Step 5 above)* |
| **Start Stack** | `make start` | `./run.ps1 start` | `docker compose up -d` |
| **Stop Stack** | `make stop` | `./run.ps1 stop` | `docker compose down` |
| **Reset Database** | `make reset-db` | `./run.ps1 reset-db` | `docker compose exec backend php artisan migrate:fresh --seed` |
| **Rebuild Services** | `make rebuild` | — | `docker compose build && docker compose up -d --force-recreate` |
| **Laravel Shell** | `make shell-back` | `./run.ps1 shell-back` | `docker compose exec backend bash` |
| **Next.js Shell** | `make shell-front` | `./run.ps1 shell-front` | `docker compose exec frontend sh` |
| **Postgres Shell** | `make shell-db` | `./run.ps1 shell-db` | `docker compose exec db psql -U postgres -d flood_alert` |
| **Lint & Format** | `make format` | — | `docker compose exec backend ./vendor/bin/pint` |
| **Git Sync & Update** | `make update` | `./run.ps1 update` | Pulls origin, composer install, migrate, npm install |
| **Delete Everything** | `make clean-safe` | — | `docker compose down -v` *(removes all data!)* |

---

## 🐧 WSL2 Setup Guide (Windows Users)

For the best development experience on Windows, run this project inside **WSL2 (Ubuntu)**:

### 1. Install Ubuntu on WSL2

```powershell
# In Windows PowerShell (Admin):
wsl --install -d Ubuntu
```

After installation, set your Linux username and password when prompted.

### 2. Enable Docker WSL Integration

1. Open **Docker Desktop** → **Settings** → **Resources** → **WSL Integration**
2. Toggle **ON** for **Ubuntu**
3. Click **Apply & restart**

### 3. Clone & Run Inside WSL2

```bash
# Open Ubuntu terminal, then:
cd ~
git clone https://github.com/YOUR_USERNAME/Mix-Key.git
cd Mix-Key

# Install make
sudo apt update && sudo apt install -y make

# Setup the project
make setup
```

### 4. Open in VS Code from WSL2

```bash
code .
```

This launches VS Code with the **WSL extension**, giving you native Linux performance with a Windows GUI.

### 5. Access WSL2 Files from Windows

You can browse your project files in Windows Explorer at:

```
\\wsl$\Ubuntu\home\YOUR_USERNAME\Mix-Key
```

> **⚠️ Important Notes for WSL2:**
> * Always store project files in `~/` (Linux filesystem), **never** on `/mnt/c/` (Windows filesystem)
> * Linux filesystem is 10–100x faster for Docker bind mounts and file watching
> * `inotify` (hot-reload) only works on the native Linux filesystem

---

## 📁 Project Structure

```
Mix-Key/
├── backend/                # Laravel 10 API (PHP 8.4)
│   ├── Dockerfile          # Multi-stage: base → dev → production
│   ├── app/                # Controllers, Models, Services
│   ├── database/           # Migrations & Seeders
│   └── routes/             # API route definitions
├── frontend/               # Next.js 14 Dashboard (TypeScript)
│   ├── Dockerfile          # Multi-stage: base → dev → production
│   ├── app/                # Pages & Components
│   └── tailwind.config.ts  # Tailwind CSS configuration
├── mosquitto/              # MQTT Broker configuration
│   └── config/
│       ├── mosquitto.conf  # Broker settings (auth required)
│       └── passwd          # Encrypted credentials
├── nginx/                  # Reverse Proxy
│   └── conf.d/
│       └── default.conf    # Routing rules (/api → backend, / → frontend)
├── nodered/                # IoT Flow Transformer
│   └── data/               # Node-RED flows & settings
├── postgres/               # Database initialization
│   └── init-scripts/
│       └── 01-init-timescaledb.sql  # Schema: stations, telemetry, alert_logs
├── .env.example            # Environment template
├── docker-compose.yml      # Production service definitions
├── docker-compose.override.example.yml  # Dev overrides (ports, volumes, HMR)
├── Makefile                # DX command shortcuts (Linux/Mac/WSL2)
└── run.ps1                 # DX command shortcuts (Windows PowerShell)
```

---

## 📜 License

This project is developed as part of an academic capstone project for flood monitoring in Thai municipalities.