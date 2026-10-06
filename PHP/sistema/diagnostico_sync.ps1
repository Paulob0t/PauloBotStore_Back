#Requires -Version 5.1
<#
.SYNOPSIS
    Diagnostico del sistema de sincronizacion bidireccional

.DESCRIPTION
    Muestra el estado actual de las sincronizaciones:
    - Registros pendientes de sincronizar (local -> nube)
    - Ultimo log de sincronizacion nube -> local
    - Ultimo log de sincronizacion local -> nube
    - Verificacion de conectividad
#>

param(
    [string]$DsnName = "MySQLDSN"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Continue"

# ============================================================
# FUNCIONES AUXILIARES
# ============================================================

function Show-Header {
    param([string]$Title)
    Write-Host "`n$('=' * 70)" -ForegroundColor Cyan
    Write-Host "  $Title" -ForegroundColor Yellow
    Write-Host "$('=' * 70)" -ForegroundColor Cyan
}

function Show-Success {
    param([string]$Message)
    Write-Host "[OK] $Message" -ForegroundColor Green
}

function Show-Warning {
    param([string]$Message)
    Write-Host "[!] $Message" -ForegroundColor Yellow
}

function Show-Error {
    param([string]$Message)
    Write-Host "[X] $Message" -ForegroundColor Red
}

# ============================================================
# DIAGNÓSTICO
# ============================================================

Show-Header "DIAGNÓSTICO DE SINCRONIZACIÓN - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"

# ----------------------------------------------------------
# 1. Verificar conexión ODBC
# ----------------------------------------------------------
Write-Host "`n[1] Verificando conexión ODBC..." -ForegroundColor Cyan

try {
    Add-Type -AssemblyName System.Data
    $conn = New-Object System.Data.Odbc.OdbcConnection("DSN=$DsnName")
    $conn.Open()
    Show-Success "Conexión ODBC exitosa (DSN: $DsnName)"
    
    # ----------------------------------------------------------
    # 2. Verificar registros pendientes de sincronización
    # ----------------------------------------------------------
    Write-Host "`n[2] Verificando registros pendientes (LOCAL → NUBE)..." -ForegroundColor Cyan
    
    $cmd = $conn.CreateCommand()
    $cmd.CommandText = "SELECT COUNT(*) FROM sincronizacion_log WHERE sincronizado = 0"
    $pendientes = $cmd.ExecuteScalar()
    
    if ($pendientes -eq 0) {
        Show-Success "Sin registros pendientes de sincronización"
    } else {
        Show-Warning "$pendientes registros pendientes de sincronización"
        
        # Mostrar detalle por tabla
        $cmd.CommandText = "SELECT tabla, COUNT(*) as total FROM sincronizacion_log WHERE sincronizado = 0 GROUP BY tabla ORDER BY total DESC"
        $reader = $cmd.ExecuteReader()
        
        Write-Host "  Detalle por tabla:" -ForegroundColor Gray
        while ($reader.Read()) {
            Write-Host "    - $($reader.GetString(0)): $($reader.GetInt32(1)) registros" -ForegroundColor White
        }
        $reader.Close()
    }
    
    # ----------------------------------------------------------
    # 3. Estadísticas de sincronización
    # ----------------------------------------------------------
    Write-Host "`n[3] Estadísticas de sincronización..." -ForegroundColor Cyan
    
    $cmd.CommandText = "SELECT COUNT(*) FROM sincronizacion_log WHERE sincronizado = 1"
    $sincronizados = $cmd.ExecuteScalar()
    Show-Success "$sincronizados registros sincronizados exitosamente"
    
    # Última sincronización
    $cmd.CommandText = "SELECT MAX(fecha_sincronizado) FROM sincronizacion_log WHERE sincronizado = 1"
    $ultimaSync = $cmd.ExecuteScalar()
    if ($ultimaSync) {
        Write-Host "  Última sincronización: $ultimaSync" -ForegroundColor Gray
    }
    
    $conn.Close()
    
} catch {
    Show-Error "Error de conexión ODBC: $_"
    exit 1
}

# ----------------------------------------------------------
# 4. Verificar logs recientes
# ----------------------------------------------------------
Write-Host "`n[4] Verificando archivos de log..." -ForegroundColor Cyan

$logDir = Join-Path $PSScriptRoot "logs"
if (Test-Path $logDir) {
    $logsHoy = Get-ChildItem $logDir -Filter "sync_*$(Get-Date -Format 'yyyyMMdd').log" -ErrorAction SilentlyContinue
    
    if ($logsHoy) {
        Show-Success "Logs de hoy encontrados:"
        foreach ($log in $logsHoy) {
            Write-Host "  - $($log.Name) ($([math]::Round($log.Length/1KB, 2)) KB)" -ForegroundColor White
            
            # Mostrar últimas 3 líneas del log
            $ultimas = Get-Content $log.FullName -Tail 3 -ErrorAction SilentlyContinue
            if ($ultimas) {
                foreach ($linea in $ultimas) {
                    Write-Host "    $linea" -ForegroundColor DarkGray
                }
            }
        }
    } else {
        Show-Warning "No hay logs de hoy"
    }
} else {
    Show-Warning "Directorio de logs no existe: $logDir"
}

# ----------------------------------------------------------
# 5. Verificar conectividad con la nube
# ----------------------------------------------------------
Write-Host "`n[5] Verificando conectividad con la nube..." -ForegroundColor Cyan

try {
    $endpointTest = "https://vending.colegos.com.mx/sincronizar_local.php"
    $response = Invoke-WebRequest -Uri $endpointTest -Method Head -TimeoutSec 5 -UseBasicParsing -ErrorAction Stop
    
    if ($response.StatusCode -eq 200) {
        Show-Success "Conectividad con la nube OK (Status: $($response.StatusCode))"
    } else {
        Show-Warning "Respuesta inesperada: Status $($response.StatusCode)"
    }
} catch {
    Show-Error "Error de conectividad: $_"
}

# ----------------------------------------------------------
# 6. Verificar tareas programadas
# ----------------------------------------------------------
Write-Host "`n[6] Verificando tareas programadas de Windows..." -ForegroundColor Cyan

$tareasSync = Get-ScheduledTask | Where-Object { $_.TaskName -like "*sync*" -or $_.TaskName -like "*sincroniz*" } -ErrorAction SilentlyContinue

if ($tareasSync) {
    Show-Success "Tareas programadas encontradas:"
    foreach ($tarea in $tareasSync) {
        $estado = $tarea.State
        $color = switch ($estado) {
            "Ready"    { "Green" }
            "Running"  { "Cyan" }
            "Disabled" { "Red" }
            default    { "Yellow" }
        }
        Write-Host "  - $($tarea.TaskName): $estado" -ForegroundColor $color
        
        # Mostrar última ejecución
        $info = Get-ScheduledTaskInfo $tarea.TaskName -ErrorAction SilentlyContinue
        if ($info -and $info.LastRunTime) {
            Write-Host "    Última ejecución: $($info.LastRunTime)" -ForegroundColor Gray
            Write-Host "    Resultado: $($info.LastTaskResult)" -ForegroundColor Gray
        }
    }
} else {
    Show-Warning "No se encontraron tareas programadas de sincronización"
}

# ----------------------------------------------------------
# RESUMEN FINAL
# ----------------------------------------------------------
Show-Header "RESUMEN"

Write-Host ""
Write-Host "Para ejecutar manualmente las sincronizaciones:" -ForegroundColor White
Write-Host "  • Local → Nube:  .\sincronizar_nube.ps1" -ForegroundColor Cyan
Write-Host "  • Nube → Local:  .\sincronizar_local.ps1" -ForegroundColor Cyan
Write-Host ""
Write-Host "Logs almacenados en: $logDir" -ForegroundColor Gray
Write-Host ""
