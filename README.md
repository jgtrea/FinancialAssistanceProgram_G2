# Financial Assistance Program — Voucher Management System

A web-based system for managing educational financial assistance vouchers for senior high school students. It handles student record management, voucher generation with PDF export, signatory management, archiving, and audit logging — with role-based access for admins and staff users.

---

## Features

- **Authentication & Role-Based Access** — separate Admin and Staff (User) roles with route-level protection
- **Student Management** — add, edit, archive, and restore student records
- **Voucher Generation** — create and track vouchers per student; generate multi-slot PDF vouchers with embedded signatory signatures
- **Signatory Management** — manage signatories with uploaded signature images used on generated PDFs
- **Archive** — soft-archive students and signatories with restore capability
- **Audit Logs** — track user actions system-wide; admins see all logs, staff see their own
- **User Management** _(Admin only)_ — create, archive, and restore staff accounts
- **Excel Import** — bulk-import student/voucher data via `.xlsx` files
- **Async PDF Queue** — PDF generation runs as a background job queue (JSON-file backed) processed by a Windows service, so the web request returns instantly and large batches never time out

---

## Tech Stack

| Layer              | Technology                              |
| ------------------ | --------------------------------------- |
| Framework          | CodeIgniter 4 (PHP 8.2+)                |
| PDF Generation     | mPDF 8.x                                |
| Spreadsheet Import | PhpSpreadsheet 5.x                      |
| Database           | MySQL (via CodeIgniter's Query Builder) |
| Frontend           | Bootstrap + vanilla JS                  |
| Testing            | PHPUnit 10                              |

---

## Folder Structure

```
FinancialAssistanceProgram_G2/
├── app/
│   ├── Config/             # App configuration (routes, filters, database, etc.)
│   ├── Controllers/
│   │   ├── Admin/          # Admin-only controllers (Dashboard, Voucher, Report)
│   │   ├── User/           # Staff controllers (Dashboard, Voucher)
│   │   ├── Authentication.php
│   │   ├── SignatoryController.php
│   │   ├── StudentController.php
│   │   ├── ArchiveController.php
│   │   ├── AuditLogController.php
│   │   ├── UsersController.php
│   │   └── VoucherImport.php
│   ├── Database/
│   │   └── Migrations/     # Database migration files
│   ├── Filters/            # Auth and role-check filters
│   ├── Helpers/            # Audit and voucher helper functions
│   ├── Libraries/          # VoucherPdf, JsonPdfQueue, JsonPdfRunner
│   ├── Commands/           # spark commands (ProcessJsonPdfQueue worker)
│   ├── Models/             # Eloquent-style models for each entity
│   └── Views/
│       ├── admin/          # Admin-specific pages
│       ├── auth/           # Login page
│       ├── layouts/        # Base layout (main.php)
│       ├── partials/       # Shared sidebar, topbar, footer
│       ├── signatories/    # Signatory list and form
│       ├── vouchers/       # Voucher list, form, generate, view
│       └── archive/        # Archive index
├── public/                 # Web root (index.php, assets)
├── writable/               # Logs, cache, uploads (not committed)
├── composer.json
└── .env                    # Local environment config (not committed)
```

---

## Getting Started

### Prerequisites

- PHP 8.2 or higher with extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `gd` (for mPDF)
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- A local web server (Apache/Nginx) or PHP's built-in server

### Setup

1. **Clone the repository**

   ```bash
   git clone https://github.com/jgtrea/FinancialAssistanceProgram_G2.git
   cd FinancialAssistanceProgram_G2
   ```

2. **Install dependencies**

   ```bash
   composer install
   ```

3. **Configure environment**

   ```bash
   cp env .env
   ```

   Edit `.env` and set at minimum:

   ```ini
   CI_ENVIRONMENT = development

   app.baseURL = 'http://localhost:8080/'

   database.default.hostname = localhost
   database.default.database = your_db_name
   database.default.username = your_db_user
   database.default.password = your_db_password
   database.default.DBDriver = MySQLi
   ```

4. **Run migrations**

   ```bash
   php spark migrate
   ```

5. **Start the development server**

   ```bash
   php spark serve
   ```

   Open [http://localhost:8080](http://localhost:8080) in your browser.

### Default Credentials

Seed an admin account manually or via a database seeder. The password hash algorithm used is `PASSWORD_ARGON2ID`.

### PDF Generation (development)

Voucher PDFs are generated asynchronously via a JSON-file-backed job queue. For local development, run the worker in a separate terminal:

```bash
php spark run:json-pdf-queue 5
```

The `5` is the poll interval in seconds. The worker drains `writable/pdf_queue/queue.json`, renders chunks, and writes the final PDF/ZIP into `writable/pdfs/`.

For production / LAN deployment see the next section.

---

## Production / LAN Deployment (Windows)

The system is typically deployed on a single Windows PC (XAMPP server) that the rest of the LAN points its browsers at. The background worker must keep running 24/7, surviving reboots and crashes. Use the bundled installer to register it as a Windows Service.

### Architecture recap

```
[Client PCs]  ──HTTP──>  [Server PC running XAMPP]
                              │
                              ├── Apache + PHP serve the web UI
                              ├── MySQL stores records
                              └── JsonPdfQueue worker (Windows Service)
                                     drains writable/pdf_queue/*.json
                                     writes PDFs to writable/pdfs/
```

Client PCs need nothing installed. Only the server PC runs the deployment steps below.

### Server one-time setup

1. **Install XAMPP** on the server PC (Apache + MySQL + PHP 8.2+).

2. **Clone the repo:**

   ```powershell
   cd C:\xampp\htdocs
   git clone https://github.com/jgtrea/FinancialAssistanceProgram_G2.git
   cd FinancialAssistanceProgram_G2
   composer install
   ```

3. **Configure `.env`** as described in the Setup section above, then run migrations:

   ```powershell
   php spark serve
   php spark migrate
   ```

4. **Make XAMPP services auto-start** (so Apache + MySQL come up on boot). Open XAMPP Control Panel → Config → check "Apache" and "MySQL" under "Autostart of modules", or install them as Windows services from the same panel.

5. **Set server power plan to never sleep** (so the worker isn't paused at night):

   ```powershell
   powercfg /change standby-timeout-ac 0
   powercfg /change hibernate-timeout-ac 0
   ```

6. **Install NSSM** (Non-Sucking Service Manager). Open an **Administrator PowerShell**:

   ```powershell
   winget install -e --id NSSM.NSSM
   ```

   Close and reopen the Admin PowerShell so the updated `PATH` is picked up. Verify:

   ```powershell
   nssm version
   ```

   If `winget` is unavailable, download from <https://nssm.cc/download>, extract, and copy `nssm.exe` into `C:\Windows\System32\`.

7. **Run the worker installer** from the project directory:

   ```powershell
   cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
   Set-ExecutionPolicy -Scope Process Bypass -Force
   .\install-worker.ps1
   ```

   The script:
   - Grants the `SYSTEM` account full access to `writable/` so the service can write logs and PDFs
   - Registers `JsonPdfQueue` as a Windows Service (auto-start on boot, auto-restart on crash)
   - Configures stdout/stderr → `writable\logs\json-pdf-worker.log`
   - Starts the service immediately

   If NSSM is missing, the script falls back to a Scheduled Task (also boot-time, also `SYSTEM`).

8. **Open port 80 on the Windows firewall** so LAN clients can reach Apache. Admin PowerShell:

   ```powershell
   New-NetFirewallRule -DisplayName "XAMPP Apache (80)" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow
   ```

9. **Bookmark the URL on each client PC:** `http://<server-lan-ip>/`

### Verifying the worker is alive

```powershell
Get-Service JsonPdfQueue           # Status should be Running
Get-Process php                    # one php.exe owned by SYSTEM
Get-Content "C:\xampp\htdocs\FinancialAssistanceProgram_G2\writable\logs\json-pdf-worker.log" -Tail 30 -Wait
```

You should see `JSON worker running. Polling every 5s.` and a heartbeat line every ~60 seconds.

### Service management

```powershell
nssm start   JsonPdfQueue          # start
nssm stop    JsonPdfQueue          # stop
nssm restart JsonPdfQueue          # bounce (do this after pulling code changes)
nssm status  JsonPdfQueue          # check
nssm remove  JsonPdfQueue confirm  # uninstall entirely
```

### Scaling: running multiple workers

One worker drains chunks one at a time. For faster throughput on large batches, run several workers in parallel. Job claiming is **concurrency-safe** — each chunk is claimed under an exclusive file lock (`flock(LOCK_EX)` in `JsonPdfQueue::mutateAll`), so two workers never render the same chunk. You just need one service per worker, each with a **unique service name** but the same command.

#### Add a worker (NSSM)

From an **Administrator PowerShell** in the project directory. The first worker is named `JsonPdfQueue`; name additional ones `JsonPdfQueue2`, `JsonPdfQueue3`, etc. All write to the same log; give each its own log file if you want to read them apart.

```powershell
$projectDir = "C:\xampp\htdocs\FinancialAssistanceProgram_G2"
$phpExe     = "C:\xampp\php\php.exe"
$name       = "JsonPdfQueue2"                                   # bump the number for each extra worker
$logFile    = "$projectDir\writable\logs\json-pdf-worker-2.log" # separate log per worker

nssm install $name $phpExe "spark" "run:json-pdf-queue" "5"
nssm set $name AppDirectory $projectDir
nssm set $name Start SERVICE_AUTO_START
nssm set $name AppExit Default Restart
nssm set $name AppRestartDelay 5000
nssm set $name AppThrottle 0
nssm set $name AppStdout $logFile
nssm set $name AppStderr $logFile
nssm set $name Description "Background PDF voucher worker (JSON file queue) - #2"
nssm start $name
```

Repeat with `JsonPdfQueue3` (and a `-3.log`) for a third, and so on.

#### Add a worker (Scheduled Task fallback)

If you used the Scheduled Task method (no NSSM), register another task with a unique name:

```powershell
$projectDir = "C:\xampp\htdocs\FinancialAssistanceProgram_G2"
$action  = New-ScheduledTaskAction -Execute "C:\xampp\php\php.exe" `
    -Argument "spark run:json-pdf-queue 5" -WorkingDirectory $projectDir
$trigger   = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -RunLevel Highest
$settings  = New-ScheduledTaskSettingsSet -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries

Register-ScheduledTask -TaskName "JsonPdfQueue2" `
    -Description "Background PDF voucher worker #2" `
    -Action $action -Trigger $trigger -Principal $principal -Settings $settings
Start-ScheduledTask -TaskName "JsonPdfQueue2"
```

#### How many?

Each worker is its own `php.exe` and mPDF holds a chunk in memory while rendering, so workers cost RAM and CPU. A good starting point is **one worker per CPU core**, leaving headroom for Apache + MySQL. Watch `Get-Process php` and Task Manager memory; back off if the server starts swapping.

#### Manage / remove the extra workers

```powershell
Get-Service JsonPdfQueue*              # list all workers and their status
nssm restart JsonPdfQueue2             # bounce one (do this for each after pulling code)
nssm remove  JsonPdfQueue2 confirm     # remove one worker
```

> When updating the deployment (below), restart **every** worker, not just the first:
> `Get-Service JsonPdfQueue* | ForEach-Object { nssm restart $_.Name }`

### Updating the deployment

```powershell
cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
git pull
composer install --no-dev --optimize-autoloader
php spark migrate
nssm restart JsonPdfQueue
```

### Troubleshooting

| Symptom                                    | Cause / Fix                                                                                                           |
| ------------------------------------------ | --------------------------------------------------------------------------------------------------------------------- |
| Service stuck `Paused`                     | NSSM throttled it after fast crashes. `sc.exe stop JsonPdfQueue`, then check the log.                                 |
| Log full of "file used by another process" | An old `php.exe` is still holding the file. `Get-Process php \| Stop-Process -Force`, then `nssm start JsonPdfQueue`. |
| Worker starts then exits immediately       | Usually MySQL not yet up at boot, or `php.exe` path wrong. Check log; restart MySQL if needed.                        |
| Jobs queue but never process               | Service not running. `Get-Service JsonPdfQueue`.                                                                      |
| Status modal shows `queued` forever        | Same as above — worker is down.                                                                                       |
| Permission denied writing PDFs             | Re-run `icacls "C:\xampp\htdocs\FinancialAssistanceProgram_G2\writable" /grant "SYSTEM:(OI)(CI)F" /T`                 |

### Logs and queue files

| Path                                 | Contents                                    |
| ------------------------------------ | ------------------------------------------- |
| `writable/logs/json-pdf-worker.log`  | Service stdout/stderr                       |
| `writable/pdf_queue/queue.json`      | Pending jobs (parent + chunks)              |
| `writable/pdf_queue/processing.json` | Jobs currently being rendered               |
| `writable/pdf_queue/finished.json`   | Done + failed jobs (with final `file_path`) |
| `writable/pdfs/`                     | Generated PDF and ZIP files                 |

The three JSON files are append-rotated by the worker; you can safely back them up but don't edit them while the service is running.
