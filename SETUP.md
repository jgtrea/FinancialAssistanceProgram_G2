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

> If `composer install` fails due to `ext-gd` not being enabled yet, run:
> ```powershell
> composer update --ignore-platform-req=ext-gd
> ```
> Then enable `gd` in `php.ini` and restart Apache.

### 2. Configure environment

```powershell
New-Item .env -ItemType File
notepad .env
```

Paste and fill in:

```ini
CI_ENVIRONMENT = development
app.baseURL    = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = voucher_system
database.default.username = root
database.default.password =
database.default.DBDriver = MySQLi
```

### 3. Database setup

A base schema template is included in the repo. `php spark migrate` only applies incremental column changes — **it does not create the base tables on its own.**

```powershell
# Create the database
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS voucher_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

# Import the base schema + default admin user
C:\xampp\mysql\bin\mysql.exe -u root voucher_system < app\Database\voucher_system.sql

# Apply incremental migrations on top
php spark migrate
```

> Already have a team dump? Use that instead of `voucher_system.sql`:
> ```powershell
> C:\xampp\mysql\bin\mysql.exe -u root voucher_system < path\to\team_dump.sql
> php spark migrate
> ```

### 4. Set the admin password

The template seeds `admin@example.com` with a placeholder password. Generate a real ARGON2ID hash and apply it:

```powershell
# 1. Generate the hash
C:\xampp\php\php.exe -r "echo password_hash('Admin@1234', PASSWORD_ARGON2ID);"

# 2. Apply it — replace <hash> with the output above
C:\xampp\mysql\bin\mysql.exe -u root voucher_system -e "UPDATE users SET password = '<hash>' WHERE email = 'admin@example.com';"
```

> Change the email and password on first login.

### 5. Start the server

```bash
php spark serve
```

Open [http://localhost:8080](http://localhost:8080).

### 6. Start the PDF worker (separate terminal)

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

Follow the same steps as Development Setup above, with these differences:
- Set `CI_ENVIRONMENT = production` in `.env`
- Set `app.baseURL` to the server's LAN IP (e.g. `'http://192.168.1.10/'`)
- Auto-start Apache + MySQL on boot — XAMPP Control Panel → Config → check Apache and MySQL under "Autostart of modules"
- Prevent the server from sleeping:

```powershell
powercfg /change standby-timeout-ac 0
powercfg /change hibernate-timeout-ac 0
```

### Install the PDF worker as a scheduled task

Open an **Administrator PowerShell** in the project directory:

```powershell
cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
Set-ExecutionPolicy -Scope Process Bypass -Force
.\install-cron-worker.ps1 -EveryMinutes 1
```

Registers `JsonPdfQueueCron` — fires every minute, drains the queue, exits. See [CONFIG.md → PDF Worker](CONFIG.md#pdf-worker) for tuning options.

### Open port 80 in Windows Firewall

```powershell
New-NetFirewallRule -DisplayName "XAMPP Apache (80)" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow
```

Share the URL with users: `http://<server-lan-ip>/`

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

> For troubleshooting, worker tuning, and log file locations see [CONFIG.md](CONFIG.md).
