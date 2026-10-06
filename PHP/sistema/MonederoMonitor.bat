@echo off
cls
echo ========================================
echo   MONEDERO MONITOR - VendingBox
echo ========================================
echo.
echo Iniciando aplicacion de escritorio...
echo.

cd /d "%~dp0"

REM Ejecutar el script PowerShell con GUI
powershell.exe -ExecutionPolicy Bypass -NoProfile -File "MonederoMonitor.ps1"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo ERROR: No se pudo iniciar la aplicacion
    echo.
    pause
)
