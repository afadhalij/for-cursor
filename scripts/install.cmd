@echo off
REM Inganzo Ngari — Install + Auto-host launcher (double-click to run)
REM This script self-elevates to Administrator and runs the PowerShell installer.

SET "SCRIPT_DIR=%~dp0"
SET "PS1=%SCRIPT_DIR%install-and-autohost.ps1"

IF NOT EXIST "%PS1%" (
  echo Could not find install-and-autohost.ps1 next to this file.
  pause
  exit /b 1
)

REM Self-elevate to admin if not already.
fsutil dirty query %systemdrive% >nul 2>&1
IF %errorLevel% NEQ 0 (
  echo Requesting administrator rights...
  powershell -NoProfile -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
  exit /b
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%PS1%"
pause
