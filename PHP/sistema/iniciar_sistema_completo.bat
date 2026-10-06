@echo off
chcp 65001 >nul 2>&1
color 0A
title 🚀 VendigBox - Inicio Completo

echo.
echo ═══════════════════════════════════════════════════════════
echo     🚀 VENDIGBOX - SISTEMA COMPLETO
echo ═══════════════════════════════════════════════════════════
echo.

REM Verificar si XAMPP está corriendo
echo [1/4] 🔍 Verificando XAMPP...
tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I /N "httpd.exe">NUL
if "%ERRORLEVEL%"=="0" (
    echo     ✅ Apache está corriendo
) else (
    echo     ⚠️  Apache no está corriendo
    echo     📌 Iniciando Apache...
    start "" "C:\xampp\apache_start.bat"
    timeout /t 3 >nul
)

REM Iniciar gestor COM5 del monedero (com5_manager.php)
echo.
echo [3/4] 🪙 Iniciando gestor COM5 del monedero...
wmic process where "name='php.exe' and commandline like '%%com5_manager%%'" get processid 2>nul | findstr /R "[0-9]" >nul
if "%ERRORLEVEL%"=="0" (
    echo     ℹ️  com5_manager ya está corriendo
) else (
    echo     📡 Iniciando com5_manager en background...
    start "" /min wscript.exe "%~dp0iniciar_listener_invisible.vbs"
    timeout /t 3 >nul
    echo     ✅ Gestor COM5 iniciado
)

REM Abrir navegador
echo.
echo [4/4] 🌐 Abriendo sistema en navegador...
timeout /t 1 >nul
start "" "http://localhost/vendingbox/index.php"

echo.
echo ═══════════════════════════════════════════════════════════
echo     ✅ SISTEMA INICIADO CORRECTAMENTE
echo ═══════════════════════════════════════════════════════════
echo.
echo 💡 Componentes activos:
echo    • Apache Web Server
echo    • MySQL Database
echo    • Gestor COM5 / Monedero (com5_manager.php)
echo    • Sistema de Ventas
echo.
echo 📋 Comandos útiles:
echo    • MonederoMonitor.bat - Panel de escritorio del monedero
echo    • MonederoMonitor.bat - Panel de monedero (recomendado)
echo    • detener_listener.bat - Detener gestor COM5
echo    • test_dispensar_cambio.php - Probar dispensado
echo    • verificar_sistema.bat - Diagnostico completo
echo.
echo Presiona cualquier tecla para cerrar esta ventana...
pause >nul
