<<<<<<< HEAD
# 🌊 Mix-key project 

An IoT-based Time-series Flood Monitoring & Alert System designed for local municipalities in Thailand. This system integrates real-time telemetry from hardware sensors, Mosquitto (MQTT) broker, Node-RED transformers, a high-performance TimescaleDB database, a PHP Laravel API gateway, and a Next.js citizen/staff dashboard—all orchestrated securely via Docker.

---

## 🛠️ Tech Stack & Architecture

*   **Frontend:** Next.js 14 (TypeScript, Tailwind CSS, Leaflet.js Mapping, Apache ECharts)
*   **Backend:** PHP Laravel 10 (REST API, Rate-limited Ingestion, Watchdog Cronjobs)
*   **IoT Stream:** Hardware Sensors → Mosquitto (MQTT) → Node-RED (Transformer) → TimescaleDB
*   **Database:** PostgreSQL 15 + TimescaleDB (Time-series partitioned Hypertables)
*   **Reverse Proxy:** Nginx (Layer 7 SSL & Routing Gateway)
*   **Infrastructure:** Docker & Docker Compose (12-Factor App)

---

## 🚀 How to Set Up the Project (Onboarding Guide)

Follow these simple steps to get the entire multi-container environment running locally on your computer.

### 📋 Prerequisites
Before starting, ensure you have the following installed on your machine:
*   [Git](https://git-scm.com/)
*   [Docker Desktop](https://www.docker.com/products/docker-desktop/) *(Ensure it is opened and running)*
*   **For terminal shortcuts (Optional but highly recommended):**
    *   **Mac/Linux:** Standard `make` is pre-installed.
    *   **Windows:** Install `make` by opening PowerShell (Admin) and running: `winget install ezwinports.make` *(Restart VS Code after installation)*.

---

### 💻 Step-by-Step Setup

#### **1. Fork & Clone the Repository**
Fork this repository to your own GitHub account, then clone it to your local machine:
```bash
git clone https://github.com/YOUR_USERNAME/Mix-Key.git
cd Mix-Key
```

#### **2. Open in your IDE**
Open the project folder inside your preferred editor (e.g., VS Code):
```bash
code .
```

#### **3. Download VS Code Extensions**
Install the official **Makefile Tools** extension by Microsoft inside VS Code. This will provide syntax highlighting and run buttons directly on your sidebar interface.

#### **4. Ensure Docker Desktop is Active**
Open the **Docker Desktop** application and wait until the indicator in the bottom-left corner turns **Green** (indicating the engine is healthy).

#### **5. Configure Local Environments**

ขั้นตอนนี้สำคัญที่สุด เพราะถ้าพลาด Backend จะเชื่อมต่อฐานข้อมูลไม่ได้:

* **สร้างไฟล์ `.env` ของระบบหลัก:**
  ```bash
  cp .env.example .env
  ```
  *(เปิดไฟล์ `.env` แล้วตรวจสอบว่า `DB_PASSWORD` และ `MQTT_PASSWORD` ได้รับการตั้งค่าไว้เรียบร้อยแล้ว)*

* **สร้างไฟล์ `.env` ของ Backend (Laravel):**
  ```bash
  cp backend/.env.example backend/.env
  ```

* **สร้างไฟล์ Docker Override:**
  ```bash
  cp docker-compose.override.example.yml docker-compose.override.yml
  ```

* **หรือให้ระบบจัดการให้อัตโนมัติ:** หากรันคำสั่ง setup ในขั้นตอนถัดไป ตัวสคริปต์เวอร์ชันปรับปรุงล่าสุดจะทำการสร้างและคัดลอกไฟล์เหล่านี้ให้โดยอัตโนมัติ!


#### **6. Launch the Stack**
Choose **one** of the following methods to boot up the environment and install all packages:

##### **Method A: Using Makefile (Recommended & Automated)**
In your terminal, simply run:
```bash
make setup
```
*If you are on Windows using PowerShell without make installed, run: `./run.ps1 setup` instead.*

##### **Method B: Running Direct Docker Commands (Manual)**
If you do not want to use the automated script, run these commands sequentially in your terminal:
```bash
# 1. Build and boot all containers in the background
docker compose up -d --build

# 2. Install backend dependencies & generate encryption keys
docker compose exec backend composer install
docker compose exec backend php artisan key:generate

# 3. Create database tables and load TimescaleDB Hypertables
docker compose exec backend php artisan migrate:fresh --seed

# 4. Install frontend libraries (Tailwind & ECharts)
docker compose exec frontend npm install
```

---

## 🔒 Step 7: MQTT Security (Crucial!)
To protect the IoT broker against anonymous spoofing, convert the default plaintext credential file into a secure encrypted password hash. Run this command in your terminal while the containers are running:
```bash
docker compose exec mqtt mosquitto_passwd -U /mosquitto/config/passwd
```

---

## 🌐 Local Developer Access Ports

Once started, the Nginx reverse proxy routes all services seamlessly. Open your browser and navigate to:

| Service | Host Address | Exposed Local Port | Description |
| :--- | :--- | :--- | :--- |
| **Unified Portal (Nginx)** | [http://localhost](http://localhost) | `80` / `443` | Nginx reverse proxy routing `/api` to Laravel & `/` to Next.js |
| **Next.js Web Server** | [http://localhost:3000](http://localhost:3000) | `3000` | Frontend dashboard with Hot Module Reload (HMR) active |
| **Laravel API Gateway** | [http://localhost:8000](http://localhost:8000) | `8000` | Backend endpoints |
| **Node-RED panel** | [http://localhost:1880](http://localhost:1880) | `1880` | Interactive IoT flow transformer dashboard |
| **TimescaleDB** | `127.0.0.1` | `5433` | PostgreSQL client port *(username: `postgres`, password: `secret`, database: `flood_alert`)* |

---

## 🛠️ Developer Experience (DX) Command References

Use these commands to easily orchestrate the environment:

| Action | UNIX / Git Bash command | Windows PowerShell command | Direct Docker equivalent |
| :--- | :--- | :--- | :--- |
| **Start Stack** | `make start` | `./run.ps1 start` | `docker compose up -d` |
| **Stop Stack** | `make stop` | `./run.ps1 stop` | `docker compose down` |
| **Reset Database** | `make reset-db` | `./run.ps1 reset-db` | `docker compose exec backend php artisan migrate:fresh --seed` |
| **Laravel Shell** | `make shell-back` | `./run.ps1 shell-back` | `docker compose exec backend bash` |
| **Next.js Shell** | `make shell-front` | `./run.ps1 shell-front` | `docker compose exec frontend sh` |
| **Postgres Shell** | `make shell-db` | `./run.ps1 shell-db` | `docker compose exec db psql -U postgres -d flood_alert` |
| **Git Sync & Update** | `make update` | `./run.ps1 update` | *Pulls origin, runs composer install, database migrations, and npm install* |
=======
HOW TO SET UP PROJECT
1.FOLK THIS REPO
2.OPEN IN IDE
3.DOWNLOAD Makefiles Tools extentions ใน vscode 
4.เปิด docker desktop
5.เปิด terminal แล้วพิม make setup หรือ ใช้คำสั่งนี้ 
docker-compose up -d --build
docker-compose exec backend composer install
docker-compose exec backend php artisan key:generate
docker-compose exec backend php artisan migrate:fresh --seed
docker-compose exec frontend npm install
6.เปลี่ยน file ลบ .exam ในทุกไฟล์ แล้ว ตั้งค่า environtment
>>>>>>> 986047a6543b5a96c1b47ba1eaec914796f69014


Hi