@echo off
cls
echo ========================================
echo  CONFIGURAR INICIO AUTOMATICO
echo ========================================
echo.
echo Este script configurara el listener
echo para que inicie automaticamente cuando
echo arranque Windows
echo.
echo ========================================
echo.

choice /C SN /M "Quieres activar el inicio automatico"
if errorlevel 2 goto :remove
if errorlevel 1 goto :add

:add
echo.
echo Agregando al inicio de Windows...
echo.

REM Obtener la ruta actual
set SCRIPT_PATH=%~dp0iniciar_listener_invisible.vbs

REM Crear acceso directo en la carpeta de inicio
set STARTUP_FOLDER=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup

REM Usar PowerShell para crear el acceso directo
powershell -Command "$WshShell = New-Object -ComObject WScript.Shell; $Shortcut = $WshShell.CreateShortcut('%STARTUP_FOLDER%\Monedero Listener.lnk'); $Shortcut.TargetPath = '%SCRIPT_PATH%'; $Shortcut.WorkingDirectory = '%~dp0'; $Shortcut.Description = 'Listener del Monedero'; $Shortcut.Save()"

if %ERRORLEVEL% EQU 0 (
    echo [OK] Listener configurado para inicio automatico
    echo.
    echo El listener se iniciara automaticamente
    echo cada vez que inicies Windows
    echo.
    echo Ubicacion: %STARTUP_FOLDER%
) else (
    echo [ERROR] No se pudo configurar el inicio automatico
)

goto :end

:remove
echo.
echo Removiendo del inicio de Windows...
echo.

set STARTUP_FOLDER=%APPDATA%\Microsoft\Windows\Start Menu\Programs\Startup
del "%STARTUP_FOLDER%\Monedero Listener.lnk" 2>nul

if %ERRORLEVEL% EQU 0 (
    echo [OK] Listener removido del inicio automatico
) else (
    echo [INFO] No habia inicio automatico configurado
)

:end
echo.
echo ========================================
pause
