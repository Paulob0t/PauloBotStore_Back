@echo off
cls
echo ========================================
echo  DETENER GESTOR COM5 DEL MONEDERO
echo ========================================
echo.

echo Buscando procesos PHP de com5_manager...
echo.

wmic process where "name='php.exe' and commandline like '%%com5_manager%%'" get processid,commandline 2>nul | findstr /I "com5_manager" >nul
if %ERRORLEVEL% EQU 0 (
    echo Deteniendo com5_manager...
    wmic process where "name='php.exe' and commandline like '%%com5_manager%%'" delete >nul 2>&1
    if %ERRORLEVEL% EQU 0 (
        echo [OK] Gestor COM5 detenido exitosamente
    ) else (
        echo [INFO] No se pudo detener com5_manager
    )
) else (
    echo [INFO] com5_manager no esta corriendo
)

echo.
echo ========================================
pause
