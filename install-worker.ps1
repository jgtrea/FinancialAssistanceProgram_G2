# install-worker.ps1
# One-time installer for the JSON-PDF queue background worker.
# Run once per machine, as Administrator. Idempotent -- safe to re-run.
#
# Usage:
#   Right-click → Run with PowerShell (as Admin)
#   OR from elevated PowerShell:
#     Set-ExecutionPolicy -Scope Process Bypass -Force
#     .\install-worker.ps1
#
# Method (in priority order):
#   1. If nssm.exe is on PATH → installs a Windows Service (preferred).
#   2. Otherwise → registers a Scheduled Task running at boot as SYSTEM.

$ErrorActionPreference = 'Stop'

# --- Resolve paths ---------------------------------------------------------
$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$batPath    = Join-Path $projectDir 'run-json-pdf-worker.bat'
$logDir     = Join-Path $projectDir 'writable\logs'
$logFile    = Join-Path $logDir   'json-pdf-worker.log'
$serviceName = 'JsonPdfQueue'

Write-Host "Project dir: $projectDir"
Write-Host "Worker bat:  $batPath"

# --- Admin check -----------------------------------------------------------
$isAdmin = ([Security.Principal.WindowsPrincipal] `
    [Security.Principal.WindowsIdentity]::GetCurrent()
).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Error "Must run as Administrator. Right-click PowerShell → Run as administrator."
    exit 1
}

# --- Sanity checks ---------------------------------------------------------
if (-not (Test-Path $batPath)) {
    Write-Error "Missing $batPath. Pull the latest from git."
    exit 1
}

if (-not (Test-Path 'C:\xampp\php\php.exe')) {
    Write-Warning "C:\xampp\php\php.exe not found. The .bat assumes XAMPP at C:\xampp. Edit run-json-pdf-worker.bat if PHP lives elsewhere."
}

if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir | Out-Null
    Write-Host "Created $logDir"
}

# --- Grant SYSTEM full access to writable\ (the service runs as SYSTEM) ---
$writableDir = Join-Path $projectDir 'writable'
icacls $writableDir /grant "SYSTEM:(OI)(CI)F" /T | Out-Null
Write-Host "Granted SYSTEM full access on $writableDir"

# --- Method 1: NSSM service (preferred) -----------------------------------
$nssm = Get-Command nssm.exe -ErrorAction SilentlyContinue
if ($nssm) {
    Write-Host ""
    Write-Host "NSSM detected at $($nssm.Source). Installing Windows Service '$serviceName'..."

    # Remove any prior install
    $existing = Get-Service $serviceName -ErrorAction SilentlyContinue
    if ($existing) {
        Write-Host "Existing service found -- stopping + removing first."
        & nssm stop $serviceName confirm | Out-Null
        & nssm remove $serviceName confirm | Out-Null
    }

    # Invoke php.exe directly (no .bat middleman) so NSSM owns the process and
    # captures stdout/stderr cleanly. The .bat in the repo is kept for manual
    # testing only.
    $phpExe = "C:\xampp\php\php.exe"
    & nssm install $serviceName $phpExe "spark" "run:json-pdf-queue" "5"
    & nssm set $serviceName AppDirectory $projectDir
    & nssm set $serviceName Start SERVICE_AUTO_START
    & nssm set $serviceName AppExit Default Restart
    & nssm set $serviceName AppRestartDelay 5000
    & nssm set $serviceName AppThrottle 0
    & nssm set $serviceName AppStdout $logFile
    & nssm set $serviceName AppStderr $logFile
    & nssm set $serviceName Description "Background PDF voucher worker (JSON file queue)"
    & nssm start $serviceName

    Write-Host ""
    Write-Host "Service '$serviceName' installed + started."
    Write-Host "Manage with:  nssm restart $serviceName  |  nssm stop $serviceName  |  nssm remove $serviceName confirm"
    Write-Host "Log file:     $logFile"
    exit 0
}

# --- Method 2: Scheduled Task fallback -------------------------------------
Write-Host ""
Write-Host "NSSM not found -- falling back to Scheduled Task (also boot-time, also SYSTEM)."
Write-Host "To upgrade later: install nssm (https://nssm.cc) and re-run this script."

$existingTask = Get-ScheduledTask -TaskName $serviceName -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "Existing task found -- removing first."
    Unregister-ScheduledTask -TaskName $serviceName -Confirm:$false
}

# Call php.exe directly with WorkingDirectory set, so the worker doesn't
# rely on the .bat wrapper (which fights NSSM/Task Scheduler for stdout).
$action = New-ScheduledTaskAction `
    -Execute "C:\xampp\php\php.exe" `
    -Argument "spark run:json-pdf-queue 5" `
    -WorkingDirectory $projectDir
$trigger = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet `
    -RestartCount 999 `
    -RestartInterval (New-TimeSpan -Minutes 1) `
    -ExecutionTimeLimit ([TimeSpan]::Zero) `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries

Register-ScheduledTask `
    -TaskName $serviceName `
    -Description "Background PDF voucher worker (JSON file queue)" `
    -Action $action `
    -Trigger $trigger `
    -Principal $principal `
    -Settings $settings | Out-Null

Start-ScheduledTask -TaskName $serviceName

Write-Host ""
Write-Host "Scheduled task '$serviceName' installed + started."
Write-Host "Manage with:  Stop-ScheduledTask $serviceName  |  Start-ScheduledTask $serviceName  |  Unregister-ScheduledTask $serviceName -Confirm:`$false"
Write-Host "Log file:     $logFile"
