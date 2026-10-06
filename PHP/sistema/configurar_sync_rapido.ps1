#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Configura las tareas programadas de sincronización para ejecutarse cada 1 minuto

.DESCRIPTION
    Crea o actualiza las tareas programadas de Windows para:
    - Sincronizacion Local a Nube (cada 1 minuto)
    - Sincronizacion Nube a Local (cada 1 minuto)
    
    ADVERTENCIA: DEBE EJECUTARSE COMO ADMINISTRADOR

.EXAMPLE
    .\configurar_sync_rapido.ps1
#>

$ErrorActionPreference = "Stop"

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  CONFIGURACION DE SINCRONIZACION RAPIDA  " -ForegroundColor Cyan
Write-Host "  Intervalo: Cada 1 minuto                " -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

# Verificar que se ejecuta como administrador
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
$isAdmin = $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "ERROR: Este script debe ejecutarse como ADMINISTRADOR" -ForegroundColor Red
    Write-Host ""
    Write-Host "Haz clic derecho en PowerShell y selecciona Ejecutar como administrador" -ForegroundColor Yellow
    Write-Host ""
    Read-Host "Presiona Enter para salir"
    exit 1
}

# Rutas
$scriptPath = $PSScriptRoot
$scriptNube = Join-Path $scriptPath "sincronizar_nube.ps1"
$scriptLocal = Join-Path $scriptPath "sincronizar_local.ps1"

# Verificar que existen los scripts
if (-not (Test-Path $scriptNube)) {
    Write-Host "No se encuentra: $scriptNube" -ForegroundColor Red
    exit 1
}

if (-not (Test-Path $scriptLocal)) {
    Write-Host "No se encuentra: $scriptLocal" -ForegroundColor Red
    exit 1
}

Write-Host "Scripts encontrados" -ForegroundColor Green
Write-Host "   Nube: $scriptNube" -ForegroundColor Gray
Write-Host "   Local: $scriptLocal" -ForegroundColor Gray
Write-Host ""

# ============================================================
# TAREA 1: SINCRONIZACION LOCAL A NUBE (cada 1 minuto)
# ============================================================
Write-Host "Configurando sincronizacion LOCAL a NUBE..." -ForegroundColor Cyan

$taskNameNube = "VendingBox_Sync_Nube"

# Eliminar tarea existente si existe
$existingTask = Get-ScheduledTask -TaskName $taskNameNube -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "   Eliminando tarea anterior..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskNameNube -Confirm:$false
}

# Crear acción (ejecutar PowerShell con el script)
$actionNube = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$scriptNube`"" `
    -WorkingDirectory $scriptPath

# Crear trigger (cada 1 minuto, indefinidamente)
$triggerNube = New-ScheduledTaskTrigger -Once -At (Get-Date) -RepetitionInterval (New-TimeSpan -Minutes 1)

# Configuracion principal
$principalNube = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

# Configuraciones adicionales
$settingsNube = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

# Registrar tarea
Register-ScheduledTask `
    -TaskName $taskNameNube `
    -Action $actionNube `
    -Trigger $triggerNube `
    -Principal $principalNube `
    -Settings $settingsNube `
    -Description "Sincroniza cambios locales hacia la nube cada 1 minuto" | Out-Null

Write-Host "   Tarea creada: $taskNameNube" -ForegroundColor Green
Write-Host "   Intervalo: Cada 1 minuto" -ForegroundColor Green
Write-Host ""

# ============================================================
# TAREA 2: SINCRONIZACION NUBE A LOCAL (cada 1 minuto)
# ============================================================
Write-Host "Configurando sincronizacion NUBE a LOCAL..." -ForegroundColor Cyan

$taskNameLocal = "VendingBox_Sync_Local"

# Eliminar tarea existente si existe
$existingTask = Get-ScheduledTask -TaskName $taskNameLocal -ErrorAction SilentlyContinue
if ($existingTask) {
    Write-Host "   Eliminando tarea anterior..." -ForegroundColor Yellow
    Unregister-ScheduledTask -TaskName $taskNameLocal -Confirm:$false
}

# Crear acción
$actionLocal = New-ScheduledTaskAction `
    -Execute "powershell.exe" `
    -Argument "-NoProfile -ExecutionPolicy Bypass -WindowStyle Hidden -File `"$scriptLocal`"" `
    -WorkingDirectory $scriptPath

# Crear trigger (cada 1 minuto, indefinidamente)
$triggerLocal = New-ScheduledTaskTrigger -Once -At (Get-Date).AddSeconds(30) -RepetitionInterval (New-TimeSpan -Minutes 1)

# Configuracion principal
$principalLocal = New-ScheduledTaskPrincipal `
    -UserId "SYSTEM" `
    -LogonType ServiceAccount `
    -RunLevel Highest

# Configuraciones adicionales
$settingsLocal = New-ScheduledTaskSettingsSet `
    -AllowStartIfOnBatteries `
    -DontStopIfGoingOnBatteries `
    -StartWhenAvailable `
    -RunOnlyIfNetworkAvailable `
    -MultipleInstances IgnoreNew `
    -ExecutionTimeLimit (New-TimeSpan -Minutes 5)

# Registrar tarea
Register-ScheduledTask `
    -TaskName $taskNameLocal `
    -Action $actionLocal `
    -Trigger $triggerLocal `
    -Principal $principalLocal `
    -Settings $settingsLocal `
    -Description "Descarga y sincroniza datos desde la nube cada 1 minuto" | Out-Null

Write-Host "   Tarea creada: $taskNameLocal" -ForegroundColor Green
Write-Host "   Intervalo: Cada 1 minuto" -ForegroundColor Green
Write-Host ""

# ============================================================
# VERIFICACION
# ============================================================
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "  CONFIGURACION COMPLETADA              " -ForegroundColor Green
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

Write-Host "Tareas programadas creadas:" -ForegroundColor White
Write-Host ""

$tareas = Get-ScheduledTask -TaskName "VendingBox_*" -ErrorAction SilentlyContinue

foreach ($tarea in $tareas) {
    $info = Get-ScheduledTaskInfo $tarea.TaskName -ErrorAction SilentlyContinue
    $estado = $tarea.State
    $color = if ($estado -eq "Ready") { "Green" } else { "Yellow" }
    
    Write-Host "  * $($tarea.TaskName)" -ForegroundColor Yellow
    Write-Host "     Estado: $estado" -ForegroundColor $color
    Write-Host "     Ultima ejecucion: $($info.LastRunTime)" -ForegroundColor Gray
    Write-Host "     Proxima ejecucion: $($info.NextRunTime)" -ForegroundColor Gray
    Write-Host ""
}

Write-Host "COMANDOS UTILES:" -ForegroundColor Cyan
Write-Host ""
Write-Host "  Ver estado de las tareas:" -ForegroundColor White
Write-Host "  Get-ScheduledTask -TaskName VendingBox_*" -ForegroundColor Gray
Write-Host ""
Write-Host "  Iniciar sincronizacion manualmente:" -ForegroundColor White
Write-Host "  Start-ScheduledTask -TaskName VendingBox_Sync_Nube" -ForegroundColor Gray
Write-Host "  Start-ScheduledTask -TaskName VendingBox_Sync_Local" -ForegroundColor Gray
Write-Host ""
Write-Host "  Deshabilitar sincronizacion:" -ForegroundColor White
Write-Host "  Disable-ScheduledTask -TaskName VendingBox_Sync_Nube" -ForegroundColor Gray
Write-Host "  Disable-ScheduledTask -TaskName VendingBox_Sync_Local" -ForegroundColor Gray
Write-Host ""
Write-Host "  Habilitar sincronizacion:" -ForegroundColor White
Write-Host "  Enable-ScheduledTask -TaskName VendingBox_Sync_Nube" -ForegroundColor Gray
Write-Host "  Enable-ScheduledTask -TaskName VendingBox_Sync_Local" -ForegroundColor Gray
Write-Host ""
Write-Host "  Eliminar tareas completas:" -ForegroundColor White
Write-Host "  Ejecuta: desinstalar_sync.ps1" -ForegroundColor Gray
Write-Host ""

Write-Host "Las sincronizaciones comenzaran automaticamente cada 1 minuto" -ForegroundColor Green
Write-Host "Puedes monitorear los logs en: $scriptPath\logs\" -ForegroundColor Yellow
Write-Host ""

Read-Host "Presiona Enter para salir"
