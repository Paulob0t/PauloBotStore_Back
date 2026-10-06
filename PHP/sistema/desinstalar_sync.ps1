#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Detiene y elimina las tareas de sincronización

.DESCRIPTION
    Elimina las tareas programadas de sincronización de Windows

.EXAMPLE
    .\desinstalar_sync.ps1
#>

$ErrorActionPreference = "Stop"

Write-Host "============================================" -ForegroundColor Red
Write-Host "  DESINSTALAR SINCRONIZACIÓN AUTOMÁTICA   " -ForegroundColor Red
Write-Host "============================================" -ForegroundColor Red
Write-Host ""

# Verificar que se ejecuta como administrador
$currentPrincipal = New-Object Security.Principal.WindowsPrincipal([Security.Principal.WindowsIdentity]::GetCurrent())
$isAdmin = $currentPrincipal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)

if (-not $isAdmin) {
    Write-Host "❌ ERROR: Este script debe ejecutarse como ADMINISTRADOR" -ForegroundColor Red
    Write-Host ""
    Read-Host "Presiona Enter para salir"
    exit 1
}

# Buscar tareas de sincronización
$tareas = Get-ScheduledTask -TaskName "VendingBox_*" -ErrorAction SilentlyContinue

if ($tareas) {
    Write-Host "📋 Tareas encontradas:" -ForegroundColor Yellow
    Write-Host ""
    
    foreach ($tarea in $tareas) {
        Write-Host "  • $($tarea.TaskName) - Estado: $($tarea.State)" -ForegroundColor White
    }
    
    Write-Host ""
    $confirmacion = Read-Host "¿Deseas eliminar estas tareas? (S/N)"
    
    if ($confirmacion -eq 'S' -or $confirmacion -eq 's') {
        foreach ($tarea in $tareas) {
            Write-Host "  🗑️  Eliminando: $($tarea.TaskName)..." -ForegroundColor Yellow
            Unregister-ScheduledTask -TaskName $tarea.TaskName -Confirm:$false
            Write-Host "     ✅ Eliminada" -ForegroundColor Green
        }
        
        Write-Host ""
        Write-Host "✅ Todas las tareas han sido eliminadas" -ForegroundColor Green
    } else {
        Write-Host "❌ Operación cancelada" -ForegroundColor Yellow
    }
} else {
    Write-Host "ℹ️  No se encontraron tareas de sincronización" -ForegroundColor Cyan
}

Write-Host ""
Read-Host "Presiona Enter para salir"
