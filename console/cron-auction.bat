@echo off
rem Prefer cron-auction.vbs for Task Scheduler (no visible CMD window).
rem This .bat is only for manual testing from an already-open console.

setlocal
cd /d "%~dp0.."
if not exist "console\runtime\logs" mkdir "console\runtime\logs"
"C:\Program Files\PHP\php.exe" yii auction/tick >> "console\runtime\logs\cron-auction.log" 2>&1
endlocal
