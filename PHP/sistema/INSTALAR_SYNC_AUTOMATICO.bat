@echo off
echo ============================================
echo   INSTALANDO SINCRONIZACION AUTOMATICA
echo ============================================
echo.
echo Este script instalara tareas programadas que
echo sincronizaran la BD cada 1 minuto.
echo.
echo REQUIERE PERMISOS DE ADMINISTRADOR
echo.
pause

PowerShell -NoProfile -ExecutionPolicy Bypass -Command "& {Start-Process PowerShell -ArgumentList '-NoProfile -ExecutionPolicy Bypass -File ""%~dp0configurar_sync_rapido.ps1""' -Verb RunAs}"

echo.
echo Verifica que se hayan creado las tareas.
echo.
pause
