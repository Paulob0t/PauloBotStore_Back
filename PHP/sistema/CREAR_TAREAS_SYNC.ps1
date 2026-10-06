#Requires -RunAsAdministrator
# CREAR_TAREAS_SYNC.ps1
# Crea las tareas programadas de sincronizacion cada 1 minuto

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  CONFIGURACION DE SINCRONIZACION RAPIDA" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

$scriptDir = "C:\xampp\htdocs\vendingbox.online\sistema"

# Verificar scripts
$scriptNube = Join-Path $scriptDir "sincronizar_nube.ps1"
$scriptLocal = Join-Path $scriptDir "sincronizar_local.ps1"

if (-not (Test-Path $scriptNube)) {
    Write-Host "ERROR: No se encuentra $scriptNube" -ForegroundColor Red
    exit 1
}
if (-not (Test-Path $scriptLocal)) {
    Write-Host "ERROR: No se encuentra $scriptLocal" -ForegroundColor Red
    exit 1
}

Write-Host "Scripts encontrados OK" -ForegroundColor Green
Write-Host ""

# TAREA 1: Sincronizar Local -> Nube
Write-Host "Creando tarea: VendingBox_Sync_Nube..." -ForegroundColor Yellow

$actionNube = New-ScheduledTaskAction `
    -Execute "PowerShell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$scriptNube`""

$triggerNube = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$principalNube = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

$settingsNube = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

try {
    Register-ScheduledTask `
        -TaskName "VendingBox_Sync_Nube" `
        -Action $actionNube `
        -Trigger $triggerNube `
        -Principal $principalNube `
        -Settings $settingsNube `
        -Description "Sincroniza cambios locales hacia la nube cada 1 minuto" `
        -Force | Out-Null
    
    Write-Host "OK Tarea VendingBox_Sync_Nube creada" -ForegroundColor Green
} catch {
    Write-Host "ERROR al crear tarea Nube: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""

# TAREA 2: Sincronizar Nube -> Local
Write-Host "Creando tarea: VendingBox_Sync_Local..." -ForegroundColor Yellow

$actionLocal = New-ScheduledTaskAction `
    -Execute "PowerShell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -File `"$scriptLocal`""

$triggerLocal = New-ScheduledTaskTrigger `
    -Once `
    -At (Get-Date).AddMinutes(1) `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration ([TimeSpan]::MaxValue)

$principalLocal = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

$settingsLocal = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

try {
    Register-ScheduledTask `
        -TaskName "VendingBox_Sync_Local" `
        -Action $actionLocal `
        -Trigger $triggerLocal `
        -Principal $principalLocal `
        -Settings $settingsLocal `
        -Description "Sincroniza cambios de la nube hacia local cada 1 minuto" `
        -Force | Out-Null
    
    Write-Host "OK Tarea VendingBox_Sync_Local creada" -ForegroundColor Green
} catch {
    Write-Host "ERROR al crear tarea Local: $_" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  INSTALACION COMPLETADA" -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Verificar y mostrar tareas
Write-Host "Tareas creadas:" -ForegroundColor Yellow
Get-ScheduledTask -TaskName "VendingBox_*" | Format-Table TaskName, State, LastRunTime, NextRunTime -AutoSize

Write-Host ""
Write-Host "La sincronizacion se ejecutara automaticamente cada 1 minuto." -ForegroundColor Green
Write-Host "Los logs se guardan en: $scriptDir\logs\" -ForegroundColor Cyan
Write-Host ""
