@echo off
cls
echo ========================================
echo  VERIFICAR ESTADO DEL LISTENER
echo ========================================
echo.

echo [1] Verificando procesos PHP...
echo.
tasklist /FI "IMAGENAME eq php.exe" 2>nul | find /I "php.exe" >nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Listener CORRIENDO
    echo.
    echo Detalles:
    tasklist /FI "IMAGENAME eq php.exe" /V
) else (
    echo [X] Listener NO esta corriendo
    echo.
    echo Ejecuta: iniciar_listener_background.bat
)

echo.
echo ========================================
echo [2] Ultimo saldo registrado:
echo ========================================
if exist "admin\dist\logs\saldo_actual.json" (
    type admin\dist\logs\saldo_actual.json
) else (
    echo [INFO] No hay saldo registrado
)

echo.
echo ========================================
echo [3] Ultimas 10 lineas del log:
echo ========================================
if exist "admin\dist\logs\monedero_listener.log" (
    powershell "Get-Content 'admin\dist\logs\monedero_listener.log' -Tail 10"
) else (
    echo [INFO] No hay log disponible
)

echo.
echo ========================================
pause
