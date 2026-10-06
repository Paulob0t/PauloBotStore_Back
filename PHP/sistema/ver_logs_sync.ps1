#Requires -Version 5.1
<#
.SYNOPSIS
    Visualizador de logs de sincronizacion

.DESCRIPTION
    Muestra los logs de sincronizacion de forma ordenada y con colores

.PARAMETER Tipo
    Tipo de log a mostrar: nube, local, o todos

.PARAMETER Lineas
    Numero de lineas a mostrar (default: 20)
#>

param(
    [ValidateSet("nube", "local", "todos")]
    [string]$Tipo = "todos",
    [int]$Lineas = 20
)

$LogDir = Join-Path $PSScriptRoot "logs"
$Fecha = Get-Date -Format "yyyyMMdd"

function Show-Log {
    param(
        [string]$Archivo,
        [string]$Titulo
    )
    
    if (Test-Path $Archivo) {
        Write-Host "`n$('=' * 70)" -ForegroundColor Cyan
        Write-Host "  $Titulo" -ForegroundColor Yellow
        Write-Host "$('=' * 70)" -ForegroundColor Cyan
        
        $contenido = Get-Content $Archivo -Tail $Lineas -ErrorAction SilentlyContinue
        
        foreach ($linea in $contenido) {
            $color = "White"
            
            if ($linea -match "\[OK\]") {
                $color = "Green"
            } elseif ($linea -match "\[ERROR\]") {
                $color = "Red"
            } elseif ($linea -match "\[WARN\]") {
                $color = "Yellow"
            } elseif ($linea -match "\[INFO\]") {
                $color = "Cyan"
            }
            
            Write-Host $linea -ForegroundColor $color
        }
        
        $total = (Get-Content $Archivo | Measure-Object -Line).Lines
        Write-Host "`nTotal de lineas en el archivo: $total" -ForegroundColor Gray
    } else {
        Write-Host "`n[!] Archivo no encontrado: $Archivo" -ForegroundColor Yellow
    }
}

# Mostrar logs
if ($Tipo -eq "nube" -or $Tipo -eq "todos") {
    $logNube = Join-Path $LogDir "sync_nube_$Fecha.log"
    Show-Log -Archivo $logNube -Titulo "SINCRONIZACION LOCAL -> NUBE (Ultimas $Lineas lineas)"
}

if ($Tipo -eq "local" -or $Tipo -eq "todos") {
    $logLocal = Join-Path $LogDir "sync_local_$Fecha.log"
    Show-Log -Archivo $logLocal -Titulo "SINCRONIZACION NUBE -> LOCAL (Ultimas $Lineas lineas)"
}

Write-Host "`nUso:" -ForegroundColor White
Write-Host "  .\ver_logs_sync.ps1 -Tipo nube    # Ver solo logs de local->nube" -ForegroundColor Gray
Write-Host "  .\ver_logs_sync.ps1 -Tipo local   # Ver solo logs de nube->local" -ForegroundColor Gray
Write-Host "  .\ver_logs_sync.ps1 -Lineas 50    # Ver ultimas 50 lineas" -ForegroundColor Gray
Write-Host ""
