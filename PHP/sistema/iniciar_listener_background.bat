@echo off
cls
echo ========================================
echo  COM5 MANAGER - MODO BACKGROUND
echo ========================================
echo.
echo Este script inicia el listener en
echo SEGUNDO PLANO (invisible, sin ventana)
echo.
echo El listener seguira corriendo aunque
echo cierres esta ventana
echo.
echo ========================================
echo.

cd /d "%~dp0"

echo [1/3] Verificando si el listener ya esta corriendo...
wmic process where "name='php.exe' and commandline like '%%com5_manager%%'" get processid 2>nul | findstr /R "[0-9]" >nul
if %ERRORLEVEL% EQU 0 (
    echo [OK] Ya hay un listener corriendo
    echo.
    choice /C SN /M "Quieres detenerlo y reiniciar"
    if errorlevel 2 goto :end
    if errorlevel 1 goto :kill
) else (
    echo [INFO] No hay listener corriendo
)

:kill
echo.
echo [2/3] Deteniendo listeners anteriores...
wmic process where "name='php.exe' and commandline like '%%com5_manager%%'" delete >nul 2>&1
timeout /t 2 /nobreak >nul

:start
echo.
echo [3/3] Iniciando listener en background...

REM Usar VBScript para lanzar invisible
if exist "iniciar_listener_invisible.vbs" (
    cscript //nologo iniciar_listener_invisible.vbs
    echo [OK] Listener iniciado en background
) else (
    echo [ERROR] No se encuentra el archivo VBS
    echo Intentando metodo alternativo...
    
    REM Metodo alternativo con PowerShell
    powershell -WindowStyle Hidden -Command "Start-Process -WindowStyle Hidden -FilePath 'C:\xampp\php\php.exe' -ArgumentList 'com5_manager.php' -WorkingDirectory '%~dp0'"
    echo [OK] Listener iniciado con PowerShell
)

echo.
echo ========================================
echo  Listener corriendo en background
echo ========================================
echo.
echo El listener esta corriendo de forma
echo invisible en segundo plano.
echo.
echo Para verificar que funciona:
echo - Abre cart.php en el navegador
echo - Inserta una moneda
echo - Deberia actualizar el saldo
echo.
echo Para detenerlo, ejecuta:
echo   detener_listener.bat
echo.
echo ========================================

:end
pause
