@echo off
REM Background worker for the JSON-file PDF queue.
REM Polls writable/pdf_queue/queue.json every 5 seconds.
REM Logs to writable/logs/json-pdf-worker.log

cd /d "%~dp0"

if not exist "writable\logs" mkdir "writable\logs"

C:\xampp\php\php.exe spark run:json-pdf-queue 5 >> "writable\logs\json-pdf-worker.log" 2>&1
