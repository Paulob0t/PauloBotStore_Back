#Requires -Version 5.1
<#
.SYNOPSIS
    Prueba rapida del sistema de sincronizacion

.DESCRIPTION
    Ejecuta ambas sincronizaciones y muestra el resultado
#>

Write-Host "`n$('=' * 70)" -ForegroundColor Cyan
Write-Host "  PRUEBA RAPIDA DEL SISTEMA DE SINCRONIZACION" -ForegroundColor Yellow
Write-Host "$('=' * 70)" -ForegroundColor Cyan

# Cambiar al directorio del script
Set-Location $PSScriptRoot

# Prueba 1: Sincronizacion local -> nube
Write-Host "`n[1/2] Probando sincronizacion LOCAL -> NUBE..." -ForegroundColor Yellow
Write-Host "$('-' * 70)" -ForegroundColor DarkGray

try {
    & ".\sincronizar_nube.ps1"
    Write-Host "`n[OK] Sincronizacion local -> nube completada" -ForegroundColor Green
} catch {
    Write-Host "`n[X] Error en sincronizacion local -> nube: $_" -ForegroundColor Red
}

# Pausa
Start-Sleep -Seconds 2

# Prueba 2: Sincronizacion nube -> local
Write-Host "`n[2/2] Probando sincronizacion NUBE -> LOCAL..." -ForegroundColor Yellow
Write-Host "$('-' * 70)" -ForegroundColor DarkGray

try {
    & ".\sincronizar_local.ps1"
    Write-Host "`n[OK] Sincronizacion nube -> local completada" -ForegroundColor Green
} catch {
    Write-Host "`n[X] Error en sincronizacion nube -> local: $_" -ForegroundColor Red
}

# Resumen
Write-Host "`n$('=' * 70)" -ForegroundColor Cyan
Write-Host "  RESUMEN" -ForegroundColor Yellow
Write-Host "$('=' * 70)" -ForegroundColor Cyan

Write-Host "`nLogs generados en: $PSScriptRoot\logs" -ForegroundColor White
Write-Host "`nPara ver los logs ejecuta:" -ForegroundColor White
Write-Host "  .\ver_logs_sync.ps1" -ForegroundColor Cyan
Write-Host "`nPara diagnostico completo ejecuta:" -ForegroundColor White
Write-Host "  .\diagnostico_sync.ps1" -ForegroundColor Cyan
Write-Host ""
