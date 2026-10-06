# ============================================
# 🔨 COMPILAR A .EXE - Monedero Monitor
# ============================================
# Este script compila MonederoMonitor.ps1 a un ejecutable .exe
# ============================================

Write-Host "========================================" -ForegroundColor Yellow
Write-Host "  COMPILADOR - Monedero Monitor" -ForegroundColor Yellow
Write-Host "========================================" -ForegroundColor Yellow
Write-Host ""

# Verificar si ps2exe está instalado
Write-Host "[1/4] Verificando ps2exe..." -ForegroundColor Cyan
$ps2exeInstalled = Get-Module -ListAvailable -Name ps2exe

if (!$ps2exeInstalled) {
    Write-Host "❌ ps2exe no está instalado" -ForegroundColor Red
    Write-Host ""
    Write-Host "Instalando ps2exe..." -ForegroundColor Yellow
    
    try {
        Install-Module ps2exe -Scope CurrentUser -Force -AllowClobber
        Write-Host "✅ ps2exe instalado correctamente" -ForegroundColor Green
    } catch {
        Write-Host "❌ Error al instalar ps2exe: $_" -ForegroundColor Red
        Write-Host ""
        Write-Host "Intenta manualmente:" -ForegroundColor Yellow
        Write-Host "  Install-Module ps2exe -Scope CurrentUser" -ForegroundColor White
        pause
        exit 1
    }
} else {
    Write-Host "✅ ps2exe ya está instalado" -ForegroundColor Green
}

# Importar módulo
Write-Host ""
Write-Host "[2/4] Cargando módulo ps2exe..." -ForegroundColor Cyan
Import-Module ps2exe
Write-Host "✅ Módulo cargado" -ForegroundColor Green

# Compilar
Write-Host ""
Write-Host "[3/4] Compilando MonederoMonitor.ps1..." -ForegroundColor Cyan
Write-Host "Esto puede tardar 30-60 segundos..." -ForegroundColor Yellow
Write-Host ""

$scriptPath = "$PSScriptRoot\MonederoMonitor.ps1"
$exePath = "$PSScriptRoot\MonederoMonitor.exe"

if (!(Test-Path $scriptPath)) {
    Write-Host "❌ No se encuentra MonederoMonitor.ps1" -ForegroundColor Red
    pause
    exit 1
}

try {
    Invoke-ps2exe `
        -inputFile $scriptPath `
        -outputFile $exePath `
        -noConsole `
        -title "Monedero Monitor - VendingBox" `
        -company "VendingBox" `
        -product "Monedero Monitor" `
        -version "1.0.0.0" `
        -copyright "© 2026 VendingBox" `
        -iconFile "$PSScriptRoot\monedero_icon.ico" `
        -requireAdmin $false `
        -noError `
        -noOutput
    
    Write-Host "✅ Compilación exitosa!" -ForegroundColor Green
    Write-Host ""
    Write-Host "Archivo generado:" -ForegroundColor Cyan
    Write-Host "  $exePath" -ForegroundColor White
    
} catch {
    Write-Host "❌ Error al compilar: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Compilando sin icono..." -ForegroundColor Yellow
    
    # Intentar sin icono
    try {
        Invoke-ps2exe `
            -inputFile $scriptPath `
            -outputFile $exePath `
            -noConsole `
            -title "Monedero Monitor - VendingBox" `
            -company "VendingBox" `
            -product "Monedero Monitor" `
            -version "1.0.0.0" `
            -copyright "© 2026 VendingBox"
        
        Write-Host "✅ Compilación exitosa (sin icono)!" -ForegroundColor Green
    } catch {
        Write-Host "❌ Error fatal: $_" -ForegroundColor Red
        pause
        exit 1
    }
}

# Verificar archivo
Write-Host ""
Write-Host "[4/4] Verificando archivo..." -ForegroundColor Cyan

if (Test-Path $exePath) {
    $fileInfo = Get-Item $exePath
    $sizeMB = [math]::Round($fileInfo.Length / 1MB, 2)
    
    Write-Host "✅ Archivo creado correctamente" -ForegroundColor Green
    Write-Host ""
    Write-Host "Detalles:" -ForegroundColor Cyan
    Write-Host "  Tamaño: $sizeMB MB" -ForegroundColor White
    Write-Host "  Ruta: $($fileInfo.FullName)" -ForegroundColor White
    Write-Host ""
    Write-Host "========================================" -ForegroundColor Yellow
    Write-Host "  ✅ COMPILACIÓN COMPLETADA" -ForegroundColor Green
    Write-Host "========================================" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "Puedes ejecutar el .exe con:" -ForegroundColor Cyan
    Write-Host "  MonederoMonitor.exe" -ForegroundColor White
    Write-Host ""
    
    # Preguntar si ejecutar
    $execute = Read-Host "¿Ejecutar ahora? (S/N)"
    if ($execute -eq "S" -or $execute -eq "s") {
        Start-Process $exePath
    }
    
} else {
    Write-Host "❌ No se pudo crear el archivo .exe" -ForegroundColor Red
}

Write-Host ""
pause
