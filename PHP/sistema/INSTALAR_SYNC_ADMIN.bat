@echo off
echo ================================================
echo   INSTALADOR DE SINCRONIZACION AUTOMATICA
echo ================================================
echo.
echo Este script creara 2 tareas programadas que
echo sincronizaran la base de datos cada 1 minuto:
echo.
echo   1. VendingBox_Sync_Nube  (Local --^> Nube)
echo   2. VendingBox_Sync_Local (Nube --^> Local)
echo.
echo ================================================
echo   IMPORTANTE: Haz CLIC DERECHO en este archivo
echo   y selecciona "Ejecutar como administrador"
echo ================================================
echo.
pause

REM Verificar si tiene permisos de administrador
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo.
    echo ERROR: No tienes permisos de administrador
    echo.
    echo Haz CLIC DERECHO en este archivo y selecciona
    echo "Ejecutar como administrador"
    echo.
    pause
    exit /b 1
)

echo.
echo ✅ Permisos de administrador: OK
echo.

REM Crear tarea 1: Sync Nube (Local -> Nube)
echo Creando tarea VendingBox_Sync_Nube...
schtasks /Create /TN "VendingBox_Sync_Nube" /TR "PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File \"C:\xampp\htdocs\vendingbox.online\sistema\sincronizar_nube.ps1\"" /SC MINUTE /MO 1 /RU SYSTEM /RL HIGHEST /F /V1
if %errorLevel% equ 0 (
    echo ✅ Tarea VendingBox_Sync_Nube creada
) else (
    echo ❌ Error al crear tarea Nube: %errorLevel%
    pause
    exit /b 1
)

REM Iniciar la tarea inmediatamente
schtasks /Run /TN "VendingBox_Sync_Nube"

echo.

REM Crear tarea 2: Sync Local (Nube -> Local)
echo Creando tarea VendingBox_Sync_Local...
schtasks /Create /TN "VendingBox_Sync_Local" /TR "PowerShell.exe -NoProfile -ExecutionPolicy Bypass -File \"C:\xampp\htdocs\vendingbox.online\sistema\sincronizar_local.ps1\"" /SC MINUTE /MO 1 /RU SYSTEM /RL HIGHEST /F /V1
if %errorLevel% equ 0 (
    echo ✅ Tarea VendingBox_Sync_Local creada
) else (
    echo ❌ Error al crear tarea Local: %errorLevel%
    pause
    exit /b 1
)

REM Iniciar la tarea inmediatamente
schtasks /Run /TN "VendingBox_Sync_Local"

echo.
echo ================================================
echo   ✅ INSTALACION COMPLETADA
echo ================================================
echo.
echo Tareas creadas:
schtasks /Query /TN "VendingBox_*" /FO TABLE
echo.
echo La sincronizacion se ejecutara automaticamente
echo cada 1 minuto en segundo plano.
echo.
echo Logs: C:\xampp\htdocs\vendingbox.online\sistema\logs\
echo.
pause
