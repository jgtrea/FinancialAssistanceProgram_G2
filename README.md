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
- **Async PDF Queue** — PDF generation runs as a background job queue (JSON-file backed) processed by a Windows service or scheduled task, so the web request returns instantly and large batches never time out. Batches >501 students are split into chunks and bundled into a ZIP

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

- PHP 8.2 or higher with these extensions **enabled**: `intl`, `mbstring`, `json`, `mysqlnd`, `gd`, `zip`, `fileinfo`, `curl`
  - `intl` — CodeIgniter boot (`Locale` class); missing → `Class "Locale" not found` on every page
  - `gd` — mPDF renders the voucher background + signature images; missing → blank/broken images in PDFs
  - `zip` — `ZipArchive` bundles multi-chunk batches (>501 students) into a ZIP; missing → job fails with `Class "ZipArchive" not found`
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- A local web server (Apache/Nginx) or PHP's built-in server

> **A fresh XAMPP install ships with `intl`, `gd`, and `zip` commented out.** This is the #1 post-reinstall gotcha. See [Enable required PHP extensions](#enable-required-php-extensions) below — and remember the CLI worker uses the **same `php.ini`**, so restart it after editing.

### Enable required PHP extensions

Edit `C:\xampp\php\php.ini` and remove the leading `;` from each line:

```ini
extension=intl
extension=gd
extension=zip
extension=fileinfo
extension=curl
```

Then **restart Apache** (XAMPP Control Panel) **and** restart the PDF worker (it loads `php.ini` once at startup). Verify all are loaded:

```powershell
C:\xampp\php\php.exe -m | findstr /i "intl gd zip fileinfo curl"
```

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

4. **Import the database**

   The base tables (`users`, `students`, `school`, `signatories`, …) are **not** created by migrations — migrations only apply incremental column changes on top. Import the shared SQL dump first, then run migrations to top up the schema:

   ```powershell
   # create the empty database (name must match .env → database.default.database)
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS voucher_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"

   # import the dump your team shares (replace the path)
   C:\xampp\mysql\bin\mysql.exe -u root voucher_system < path\to\voucher_system.sql

   # apply any pending migrations on top of the imported schema
   php spark migrate
   ```

   > No dump yet? Ask a teammate to export one: `mysqldump -u root voucher_system > voucher_system.sql`. The dump is the source of truth for `users` (login uses `email` + `first_name/middle_name/last_name`) and `school` (drives the JHS/SHS dropdowns and `seed:test-students`).

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
                              └── JsonPdfQueue worker (Windows Service OR scheduled task)
                                     drains writable/pdf_queue/*.json
                                     writes PDFs to writable/pdfs/
```

Client PCs need nothing installed. Only the server PC runs the deployment steps below.

### Server one-time setup

1. **Install XAMPP** on the server PC (Apache + MySQL + PHP 8.2+).

2. **Enable the required PHP extensions** in `C:\xampp\php\php.ini` (`intl`, `gd`, `zip`, `fileinfo`, `curl`) — see [Enable required PHP extensions](#enable-required-php-extensions). A fresh XAMPP leaves these off; the app and the PDF worker will not work until they're on. Restart Apache after editing.

3. **Clone the repo:**

   ```powershell
   cd C:\xampp\htdocs
   git clone https://github.com/jgtrea/FinancialAssistanceProgram_G2.git
   cd FinancialAssistanceProgram_G2
   composer install
   ```

4. **Configure `.env`** as described in the Setup section above, then **import the database and run migrations** (migrations do not create the base tables — see [step 4 of Setup](#setup)):

   ```powershell
   C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS voucher_system CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
   C:\xampp\mysql\bin\mysql.exe -u root voucher_system < path\to\voucher_system.sql
   php spark migrate
   ```

5. **Make XAMPP services auto-start** (so Apache + MySQL come up on boot). Open XAMPP Control Panel → Config → check "Apache" and "MySQL" under "Autostart of modules", or install them as Windows services from the same panel. **MySQL must be running before the worker fires**, or drains fail to connect.

6. **Set server power plan to never sleep** (so the worker isn't paused at night):

   ```powershell
   powercfg /change standby-timeout-ac 0
   powercfg /change hibernate-timeout-ac 0
   ```

7. **Install the cron worker (scheduled task)** — the recommended default. Open an **Administrator PowerShell** in the project directory:

   ```powershell
   cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
   Set-ExecutionPolicy -Scope Process Bypass -Force
   .\install-cron-worker.ps1 -EveryMinutes 1
   ```

   The script:
   - Grants the `SYSTEM` account full access to `writable/` so drains can write logs and PDFs
   - Registers a Windows Scheduled Task `JsonPdfQueueCron` (auto-start on boot, runs as `SYSTEM`)
   - Fires every 1 minute: each fire spawns a fresh `php.exe`, drains the whole queue once (throttled, below-normal priority), and exits — so the box is free between fires and every run picks up the current `php.ini`

   `-EveryMinutes 1` keeps pickup near-interactive (queued jobs start within ~1 min). For tuning and the always-on service alternative, see [Worker options](#worker-options) below. **Install only one worker.**

8. **Open port 80 on the Windows firewall** so LAN clients can reach Apache. Admin PowerShell:

   ```powershell
   New-NetFirewallRule -DisplayName "XAMPP Apache (80)" -Direction Inbound -LocalPort 80 -Protocol TCP -Action Allow
   ```

9. **Bookmark the URL on each client PC:** `http://<server-lan-ip>/`

### Worker options

The PDF worker can run three ways. **Pick exactly one** — running two at once double-drains the queue.

| Mode | Installer | Process model | Pickup latency | Best for |
| ---- | --------- | ------------- | -------------- | -------- |
| **Cron (scheduled task)** — *recommended, this deployment* | `install-cron-worker.ps1` | no persistent process — scheduler spawns a one-shot drain that exits | up to the interval (~1 min) | lightest on a shared PC; idle between fires |
| Always-on service | `install-worker.ps1` | one persistent `php.exe`, drains every 5s | ~5s | snappiest pickup; one process always resident |
| Manual `.bat` | `run-json-pdf-worker.bat` | one `php.exe` in a console window | ~5s | local dev only (not boot-persistent) |

Cron and the service both auto-start on reboot and run as `SYSTEM`. The `.bat` does not survive reboot.

#### Cron worker (default)

Installed in [step 7](#server-one-time-setup) above with `.\install-cron-worker.ps1 -EveryMinutes 1`. Nothing runs between fires — the box is fully free for LAN users — and because each fire is a fresh `php.exe`, it always picks up the current code and `php.ini` (no restart needed after updates).

Tuning at install time (re-run the script to change; it replaces the existing task):

```powershell
.\install-cron-worker.ps1 -EveryMinutes 1                 # near-interactive (recommended)
.\install-cron-worker.ps1 -EveryMinutes 15 -Throttle 500  # gentler: fewer fires, longer pause between chunks
.\install-cron-worker.ps1 -At 19:00                       # nightly off-peak batch only
```

- `-EveryMinutes N` — minutes between fires. Lower = faster pickup. `1` keeps the status toast / auto-download responsive.
- `-Throttle N` — ms pause between each 501-student chunk so MySQL serves LAN users between renders (default 250).
- `-At HH:mm` — nightly single drain instead of recurring (overrides `-EveryMinutes`).

> **Trade-off:** a queued job waits up to the interval before pickup. For overnight-only batches a longer interval or `-At` is fine; for interactive use keep `-EveryMinutes 1`.

#### Managing the cron worker

```powershell
# status + last result (0 = OK)
Get-ScheduledTask     JsonPdfQueueCron | Select TaskName, State
Get-ScheduledTaskInfo JsonPdfQueueCron | Select LastRunTime, LastTaskResult, NextRunTime

# fire now (don't wait for the next interval)
Start-ScheduledTask -TaskName JsonPdfQueueCron

# stop an in-progress drain
Stop-ScheduledTask -TaskName JsonPdfQueueCron

# "restart" = stop current run + fire fresh
Stop-ScheduledTask -TaskName JsonPdfQueueCron ; Start-ScheduledTask -TaskName JsonPdfQueueCron

# pause / resume all future fires
Disable-ScheduledTask -TaskName JsonPdfQueueCron
Enable-ScheduledTask  -TaskName JsonPdfQueueCron

# remove entirely
Unregister-ScheduledTask -TaskName JsonPdfQueueCron -Confirm:$false
```

There's nothing to restart after a code/`php.ini` change — the next fire (≤ your interval) loads it automatically. Use `Start-ScheduledTask` if you want it applied immediately. The finished-job file sweep runs **inside each drain**, so cleanup cadence matches your fire interval.

#### Always-on service (alternative)

Only if you want ~5s pickup and don't mind one resident `php.exe`. Requires NSSM:

```powershell
# install NSSM (Administrator PowerShell), then reopen the shell so PATH updates
winget install -e --id NSSM.NSSM
nssm version
# (no winget? download from https://nssm.cc/download and copy nssm.exe into C:\Windows\System32\)

# install + start the service
cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
Set-ExecutionPolicy -Scope Process Bypass -Force
.\install-worker.ps1
```

`install-worker.ps1` grants `SYSTEM` access to `writable/`, registers `JsonPdfQueue` (auto-start + auto-restart on crash), logs to `writable\logs\json-pdf-worker.log`, and starts it. Manage it:

```powershell
nssm start   JsonPdfQueue          # start
nssm stop    JsonPdfQueue          # stop
nssm restart JsonPdfQueue          # bounce (do this after pulling code changes)
nssm status  JsonPdfQueue          # check
nssm remove  JsonPdfQueue confirm  # uninstall entirely
```

> **Switching cron ⇆ service:** remove the one you're leaving so they don't both drain. Cron → service: `Unregister-ScheduledTask -TaskName JsonPdfQueueCron -Confirm:$false`, then `.\install-worker.ps1`. Service → cron: `nssm stop JsonPdfQueue ; nssm remove JsonPdfQueue confirm` (plus `JsonPdfQueue2`, … if you added extras), then `.\install-cron-worker.ps1 -EveryMinutes 1`.

### Verifying the worker is alive

```powershell
# cron (this deployment): task present + last fire succeeded
Get-ScheduledTask     JsonPdfQueueCron | Select TaskName, State
Get-ScheduledTaskInfo JsonPdfQueueCron | Select LastRunTime, LastTaskResult, NextRunTime

# always-on service alternative:
Get-Service JsonPdfQueue           # Status should be Running

# either way, watch the log
Get-Content "C:\xampp\htdocs\FinancialAssistanceProgram_G2\writable\logs\json-pdf-worker.log" -Tail 30 -Wait
```

Cron drains log a `Drained: N done, …` line per fire that did work. The always-on service logs `JSON worker running. Polling every 5s.` plus a heartbeat every ~60s.

### Scaling: running multiple workers

One worker drains chunks one at a time. For faster throughput on large batches, run several in parallel. Job claiming is **concurrency-safe** — each chunk is claimed under an exclusive file lock (`flock(LOCK_EX)` in `JsonPdfQueue::mutateAll`), so two workers never render the same chunk.

> **On cron (this deployment):** pass `-Drainers N` to the installer to fire N parallel one-shot drains per interval — `\.install-cron-worker.ps1 -EveryMinutes 1 -Drainers 2`. That's the cron equivalent of the multi-service setup below; you don't need the NSSM steps. Keep `N` ≤ CPU cores minus headroom for Apache/MySQL.

The rest of this section is for the **always-on service** alternative — one service per worker, each with a **unique service name** but the same command.

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

### Keeping generation light on a shared PC

The server PC is shared by the whole LAN (and often doubles as the admin's workstation), so a huge batch — e.g. 50,000 vouchers — could otherwise saturate CPU/MySQL and slow everyone else's data entry. Two mechanisms keep that in check, configured automatically by the installer (`install-cron-worker.ps1` for cron, `install-worker.ps1` for the service):

1. **Instant enqueue.** Clicking Generate only writes the job to the queue and returns immediately — the heavy rendering happens in the background worker, never in the web request. The user who generates is never blocked, and the request doesn't tie up an Apache thread.
2. **A throttled, below-normal-priority worker.** The service runs:

   ```
   spark run:json-pdf-queue 5 --throttle=250
   ```

   - `5` — poll the queue every 5 seconds (drop to `2` for snappier small-batch pickup).
   - `--throttle=250` — pause 250 ms between each 501-student chunk, so MySQL can serve other users between renders.
   - `BELOW_NORMAL_PRIORITY_CLASS` (set by the installer) — the worker yields CPU to Apache/MySQL and to the admin's foreground apps.

The throttle scales with batch size: a small batch is a single chunk (no pauses) and renders fast, while a 50k batch renders gradually without freezing the other users. Below-normal priority also means a job generated while nobody else is busy still runs at full speed.

#### Tune the throttle

If data entry still lags during big runs, increase the pause (slower generation, gentler on the box).

**Cron (this deployment)** — just re-run the installer with a bigger `-Throttle` (it replaces the task):

```powershell
.\install-cron-worker.ps1 -EveryMinutes 1 -Throttle 500
```

**Always-on service** — update the service parameters:

```powershell
nssm stop  JsonPdfQueue
nssm set   JsonPdfQueue AppParameters "spark run:json-pdf-queue 5 --throttle=500"
nssm start JsonPdfQueue
nssm get   JsonPdfQueue AppParameters    # verify
```

### Updating the deployment

```powershell
cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
git pull
composer install --no-dev --optimize-autoloader
php spark migrate
# cron (this deployment): nothing else to do — the next fire runs the new code.
# Apply immediately:  Start-ScheduledTask -TaskName JsonPdfQueueCron
```

> On the **always-on service** alternative you must bounce it so it loads the new code: `nssm restart JsonPdfQueue` (multiple workers: `Get-Service JsonPdfQueue* | ForEach-Object { nssm restart $_.Name }`).

### Troubleshooting

| Symptom                                    | Cause / Fix                                                                                                           |
| ------------------------------------------ | --------------------------------------------------------------------------------------------------------------------- |
| `Class "Locale" not found` on every page   | `intl` extension off. Enable `extension=intl` in `php.ini`, restart Apache. See [Enable required PHP extensions](#enable-required-php-extensions). |
| Job fails: `Class "ZipArchive" not found`  | `zip` extension off — multi-chunk batches (>501) can't build the ZIP. Enable `extension=zip`, restart Apache **and the worker**, re-generate. |
| Voucher PDF has blank background / broken signature images | `gd` extension off — mPDF can't decode PNG/JPG. Enable `extension=gd`, restart the worker. (Also check signature files exist under `writable/uploads/signatures/`.) |
| Extension enabled but still failing        | The worker loads `php.ini` once at startup. After editing `php.ini`, **restart the worker** (`nssm restart JsonPdfQueue`, or just wait one cron fire — fresh process). |
| Service stuck `Paused`                     | NSSM throttled it after fast crashes. `sc.exe stop JsonPdfQueue`, then check the log.                                 |
| Log full of "file used by another process" | An old `php.exe` is still holding the file. `Get-Process php \| Stop-Process -Force`, then `nssm start JsonPdfQueue`. |
| Worker starts then exits immediately       | Usually MySQL not yet up at boot, or `php.exe` path wrong. Check log; restart MySQL if needed.                        |
| Jobs queue but never process               | No worker running. Service: `Get-Service JsonPdfQueue`. Cron: `Get-ScheduledTask JsonPdfQueueCron*`. Neither installed → install one. |
| Status modal shows `queued` forever        | Same as above — worker is down (or, on cron, just waiting for the next fire).                                        |
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
