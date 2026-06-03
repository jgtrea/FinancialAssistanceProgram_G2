# install-cron-worker.ps1
# Registers the PDF-queue drain as a Windows Scheduled Task ("cron job").
#
# Unlike the always-on service (install-worker.ps1) this does NOT keep a php.exe
# running. The scheduler fires the drain on a schedule; each run processes the
# whole queue once (throttled, below-normal priority) and exits. Between fires,
# zero worker processes are alive -> the server is fully free for LAN users.
#
# Two modes:
#   Recurring (default): drain every N minutes, all day. PDFs still come out
#                        during the work day, just in gentle scheduled bursts.
#       .\install-cron-worker.ps1                       # every 15 min, 250ms throttle
#       .\install-cron-worker.ps1 -EveryMinutes 30 -Throttle 500
#
#   Nightly: drain once at a fixed time (heaviest off-peak option).
#       .\install-cron-worker.ps1 -At 19:00
#
# Run once, as Administrator, from the project directory:
#       Set-ExecutionPolicy -Scope Process Bypass -Force
#       .\install-cron-worker.ps1
#
# IMPORTANT: cron and the always-on service are mutually exclusive. If the
# service is installed, remove it first so they don't both drain:
#       nssm stop JsonPdfQueue ; nssm remove JsonPdfQueue confirm
#       # plus JsonPdfQueue2, JsonPdfQueue3, ... if you added extra workers

param(
    [int]    $EveryMinutes = 15,    # recurring: minutes between drains (ignored if -At set)
    [string] $At           = '',    # nightly: HH:mm local time; when set, overrides recurring
    [int]    $Throttle     = 250,   # ms pause between chunks (DB breathing room for other users)
    [int]    $Drainers     = 1      # parallel drainers per fire (claims are atomic; keep at 1 for low load)
)

$ErrorActionPreference = 'Stop'

$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Definition
$phpExe     = 'C:\xampp\php\php.exe'
$logDir     = Join-Path $projectDir 'writable\logs'

# --- Admin check -----------------------------------------------------------
$isAdmin = ([Security.Principal.WindowsPrincipal] `
    [Security.Principal.WindowsIdentity]::GetCurrent()
).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Error 'Must run as Administrator. Right-click PowerShell -> Run as administrator.'
    exit 1
}

if (-not (Test-Path $phpExe)) {
    Write-Warning "$phpExe not found. Edit `$phpExe in this script if PHP lives elsewhere."
}
if (-not (Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir | Out-Null
}

# SYSTEM must be able to write PDFs/logs (same grant the service installer does).
icacls (Join-Path $projectDir 'writable') /grant 'SYSTEM:(OI)(CI)F' /T | Out-Null

# One-shot drain (no interval number = drain-then-exit), throttled.
$argument = "spark run:json-pdf-queue --throttle=$Throttle"

# --- Build the trigger: nightly if -At given, else recurring every N min ----
if ($At -ne '') {
    $trigger = New-ScheduledTaskTrigger -Daily -At $At
    $modeMsg = "daily at $At"
} else {
    # Fire ~now, then repeat forever every N minutes.
    $trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) `
        -RepetitionInterval (New-TimeSpan -Minutes $EveryMinutes)
    $modeMsg = "every $EveryMinutes min"
}

$principal = New-ScheduledTaskPrincipal -UserId 'SYSTEM' -RunLevel Highest

# Priority 7 = below normal: a long drain yields CPU to Apache/MySQL.
# MultipleInstances IgnoreNew: if a drain is still running when the next fire
# arrives, skip the new one instead of stacking concurrent drains.
$settings = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Hours 8) `
    -MultipleInstances IgnoreNew `
    -Priority 7

for ($i = 1; $i -le $Drainers; $i++) {
    $taskName = if ($i -eq 1) { 'JsonPdfQueueCron' } else { "JsonPdfQueueCron$i" }

    $existing = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
    if ($existing) {
        Write-Host "Existing task '$taskName' found -- removing first."
        Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    }

    $action = New-ScheduledTaskAction -Execute $phpExe -Argument $argument -WorkingDirectory $projectDir

    Register-ScheduledTask `
        -TaskName $taskName `
        -Description "PDF voucher queue drain (cron, #$i) - $modeMsg, ${Throttle}ms throttle" `
        -Action $action -Trigger $trigger -Principal $principal -Settings $settings | Out-Null

    Write-Host "Scheduled '$taskName' -> $modeMsg, ${Throttle}ms between chunks, below-normal priority."
}

Write-Host ''
Write-Host "Done. Cron drain installed ($modeMsg)."
Write-Host 'Run now to test:   Start-ScheduledTask -TaskName JsonPdfQueueCron'
Write-Host 'List:              Get-ScheduledTask JsonPdfQueueCron*'
Write-Host 'Remove:            Unregister-ScheduledTask -TaskName JsonPdfQueueCron -Confirm:$false'
