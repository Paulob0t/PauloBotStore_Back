#Requires -Version 5.1
<#
.SYNOPSIS
    Sincroniza los registros pendientes de sincronizacion_log hacia la BD en la nube,
    aplicando la acción real (INSERT / UPDATE / DELETE) en la tabla correspondiente.

.DESCRIPTION
    1. Lee desde la BD local (DSN ODBC) los registros de sincronizacion_log donde
       sincronizado = 0.
    2. Envía el lote al endpoint PHP en la nube (sincronizar_endpoint.php) vía HTTP POST.
    3. El endpoint PHP aplica INSERT / UPDATE / DELETE en la tabla indicada con los
       datos recibidos en el campo JSON 'datos', usando id_registro como PK.
    4. Marca como sincronizados (sincronizado=1) solo los registros procesados con éxito.

.PARAMETER EndpointUrl
    URL del endpoint PHP en la nube. Default: https://vendingbox.online/sincronizar_endpoint.php

.PARAMETER DsnName
    Nombre del DSN ODBC configurado en Windows que apunta a la BD local. Default: MySQLDSN

.PARAMETER BatchSize
    Número máximo de registros a procesar por ejecución. Default: 100.

.EXAMPLE
    .\sincronizar_nube.ps1
    .\sincronizar_nube.ps1 -BatchSize 50
#>

param(
    [string]$EndpointUrl = "https://vendingbox.online/sincronizar_endpoint.php",
    [string]$DsnName     = "MySQLDSN",
    [int]$BatchSize      = 100
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# ============================================================
# LOGGING
# ============================================================

$LogDir  = Join-Path $PSScriptRoot "logs"
$LogFile = Join-Path $LogDir "sync_nube_$(Get-Date -Format 'yyyyMMdd').log"

if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir | Out-Null
}

function Write-Log {
    param(
        [string]$Message,
        [ValidateSet("INFO","WARN","ERROR","OK")][string]$Level = "INFO"
    )
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$timestamp][$Level] $Message"
    Write-Host $line -ForegroundColor $(switch ($Level) {
        "OK"    { "Green"  }
        "WARN"  { "Yellow" }
        "ERROR" { "Red"    }
        default { "White"  }
    })

    # Evita abortar la sincronizacion si el log esta temporalmente bloqueado.
    $written = $false
    for ($i = 1; $i -le 3; $i++) {
        try {
            Add-Content -Path $LogFile -Value $line -Encoding UTF8 -ErrorAction Stop
            $written = $true
            break
        } catch {
            if ($i -lt 3) {
                [System.Threading.Thread]::Sleep(150)
            }
        }
    }

    if (-not $written) {
        Write-Host "[WARN] No se pudo escribir en $LogFile (archivo en uso)." -ForegroundColor Yellow
    }
}

# ============================================================
# PROCESO PRINCIPAL
# ============================================================

$odbcConn = $null

try {
    Write-Log "=== INICIO SINCRONIZACION NUBE ==="
    Write-Log "DSN: $DsnName | Batch: $BatchSize | Endpoint: $EndpointUrl"

    # ----------------------------------------------------------
    # 1. Conectar a BD local via ODBC DSN
    # ----------------------------------------------------------
    Add-Type -AssemblyName System.Data

    $odbcConn = New-Object System.Data.Odbc.OdbcConnection("DSN=$DsnName")
    $odbcConn.Open()
    Write-Log "Conexión ODBC abierta (DSN: $DsnName)"

    # ----------------------------------------------------------
    # 2. Leer registros pendientes (sincronizado = 0)
    # ----------------------------------------------------------
    Write-Log "Consultando registros pendientes..."

    $cmdSelect = $odbcConn.CreateCommand()
    $cmdSelect.CommandText = "SELECT id_sync, tabla, accion, id_registro, datos " +
                             "FROM sincronizacion_log " +
                             "WHERE sincronizado = 0 " +
                             "ORDER BY id_sync ASC " +
                             "LIMIT ?;"
    $p = $cmdSelect.Parameters.Add("", [System.Data.Odbc.OdbcType]::Int)
    $p.Value = $BatchSize

    $reader  = $cmdSelect.ExecuteReader()
    $records = [System.Collections.Generic.List[PSCustomObject]]::new()

    while ($reader.Read()) {
        # datos puede ser NULL (ej: para acción DELETE)
        $datosStr = if ($reader.IsDBNull(4)) { '{}' } else { $reader.GetString(4) }

        # Parsear el JSON de datos; si no es válido, usar objeto vacío
        $datosObj = try { $datosStr | ConvertFrom-Json } catch { [PSCustomObject]@{} }

        $records.Add([PSCustomObject]@{
            id_sync     = $reader.GetInt32(0)
            tabla       = $reader.GetString(1)
            accion      = $reader.GetString(2)
            id_registro = $reader.GetInt32(3)
            datos       = $datosObj
        })
    }
    $reader.Close()
    $cmdSelect.Dispose()

    if ($records.Count -eq 0) {
        Write-Log "Sin registros pendientes de sincronizacion." "OK"
        Write-Log "=== FIN (sin cambios) ==="
        $odbcConn.Close()
        exit 0
    }

    Write-Log "Registros pendientes: $($records.Count)"

    # ----------------------------------------------------------
    # 3. Enviar al endpoint PHP en la nube
    #    Payload: { registros: [ {id_sync, tabla, accion, id_registro, datos}, ... ] }
    # ----------------------------------------------------------
    $payload = @{ registros = @($records) } | ConvertTo-Json -Depth 10 -Compress
    $payloadBytes = [System.Text.Encoding]::UTF8.GetBytes($payload)

    Write-Log "Enviando $($records.Count) registro(s) al endpoint..."

    try {
        $response = Invoke-RestMethod `
            -Uri         $EndpointUrl `
            -Method      POST `
            -Body        $payloadBytes `
            -ContentType "application/json; charset=utf-8" `
            -ErrorAction Stop
    } catch {
        throw "Error HTTP al contactar el endpoint: $_"
    }

    # ----------------------------------------------------------
    # 4. Procesar respuesta del endpoint
    # ----------------------------------------------------------
    $exitosos = @($response.exitosos)   # array de id_sync procesados con éxito
    $fallidos = @($response.fallidos)   # array de {id_sync, error}

    Write-Log "Respuesta recibida → Exitosos: $($exitosos.Count) | Fallidos: $($fallidos.Count)"

    foreach ($fallo in $fallidos) {
        Write-Log "  FALLO id_sync=$($fallo.id_sync): $($fallo.error)" "WARN"
    }

    # ----------------------------------------------------------
    # 5. Marcar como sincronizados en BD local solo los exitosos
    # ----------------------------------------------------------
    if ($exitosos.Count -gt 0) {
        # Forzar que sean enteros para prevenir cualquier inyección
        $idList = ($exitosos | ForEach-Object { [int64]$_ }) -join ","

        $cmdUpdate = $odbcConn.CreateCommand()
        $cmdUpdate.CommandText = "UPDATE sincronizacion_log SET sincronizado = 1, fecha_sincronizado = NOW() WHERE id_sync IN ($idList)"
        $updated = $cmdUpdate.ExecuteNonQuery()
        $cmdUpdate.Dispose()

        Write-Log "$updated registro(s) marcados como sincronizados en BD local." "OK"
    }

    # ----------------------------------------------------------
    # 6. Resumen por tabla
    # ----------------------------------------------------------
    if ($exitosos.Count -gt 0) {
        $procesados = $records | Where-Object { $exitosos -contains $_.id_sync }
        $porTabla   = $procesados | Group-Object -Property tabla | Sort-Object Name
        Write-Log "--- Resumen ---"
        foreach ($g in $porTabla) {
            $detalle = ($g.Group | Group-Object -Property accion | ForEach-Object { "$($_.Name):$($_.Count)" }) -join ", "
            Write-Log "  $($g.Name) → $detalle"
        }
    }

    Write-Log "=== FIN: $($exitosos.Count)/$($records.Count) registros sincronizados ===" "OK"

} catch {
    Write-Log "ERROR INESPERADO: $_" "ERROR"
    if ($odbcConn -and $odbcConn.State -eq 'Open') { $odbcConn.Close() }
    exit 1
} finally {
    if ($odbcConn -and $odbcConn.State -eq 'Open') {
        $odbcConn.Close()
    }
}
