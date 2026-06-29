# Configuration Reference

> Full technical reference for the Financial Assistance Program — Voucher Management System.
> For step-by-step installation see [SETUP.md](SETUP.md). For usage see [MANUAL.md](MANUAL.md).

---

## Tech Stack

| Layer              | Technology                              |
| ------------------ | --------------------------------------- |
| Framework          | CodeIgniter 4 (PHP 8.2+)                |
| PDF Generation     | mPDF 8.x                                |
| Spreadsheet Import | PhpSpreadsheet 5.x                      |
| Database           | MySQL (via CodeIgniter's Query Builder) |
| Frontend           | Bootstrap 5 + vanilla JS + Select2      |
| Testing            | PHPUnit 10                              |

---

## Folder Structure

```
FinancialAssistanceProgram_G2/
├── app/
│   ├── Config/             # App configuration (routes, filters, database, etc.)
│   ├── Controllers/
│   │   ├── Admin/          # Admin-only controllers (Dashboard, Voucher, Report); School shared with Staff
│   │   ├── User/           # Staff controllers (Dashboard, Voucher)
│   │   ├── Authentication.php
│   │   ├── ProfileController.php
│   │   ├── SignatoryController.php
│   │   ├── StudentController.php
│   │   ├── ArchiveController.php
│   │   ├── AuditLogController.php
│   │   ├── JobController.php
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
│       ├── admin/          # Admin-specific pages (includes audit_logs/)
│       ├── audit_logs/     # Staff audit log view
│       ├── auth/           # Login page
│       ├── dashboard/      # Dashboard (recent students)
│       ├── layouts/        # Base layout (main.php)
│       ├── partials/       # Shared sidebar, topbar, footer
│       ├── schools/        # School management list
│       ├── signatories/    # Signatory list and form
│       ├── vouchers/       # Voucher list, form, generate, view
│       └── archive/        # Archive index
├── public/                 # Web root (index.php, assets)
├── writable/               # Logs, cache, uploads (not committed)
├── composer.json
└── .env                    # Local environment config (not committed)
```

---

## Environment Variables (`.env`)

Copy `env` to `.env` and set at minimum:

```ini
CI_ENVIRONMENT = development

app.baseURL = 'http://localhost:8080/'

database.default.hostname = localhost
database.default.database = your_db_name
database.default.username = your_db_user
database.default.password = your_db_password
database.default.DBDriver = MySQLi
```

---

## Required PHP Extensions

| Extension  | Why it's needed                                                        |
| ---------- | ---------------------------------------------------------------------- |
| `intl`     | CodeIgniter boot (`Locale` class) — missing → `Class "Locale" not found` on every page |
| `mbstring` | String handling across the app                                         |
| `json`     | JSON encode/decode used throughout                                     |
| `mysqlnd`  | MySQL native driver                                                     |
| `gd`       | mPDF renders voucher background + signature images — missing → blank PDFs |
| `zip`      | `ZipArchive` bundles multi-chunk batches (>501 students) — missing → `Class "ZipArchive" not found` |
| `fileinfo` | MIME-type detection for uploads                                        |
| `curl`     | HTTP client used by CodeIgniter                                        |

Edit `C:\xampp\php\php.ini` and remove the leading `;` from:

```ini
extension=intl
extension=gd
extension=zip
extension=fileinfo
extension=curl
```

Then **restart Apache** and restart the PDF worker. Verify:

```powershell
C:\xampp\php\php.exe -m | findstr /i "intl gd zip fileinfo curl"
```

> **A fresh XAMPP install ships with `intl`, `gd`, and `zip` commented out.** This is the #1 post-reinstall gotcha. The CLI worker loads `php.ini` once at startup — restart it after editing.

---

## Role-Based Access

| Feature                | Admin | Staff |
| ---------------------- | :---: | :---: |
| Student Management     |  ✓   |  ✓   |
| School Management      |  ✓   |  ✓   |
| Voucher Generation     |  ✓   |  ✓   |
| Excel Import           |  ✓   |  ✓   |
| Profile                |  ✓   |  ✓   |
| Audit Logs (own)       |  ✓   |  ✓   |
| Audit Logs (all users) |  ✓   |  ✗   |
| Signatory Management   |  ✓   |  ✗   |
| Archive                |  ✓   |  ✗   |
| User Management        |  ✓   |  ✗   |

Authentication uses `PASSWORD_ARGON2ID` for password hashing.

---

## PDF Worker

Voucher PDFs are generated asynchronously via a JSON-file-backed job queue. The web request enqueues the job and returns immediately; a background worker drains the queue.

### Worker modes

The PDF worker can run three ways. **Pick exactly one** — running two at once double-drains the queue.

| Mode                                                       | Installer                 | Process model                                                        | Pickup latency              | Best for                                      |
| ---------------------------------------------------------- | ------------------------- | -------------------------------------------------------------------- | --------------------------- | --------------------------------------------- |
| **Cron (scheduled task)** — _recommended_                  | `install-cron-worker.ps1` | no persistent process — scheduler spawns a one-shot drain that exits | up to the interval (~1 min) | lightest on a shared PC; idle between fires   |
| Always-on service                                          | `install-worker.ps1`      | one persistent `php.exe`, drains every 5s                            | ~5s                         | snappiest pickup; one process always resident |
| Manual `.bat`                                              | `run-json-pdf-worker.bat` | one `php.exe` in a console window                                    | ~5s                         | local dev only (not boot-persistent)          |

### Cron worker tuning

```powershell
.\install-cron-worker.ps1 -EveryMinutes 1                 # near-interactive (recommended)
.\install-cron-worker.ps1 -EveryMinutes 15 -Throttle 500  # gentler: fewer fires, longer pause between chunks
.\install-cron-worker.ps1 -At 19:00                       # nightly off-peak batch only
```

- `-EveryMinutes N` — minutes between fires. `1` keeps the status toast / auto-download responsive.
- `-Throttle N` — ms pause between each 501-student chunk (default 250).
- `-At HH:mm` — nightly single drain instead of recurring (overrides `-EveryMinutes`).
- `-Drainers N` — parallel drains per interval for faster throughput on large batches.

### Managing the cron worker

```powershell
Get-ScheduledTask     JsonPdfQueueCron | Select TaskName, State
Get-ScheduledTaskInfo JsonPdfQueueCron | Select LastRunTime, LastTaskResult, NextRunTime

Start-ScheduledTask  -TaskName JsonPdfQueueCron   # fire now
Stop-ScheduledTask   -TaskName JsonPdfQueueCron   # stop in-progress drain
Disable-ScheduledTask -TaskName JsonPdfQueueCron  # pause all future fires
Enable-ScheduledTask  -TaskName JsonPdfQueueCron  # resume
Unregister-ScheduledTask -TaskName JsonPdfQueueCron -Confirm:$false  # remove
```

### Always-on service (alternative)

Requires NSSM:

```powershell
winget install -e --id NSSM.NSSM
cd C:\xampp\htdocs\FinancialAssistanceProgram_G2
Set-ExecutionPolicy -Scope Process Bypass -Force
.\install-worker.ps1
```

```powershell
nssm start   JsonPdfQueue
nssm stop    JsonPdfQueue
nssm restart JsonPdfQueue   # do this after pulling code changes
nssm status  JsonPdfQueue
nssm remove  JsonPdfQueue confirm
```

> **Switching cron ⇆ service:** remove the old one first. Cron → service: `Unregister-ScheduledTask -TaskName JsonPdfQueueCron -Confirm:$false`, then `.\install-worker.ps1`. Service → cron: `nssm stop JsonPdfQueue ; nssm remove JsonPdfQueue confirm`, then `.\install-cron-worker.ps1 -EveryMinutes 1`.

### Scaling: multiple workers

Job claiming is concurrency-safe (`flock(LOCK_EX)` in `JsonPdfQueue::mutateAll`) — two workers never render the same chunk.

**Cron:** pass `-Drainers N` to the installer.

**Service (NSSM):** register additional services as `JsonPdfQueue2`, `JsonPdfQueue3`, etc.:

```powershell
$projectDir = "C:\xampp\htdocs\FinancialAssistanceProgram_G2"
$name       = "JsonPdfQueue2"
$logFile    = "$projectDir\writable\logs\json-pdf-worker-2.log"

nssm install $name "C:\xampp\php\php.exe" "spark" "run:json-pdf-queue" "5"
nssm set $name AppDirectory   $projectDir
nssm set $name Start          SERVICE_AUTO_START
nssm set $name AppExit        Default Restart
nssm set $name AppRestartDelay 5000
nssm set $name AppStdout      $logFile
nssm set $name AppStderr      $logFile
nssm start $name
```

A good starting point is one worker per CPU core, leaving headroom for Apache + MySQL.

### Throttling on a shared PC

The installer sets `BELOW_NORMAL_PRIORITY_CLASS` and `--throttle=250` automatically. Increase if data entry lags during large runs:

```powershell
# Cron
.\install-cron-worker.ps1 -EveryMinutes 1 -Throttle 500

# Always-on service
nssm stop JsonPdfQueue
nssm set  JsonPdfQueue AppParameters "spark run:json-pdf-queue 5 --throttle=500"
nssm start JsonPdfQueue
```

---

## Logs and Queue Files

| Path                                 | Contents                                    |
| ------------------------------------ | ------------------------------------------- |
| `writable/logs/json-pdf-worker.log`  | Worker stdout/stderr                        |
| `writable/pdf_queue/queue.json`      | Pending jobs (parent + chunks)              |
| `writable/pdf_queue/processing.json` | Jobs currently being rendered               |
| `writable/pdf_queue/finished.json`   | Done + failed jobs (with final `file_path`) |
| `writable/pdfs/`                     | Generated PDF and ZIP files                 |

The three JSON files are append-rotated by the worker; you can safely back them up but don't edit them while the worker is running.

---

## Troubleshooting

| Symptom                                                    | Cause / Fix                                                                                                                                                            |
| ---------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `Class "Locale" not found` on every page                   | `intl` extension off. Enable `extension=intl` in `php.ini`, restart Apache.                                                                                           |
| Job fails: `Class "ZipArchive" not found`                  | `zip` extension off — multi-chunk batches (>501) can't build the ZIP. Enable `extension=zip`, restart Apache **and the worker**, re-generate.                          |
| Voucher PDF has blank background / broken signature images | `gd` extension off — mPDF can't decode PNG/JPG. Enable `extension=gd`, restart the worker. (Also check signature files exist under `writable/uploads/signatures/`.)    |
| Extension enabled but still failing                        | The worker loads `php.ini` once at startup. After editing `php.ini`, **restart the worker** (`nssm restart JsonPdfQueue`, or just wait one cron fire — fresh process). |
| Service stuck `Paused`                                     | NSSM throttled it after fast crashes. `sc.exe stop JsonPdfQueue`, then check the log.                                                                                  |
| Log full of "file used by another process"                 | An old `php.exe` is still holding the file. `Get-Process php \| Stop-Process -Force`, then `nssm start JsonPdfQueue`.                                                  |
| Worker starts then exits immediately                       | Usually MySQL not yet up at boot, or `php.exe` path wrong. Check log; restart MySQL if needed.                                                                         |
| Jobs queue but never process                               | No worker running. Service: `Get-Service JsonPdfQueue`. Cron: `Get-ScheduledTask JsonPdfQueueCron*`. Neither installed → install one.                                  |
| Status modal shows `queued` forever                        | Same as above — worker is down (or, on cron, just waiting for the next fire).                                                                                          |
| Permission denied writing PDFs                             | Re-run `icacls "C:\xampp\htdocs\FinancialAssistanceProgram_G2\writable" /grant "SYSTEM:(OI)(CI)F" /T`                                                                  |
