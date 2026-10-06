@echo off
cls
echo ========================================
echo  VERIFICADOR DE ESTADO
echo ========================================
echo.

echo [1/5] Verificando puerto COM5...
mode COM5: 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Puerto COM5 existe
) else (
    echo [ERROR] Puerto COM5 no encontrado
    echo.
    echo Puertos disponibles:
    powershell "Get-WMIObject Win32_SerialPort | Select-Object DeviceID, Name"
    goto :end
)

echo.
echo [2/5] Verificando PHP...
where php >nul 2>nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] PHP encontrado
    php -v | findstr PHP
) else (
    echo [ERROR] PHP no encontrado
    echo Intentando con XAMPP...
    if exist "C:\xampp\php\php.exe" (
        echo [OK] PHP de XAMPP encontrado
    ) else (
        echo [ERROR] No se encuentra PHP
    )
)

echo.
echo [3/5] Verificando archivos...
if exist "com5_manager.php" (
    echo [OK] com5_manager.php existe
) else (
    echo [ERROR] com5_manager.php NO existe
)

if exist "com5_ps_manager.ps1" (
    echo [OK] com5_ps_manager.ps1 existe
) else (
    echo [WARN] com5_ps_manager.ps1 NO existe
)

if exist "com5_send_command.php" (
    echo [OK] com5_send_command.php existe
) else (
    echo [WARN] com5_send_command.php NO existe
)

if exist "monedero_api.php" (
    echo [OK] monedero_api.php existe
) else (
    echo [ERROR] monedero_api.php NO existe
)

if exist "MonederoMonitor.bat" (
    echo [OK] MonederoMonitor.bat existe
) else (
    echo [WARN] MonederoMonitor.bat NO existe
)

if exist "MonederoMonitor.ps1" (
    echo [OK] MonederoMonitor.ps1 existe
) else (
    echo [WARN] MonederoMonitor.ps1 NO existe
)

if exist "cart.php" (
    echo [OK] cart.php existe
) else (
    echo [ERROR] cart.php NO existe
)

echo.
echo [4/5] Verificando logs...
if exist "admin\dist\logs\saldo_actual.json" (
    echo [OK] Archivo de saldo existe
    type admin\dist\logs\saldo_actual.json
) else (
    echo [INFO] No hay saldo registrado aun
)

if exist "admin\dist\logs\coin_inventory.log" (
    echo [OK] Inventario de cambio existe
) else (
    echo [WARN] coin_inventory.log no existe - se crea al insertar monedas
)

echo.
echo [5/5] Probando API...
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost/vendingbox/monedero_api.php?action=get_saldo' -UseBasicParsing; Write-Host '[OK] API saldo:'; Write-Host $response.Content } catch { Write-Host '[ERROR] API no responde:' $_; }"
powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost/vendingbox/monedero_api.php?action=get_coin_inventory' -UseBasicParsing; Write-Host '[OK] API inventario:'; Write-Host $response.Content } catch { Write-Host '[WARN] Inventario API:' $_; }"

:end
echo.
echo ========================================
echo  Diagnostico completado
echo ========================================
echo.
pause
