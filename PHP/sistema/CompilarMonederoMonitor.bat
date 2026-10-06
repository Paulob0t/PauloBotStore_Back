@echo off
cls
echo ========================================
echo   COMPILAR A .EXE - Monedero Monitor
echo ========================================
echo.
echo Este proceso instalara ps2exe (si no lo tienes)
echo y compilara MonederoMonitor.ps1 a un .exe
echo.
echo Presiona cualquier tecla para continuar...
pause >nul
echo.

cd /d "%~dp0"

powershell.exe -ExecutionPolicy Bypass -NoProfile -File "CompilarMonederoMonitor.ps1"

echo.
echo Proceso finalizado
pause
