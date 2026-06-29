# Setup Guide

> Step-by-step installation for the Financial Assistance Program — Voucher Management System.
> For configuration details and troubleshooting see [CONFIG.md](CONFIG.md).

---

## Prerequisites

| Requirement | Version |
| ----------- | ------- |
| PHP         | 8.2+    |
| Composer    | any     |
| MySQL       | 5.7+ or MariaDB 10.3+ |
| Web server  | Apache (XAMPP) or Nginx |

Required PHP extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `gd`, `zip`, `fileinfo`, `curl`

> **Fresh XAMPP ships with `intl`, `gd`, and `zip` commented out.** Enable them before continuing — see [CONFIG.md → Required PHP Extensions](CONFIG.md#required-php-extensions).

---

## Development Setup

### 1. Clone and install

```bash
git clone https://github.com/jgtrea/FinancialAssistanceProgram_G2.git
cd FinancialAssistanceProgram_G2
composer install
```

### 2. Configure environment

```bash
cp env .env
```

Edit `.env`:

```ini
CI_ENVIRONMENT = development
app.baseURL    = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = voucher_system
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

### 3. Import the database

Base tables are **not** created by migrations — import the team's SQL dump first, then migrate:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS voucher_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
C:\xampp\mysql\bin\mysql.exe -u root voucher_system < path\to\voucher_system.sql
php spark migrate
```

> No dump yet? Ask a teammate: `mysqldump -u root voucher_system > voucher_system.sql`

### 4. Start the server

```bash
php spark serve
```

Open [http://localhost:8080](http://localhost:8080).

### 5. Start the PDF worker (separate terminal)

```bash
php spark run:json-pdf-queue 5
```

The `5` is the poll interval in seconds. Keep this terminal open while generating vouchers.

---

## Production / LAN Deployment (Windows + XAMPP)

One Windows PC acts as the server; all other PCs on the LAN point their browsers at it. No software needed on client PCs.

```
[Client PCs]  ──HTTP──>  [Server PC running XAMPP]
                              ├── Apache + PHP  (web UI)
                              ├── MySQL         (data)
                              └── PDF worker    (background queue)
```

### Server one-time setup

1. **Install XAMPP** (Apache + MySQL + PHP 8.2+). Enable required PHP extensions — see [CONFIG.md](CONFIG.md#required-php-extensions).

2. **Clone and install:**

   ```powershell
   cd C:\xampp\htdocs
   git clone https://github.com/jgtrea/FinancialAssistanceProgram_G2.git
   cd FinancialAssistanceProgram_G2
   composer install
   ```

3. **Configure `.env`** — same as development above, with `CI_ENVIRONMENT = production` and the server's LAN IP as `app.baseURL`.

4. **Import the database:**

   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS voucher_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
   C:\xampp\mysql\bin\mysql.exe -u root voucher_system < path\to\voucher_system.sql
   php spark migrate
   ```

5. **Auto-start Apache + MySQL on boot** — XAMPP Control Panel → Config → check Apache and MySQL under "Autostart of modules".

6. **Prevent the server from sleeping:**

   ```powershell
   powercfg /change standby-timeout-ac 0
   powercfg /change hibernate-timeout-ac 0
   ```

7. **Install the PDF worker as a scheduled task** (Admin PowerShell):

   ```powershell
   cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
   Set-ExecutionPolicy -Scope Process Bypass -Force
   .\install-cron-worker.ps1 -EveryMinutes 1
   ```

   This registers `JsonPdfQueueCron` as a Windows Scheduled Task that fires every minute, drains the queue, and exits. See [CONFIG.md → PDF Worker](CONFIG.md#pdf-worker) for tuning options and the always-on service alternative.

8. **Open port 80 in Windows Firewall:**

   ```powershell
   New-NetFirewallRule -DisplayName "XAMPP Apache (80)" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow
   ```

9. **Share the URL with users:** `http://<server-lan-ip>/`

### Verify the worker is running

```powershell
Get-ScheduledTask     JsonPdfQueueCron | Select TaskName, State
Get-ScheduledTaskInfo JsonPdfQueueCron | Select LastRunTime, LastTaskResult, NextRunTime

# Watch the log
Get-Content "C:\xampp\htdocs\FinancialAssistanceProgram_G2\writable\logs\json-pdf-worker.log" -Tail 30 -Wait
```

---

## Updating an Existing Deployment

```powershell
cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
git pull
composer install --no-dev --optimize-autoloader
php spark migrate
# Cron worker: next fire picks up new code automatically.
# Apply immediately: Start-ScheduledTask -TaskName JsonPdfQueueCron
```

If using the always-on service instead of cron: `nssm restart JsonPdfQueue`

---

## Default Credentials

Seed an admin account manually or via a database seeder. The password hash algorithm used is `PASSWORD_ARGON2ID`.

---

> For troubleshooting, worker tuning, and log file locations see [CONFIG.md](CONFIG.md).
