#Requires -Version 5.1

param(
    [string]$EndpointUrl = "https://vendingbox.online/sincronizar_local.php",
    [string]$DsnName = "MySQLDSN"
)

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

$LogDir  = Join-Path $PSScriptRoot "logs"
$LogFile = Join-Path $LogDir "sync_local_$(Get-Date -Format 'yyyyMMdd').log"

$TablasSync = @(
    'categorias',
    'configuracion_empresa',
    'config_caja',
    'cortes',
    'page_titles',
    'productos',
    'subcategorias'
)

$TablasPurge = @('productos', 'categorias', 'subcategorias', 'page_titles')

if (-not (Test-Path $LogDir)) {
    New-Item -ItemType Directory -Path $LogDir | Out-Null
}

function Write-Log {
    param(
        [string]$Message,
        [ValidateSet("INFO","WARN","ERROR","OK")][string]$Level = "INFO"
    )

    $ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $line = "[$ts][$Level] $Message"
    Write-Host $line -ForegroundColor $(switch ($Level) {
        "OK"    { "Green" }
        "WARN"  { "Yellow" }
        "ERROR" { "Red" }
        default  { "White" }
    })
    Add-Content -Path $LogFile -Value $line -Encoding UTF8
}

function Escape-SqlValue {
    param($Value)

    if ($null -eq $Value) {
        return "NULL"
    }

    if ($Value -is [bool]) {
        return $(if ($Value) { "1" } else { "0" })
    }

    if ($Value -is [datetime]) {
        return "'" + $Value.ToString("yyyy-MM-dd HH:mm:ss") + "'"
    }

    $text = [string]$Value
    $text = $text.Replace("'", "''")
    return "'$text'"
}

function Get-TablePrimaryKey {
    param([string]$Tabla)

    switch ($Tabla) {
        'productos' { return 'id_producto' }
        'categorias' { return 'id_categoria' }
        'subcategorias' { return 'id_subcategoria' }
        'page_titles' { return 'id' }
        'configuracion_empresa' { return 'id' }
        'config_caja' { return 'id' }
        'cortes' { return 'id' }
        default { return 'id' }
    }
}

function Apply-PendienteNube {
    param(
        $Conn,
        $Registro
    )

    $tabla = [string]$Registro.tabla
    $accion = [string]$Registro.accion
    $idRegistro = [int]$Registro.id_registro
    $pk = Get-TablePrimaryKey $tabla

    switch ($accion.ToUpper()) {
        'DELETE' {
            $sql = "DELETE FROM ``$tabla`` WHERE ``$pk`` = $idRegistro"
            $cmd = $Conn.CreateCommand()
            $cmd.CommandText = $sql
            [void]$cmd.ExecuteNonQuery()
            return
        }
        'UPDATE' {
            $datos = $Registro.datos
            if ($null -eq $datos) { return }

            $columnNames = @($datos.PSObject.Properties.Name)
            if ($columnNames.Count -eq 0) { return }

            $sets = ($columnNames | ForEach-Object {
                '`' + $_ + '` = ' + (Escape-SqlValue $datos.$_)
            }) -join ','
            $sql = "UPDATE ``$tabla`` SET $sets WHERE ``$pk`` = $idRegistro"
            $cmd = $Conn.CreateCommand()
            $cmd.CommandText = $sql
            [void]$cmd.ExecuteNonQuery()
            return
        }
        default {
            $datos = $Registro.datos
            if ($null -eq $datos) { return }

            $columnNames = @($datos.PSObject.Properties.Name)
            if ($columnNames.Count -eq 0) { return }

            if ($columnNames -notcontains $pk) {
                $columnNames += $pk
                $datos | Add-Member -NotePropertyName $pk -NotePropertyValue $idRegistro -Force
            }

            $columnsSql = ($columnNames | ForEach-Object { '`' + $_ + '`' }) -join ","
            $valuesSql = ($columnNames | ForEach-Object { Escape-SqlValue $datos.$_ }) -join ","
            $updateSql = ($columnNames | ForEach-Object { '`' + $_ + '` = VALUES(`' + $_ + '`)' }) -join ","
            $sql = "INSERT INTO ``$tabla`` ($columnsSql) VALUES ($valuesSql) ON DUPLICATE KEY UPDATE $updateSql;"

            $cmd = $Conn.CreateCommand()
            $cmd.CommandText = $sql
            [void]$cmd.ExecuteNonQuery()
        }
    }
}

function Mark-PendientesNube {
    param(
        [string]$Url,
        [int[]]$Ids
    )

    if ($Ids.Count -eq 0) { return 0 }

    $payload = @{ marcar_sincronizados = @($Ids) } | ConvertTo-Json -Compress
    $bytes = [System.Text.Encoding]::UTF8.GetBytes($payload)
    $resp = Invoke-RestMethod -Uri $Url -Method Post -Body $bytes -ContentType "application/json; charset=utf-8"
    return [int]($resp.marcados)
}

$conn = $null

try {
    Write-Log "=== INICIO SINCRONIZACION LOCAL (NUBE -> LOCAL) ==="
    Write-Log "DSN: $DsnName | Endpoint: $EndpointUrl"

    Add-Type -AssemblyName System.Data
    $conn = New-Object System.Data.Odbc.OdbcConnection("DSN=$DsnName")
    $conn.Open()
    Write-Log "Conexión ODBC abierta (DSN: $DsnName)"

    try {
        $response = Invoke-RestMethod -Uri $EndpointUrl -Method Get -ErrorAction Stop
    } catch {
        $statusCode = $null
        $body = ""

        if ($_.Exception.Response) {
            try { $statusCode = [int]$_.Exception.Response.StatusCode } catch { }
            try {
                $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
                $body = $reader.ReadToEnd()
                $reader.Close()
            } catch { }
        }

        $msg = "Error HTTP al obtener datos de nube"
        if ($statusCode) { $msg += " (status=$statusCode)" }
        if ($body) { $msg += ". Body: $body" }
        throw $msg
    }

    if (-not $response) {
        throw "Respuesta vacía del endpoint de nube"
    }

    if (($response.PSObject.Properties.Name -contains 'ok') -and ($response.ok -eq $false)) {
        throw "Endpoint respondió error: $($response.error)"
    }

    $totalFilas = 0
    $pendientes = @()
    if ($response.PSObject.Properties.Name -contains 'pendientes_nube') {
        $pendientes = @($response.pendientes_nube)
    }

    if ($pendientes.Count -gt 0) {
        Write-Log "Pendientes NUBE detectados: $($pendientes.Count)"
        foreach ($pendiente in $pendientes) {
            try {
                Apply-PendienteNube -Conn $conn -Registro $pendiente
                $totalFilas++
                Write-Log "  Aplicado id_sync=$($pendiente.id_sync) $($pendiente.tabla)/$($pendiente.accion)"
            } catch {
                Write-Log "  FALLO id_sync=$($pendiente.id_sync): $_" "WARN"
            }
        }
    }

    foreach ($tabla in $TablasSync) {
        if ($response.PSObject.Properties.Name -notcontains $tabla) {
            Write-Log "Tabla omitida (no viene en respuesta): $tabla" "WARN"
            continue
        }

        $rows = @($response.$tabla)
        Write-Log "Sincronizando tabla: $tabla (filas: $($rows.Count))"

        foreach ($fila in $rows) {
            $columnNames = @($fila.PSObject.Properties.Name)
            if ($columnNames.Count -eq 0) {
                continue
            }

            $columnsSql = ($columnNames | ForEach-Object { '`' + $_ + '`' }) -join ","
            $valuesSql = ($columnNames | ForEach-Object { Escape-SqlValue $fila.$_ }) -join ","
            $updateSql = ($columnNames | ForEach-Object { '`' + $_ + '` = VALUES(`' + $_ + '`)' }) -join ","
            $sql = "INSERT INTO $tabla ($columnsSql) VALUES ($valuesSql) ON DUPLICATE KEY UPDATE $updateSql;"

            $cmd = $conn.CreateCommand()
            $cmd.CommandText = $sql
            [void]$cmd.ExecuteNonQuery()
            $totalFilas++
        }

        if ($TablasPurge -contains $tabla -and $rows.Count -gt 0) {
            $pk = Get-TablePrimaryKey $tabla
            $ids = @($rows | ForEach-Object { [int64]$_.$pk })
            if ($ids.Count -gt 0) {
                $idList = ($ids -join ",")
                $purgeSql = "DELETE FROM ``$tabla`` WHERE ``$pk`` NOT IN ($idList)"
                $purgeCmd = $conn.CreateCommand()
                $purgeCmd.CommandText = $purgeSql
                $purged = $purgeCmd.ExecuteNonQuery()
                if ($purged -gt 0) {
                    Write-Log "  Eliminados en local (ya no existen en nube): $purged" "OK"
                }
            }
        }
    }

    if ($pendientes.Count -gt 0) {
        $idsMarcar = @($pendientes | ForEach-Object { [int]$_.id_sync })
        try {
            $marcados = Mark-PendientesNube -Url $EndpointUrl -Ids $idsMarcar
            Write-Log "Pendientes marcados en nube: $marcados" "OK"
        } catch {
            Write-Log "No se pudieron marcar pendientes en nube (sube sincronizar_local.php actualizado): $_" "WARN"
        }
    }

    Write-Log "Filas aplicadas en local: $totalFilas" "OK"
    Write-Log "=== FIN SINCRONIZACION LOCAL ===" "OK"
    exit 0
}
catch {
    Write-Log "ERROR: $_" "ERROR"
    exit 1
}
finally {
    if ($conn -and $conn.State -eq 'Open') {
        $conn.Close()
        Write-Log "Conexión ODBC cerrada"
    }
}
