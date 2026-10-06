<?php
/**
 * 🎯 ADMINISTRADOR CENTRAL DEL PUERTO COM5
 * ========================================
 * Este es el PROCESO PADRE que mantiene COM5 abierto 24/7
 * 
 * FUNCIONALIDADES:
 * - Mantiene puerto COM5 abierto permanentemente
 * - Escucha monedas insertadas en SEGUNDO PLANO
 * - Ejecuta comandos de dispensar cuando se solicita
 * - Los demás procesos NO tocan COM5, solo piden al padre
 * 
 * ARQUITECTURA:
 * [COM5 Hardware] <---> [com5_manager.php PADRE] <---> [APIs hijas]
 *                           ↑                              ↓
 *                      Puerto abierto               monedero_api.php
 *                      PERMANENTE                   dispensar cambio
 * 
 * Fecha: 30-04-2026
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');
set_time_limit(0);
ignore_user_abort(true);

define('LOG_FILE', __DIR__ . '/admin/dist/logs/monedero_listener.log');
define('CMD_QUEUE_FILE', __DIR__ . '/admin/dist/logs/com5_commands.queue');
define('SALDO_FILE', __DIR__ . '/admin/dist/logs/saldo_actual.json');
define('INVENTORY_FILE', __DIR__ . '/admin/dist/logs/coin_inventory.log');
define('PID_FILE', __DIR__ . '/admin/dist/logs/com5_manager.pid');

// Crear directorio de logs
$logsDir = __DIR__ . '/admin/dist/logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0755, true);
}

/**
 * Logger centralizado
 */
function logManager($mensaje, $nivel = 'INFO') {
    $timestamp = date('Y-m-d H:i:s');
    $log = "[$timestamp] [$nivel] $mensaje\n";
    
    file_put_contents(LOG_FILE, $log, FILE_APPEND);
    echo $log;
}

/**
 * Guardar PID del proceso padre
 */
function savePID() {
    $pid = getmypid();
    file_put_contents(PID_FILE, $pid);
    logManager("🆔 PID del Manager: $pid", 'INIT');
    return $pid;
}

/**
 * Verificar si ya hay un manager corriendo
 */
function checkExistingManager() {
    if (file_exists(PID_FILE)) {
        $oldPID = (int)file_get_contents(PID_FILE);
        
        // En Windows, verificar si el PID existe
        exec("tasklist /FI \"PID eq $oldPID\" 2>NUL", $output);
        foreach ($output as $line) {
            if (strpos($line, "php.exe") !== false) {
                logManager("⚠️ Manager ya corriendo (PID: $oldPID)", 'WARNING');
                return true;
            }
        }
    }
    return false;
}

/**
 * Crear script PowerShell para manejar COM5
 */
function createPowerShellManager() {
    $psScript = __DIR__ . '/com5_ps_manager.ps1';

    if (file_exists($psScript) && filesize($psScript) > 500) {
        logManager("Usando com5_ps_manager.ps1 existente (no se regenera)", 'INIT');
        return $psScript;
    }
    
    $content = <<<'PS'
# COM5 PowerShell Manager - PROCESO PADRE DEL PUERTO
param(
    [string]$CommandQueueFile,
    [string]$LogFile
)

$logsDir        = Split-Path $CommandQueueFile -Parent
$saldoFile      = Join-Path $logsDir "saldo_actual.json"
$inventoryFile  = Join-Path $logsDir "coin_inventory.log"
$signalFile     = Join-Path $logsDir "nueva_moneda_signal.json"
$dispenseQueue  = Join-Path $logsDir "monedero_dispense_queue.json"
$dispenseResp   = Join-Path $logsDir "monedero_dispense_response.json"
$insertModeFile = Join-Path $logsDir "coin_insert_mode.json"

# Tubos físicos (inventario). Hex de dispensado = DENOMINACIÓN, no número de tubo.
$tubeConfigFile = Join-Path $logsDir "tube_config.json"
$tubeMap = @{
    'A' = @{ denom = 1;  hex = '1' }
    'B' = @{ denom = 1;  hex = '1' }
    'C' = @{ denom = 10; hex = 'A' }
    'D' = @{ denom = 2;  hex = '2' }
    'E' = @{ denom = 5;  hex = '5' }
}
$denomHexMap = @{ 1='1'; 2='2'; 5='5'; 10='A'; 20='14'; 50='32'; 100='64' }
$hexToDenomMap = @{ '1'=1; '2'=2; '5'=5; 'A'=10; '14'=20; '32'=50; '64'=100 }
$DISPENSE_MECHANICAL_MS = 3000
$DISPENSE_PORT_RETRY_MS = 700
$DISPENSE_PORT_MAX_RETRIES = 20

# Configurar puerto serial
$port = New-Object System.IO.Ports.SerialPort
$port.PortName   = "COM5"
$port.BaudRate   = 9600
$port.Parity     = [System.IO.Ports.Parity]::None
$port.DataBits   = 8
$port.StopBits   = [System.IO.Ports.StopBits]::One
$port.ReadTimeout  = 100
$port.WriteTimeout = 1000

function Write-Log {
    param($Message)
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $log = "[$timestamp] [PS] $Message"
    Add-Content -Path $LogFile -Value $log -Force
    Write-Host $log
}

function Test-CargaMaquinaActiva {
    if (-not (Test-Path $insertModeFile)) { return $false }
    try {
        $mode = Get-Content $insertModeFile -Raw | ConvertFrom-Json
        return [bool]$mode.activo_carga_maquina
    } catch {
        return $false
    }
}

$script:DispenseSessionActive = $false

function Wait-DispenseAck {
    param(
        [int]$AckTimeoutMs = 8000,
        [int]$MechanicalMs = $DISPENSE_MECHANICAL_MS
    )
    $t0 = Get-Date
    $deadline = $t0.AddMilliseconds($AckTimeoutMs)
    $rxBuf = ""
    $ackAt = $null
    while ((Get-Date) -lt $deadline) {
        try {
            if ($port.IsOpen -and $port.BytesToRead -gt 0) {
                $rxBuf += $port.ReadExisting()
            }
        } catch {
            Write-Log "WARN leyendo ACK (hardware ocupado): $_"
            Start-Sleep -Milliseconds 80
        }
        while ($rxBuf -match "`n") {
            $idx = $rxBuf.IndexOf("`n")
            $line = $rxBuf.Substring(0, $idx).Trim()
            $rxBuf = $rxBuf.Substring($idx + 1)
            if ($line -eq '') { continue }
            Write-Log "RX: $line"
            if ($line -match 'INT0000003') {
                $ackAt = Get-Date
                break
            }
        }
        if ($ackAt) { break }
        Start-Sleep -Milliseconds 40
    }
    if (-not $ackAt) {
        Write-Log "WARN: Timeout esperando ACK INT0000003"
        return $false
    }
    $ackMs = [int](($ackAt - $t0).TotalMilliseconds)
    Write-Log "ACK en ${ackMs}ms -> esperando mecanismo ${MechanicalMs}ms"
    Start-Sleep -Milliseconds $MechanicalMs
    return $true
}

function Wait-PortReady {
    param(
        [int]$MaxWaitMs = 15000,
        [int]$PollMs = 250,
        [switch]$SendHabilitar
    )
    $deadline = (Get-Date).AddMilliseconds($MaxWaitMs)
    $habilitarEnviado = $false
    while ((Get-Date) -lt $deadline) {
        if ($port.IsOpen) {
            if ($SendHabilitar -and -not $habilitarEnviado) {
                try {
                    $port.Write("INT0000001`r`n")
                    $habilitarEnviado = $true
                    Start-Sleep -Milliseconds 400
                } catch { }
            }
            return $true
        }
        try {
            Write-Log "COM5 caido momentaneamente, esperando reconexion..."
            $port.Open()
            Start-Sleep -Milliseconds 400
            if ($port.IsOpen) {
                Write-Log "COM5 reconectado (sesion mantenida)"
                if ($SendHabilitar) {
                    $port.Write("INT0000001`r`n")
                    Start-Sleep -Milliseconds 400
                }
                return $true
            }
        } catch { }
        Start-Sleep -Milliseconds $PollMs
    }
    return $false
}

function Send-DispenseCommand {
    param(
        [string]$CmdStr,
        [int]$MaxRetries = 30
    )
    for ($i = 1; $i -le $MaxRetries; $i++) {
        try {
            if (-not $port.IsOpen) {
                Write-Log "Puerto no listo para dispensar, esperando... ($i/$MaxRetries)"
                if (-not (Wait-PortReady)) {
                    throw "COM5 no respondio a tiempo"
                }
            }
            $port.Write($CmdStr)
            return
        } catch {
            Write-Log "WARN write dispensado ($i/$MaxRetries): $_"
            Start-Sleep -Milliseconds 500
        }
    }
    throw "No se pudo enviar comando de dispensado"
}

# ── Actualiza saldo_actual.json sumando $monto ─────────────────────────
function Update-Saldo {
    param(
        [int]$Monto,
        [ValidateSet('moneda', 'billete')]
        [string]$Tipo = 'moneda'
    )
    try {
        $saldoActual = 0
        $saldoMonedas = 0
        $saldoBilletes = 0
        if (Test-Path $saldoFile) {
            $json = Get-Content $saldoFile -Raw | ConvertFrom-Json
            $saldoActual = [double]$json.saldo
            if ($null -ne $json.saldo_monedas) {
                $saldoMonedas = [double]$json.saldo_monedas
            } else {
                $saldoMonedas = $saldoActual
            }
            if ($null -ne $json.saldo_billetes) {
                $saldoBilletes = [double]$json.saldo_billetes
            }
        }
        if ($Tipo -eq 'billete') {
            $saldoBilletes += $Monto
        } else {
            $saldoMonedas += $Monto
        }
        $nuevoSaldo = $saldoMonedas + $saldoBilletes
        $data = @{
            saldo          = $nuevoSaldo
            saldo_monedas  = $saldoMonedas
            saldo_billetes = $saldoBilletes
            timestamp      = [int][double]::Parse((Get-Date -UFormat %s))
            fecha          = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        } | ConvertTo-Json -Compress
        Set-Content -Path $saldoFile -Value $data -Force
        $etiqueta = if ($Tipo -eq 'billete') { 'BILLETE' } else { 'MONEDA' }
        Write-Log "SALDO $etiqueta`: $saldoActual + $Monto = $nuevoSaldo (M:`$$saldoMonedas B:`$$saldoBilletes)"
    } catch {
        Write-Log "ERROR al actualizar saldo: $_"
    }
}

# ── Actualiza coin_inventory.log por tubo físico ──────────────────────
function Update-Inventory {
    param([int]$Denom)
    try {
        $tubos = [ordered]@{
            "A" = @{ cantidad = 0 }
            "B" = @{ cantidad = 0 }
            "C" = @{ cantidad = 0 }
            "D" = @{ cantidad = 0 }
            "E" = @{ cantidad = 0 }
        }
        $denoms = [ordered]@{ "1"=0; "2"=0; "5"=0; "10"=0 }

        $floatOperador = 0
        if (Test-Path $inventoryFile) {
            try {
                $existing = Get-Content $inventoryFile -Raw | ConvertFrom-Json
                if ($null -ne $existing.float_operador) {
                    $floatOperador = [int]$existing.float_operador
                }
                if ($existing.tubos) {
                    foreach ($prop in $existing.tubos.PSObject.Properties) {
                        $tubos[$prop.Name] = @{ cantidad = [int]$prop.Value.cantidad }
                    }
                }
            } catch {}
        }

        $targetTube = $null
        $minStock = [int]::MaxValue
        foreach ($tubeId in @('A','B','C','D','E')) {
            if ($tubeMap[$tubeId].denom -ne $Denom) { continue }
            $stock = [int]$tubos[$tubeId].cantidad
            if ($stock -lt $minStock) {
                $minStock = $stock
                $targetTube = $tubeId
            }
        }
        if (-not $targetTube) { $targetTube = 'A' }
        $tubos[$targetTube].cantidad++

        foreach ($tubeId in @('A','B','C','D','E')) {
            $d = "$($tubeMap[$tubeId].denom)"
            $denoms[$d] = [int]$denoms[$d] + [int]$tubos[$tubeId].cantidad
        }

        $total = 0
        foreach ($k in $denoms.Keys) { $total += [int]$k * [int]$denoms[$k] }

        $ahora = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
        $newInv = [ordered]@{
            timestamp            = $ahora
            tubos                = $tubos
            denominaciones       = $denoms
            total_pesos          = $total
            float_operador       = $floatOperador
            ultima_actualizacion = $ahora
        }
        $newInv | ConvertTo-Json -Depth 5 | Set-Content -Path $inventoryFile -Force
        Write-Log "INVENTARIO: +`$$Denom en tubo $targetTube -> total=`$$total"
    } catch {
        Write-Log "ERROR al actualizar inventario: $_"
    }
}

function Update-FloatOperador {
    param([int]$Monto)
    try {
        $floatOperador = 0
        $payload = $null
        if (Test-Path $inventoryFile) {
            $payload = Get-Content $inventoryFile -Raw | ConvertFrom-Json
            if ($null -ne $payload.float_operador) {
                $floatOperador = [int]$payload.float_operador
            }
        }
        $floatOperador = [Math]::Max(0, $floatOperador + $Monto)
        if (-not $payload) {
            $ahora = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
            $payload = [ordered]@{
                timestamp            = $ahora
                tubos                = @{ A=@{cantidad=0}; B=@{cantidad=0}; C=@{cantidad=0}; D=@{cantidad=0}; E=@{cantidad=0} }
                denominaciones       = @{ "1"=0; "2"=0; "5"=0; "10"=0 }
                total_pesos          = 0
                float_operador       = 0
                ultima_actualizacion = $ahora
            }
        }
        $payload | Add-Member -NotePropertyName float_operador -NotePropertyValue $floatOperador -Force
        $payload.ultima_actualizacion = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
        $payload | ConvertTo-Json -Depth 5 | Set-Content -Path $inventoryFile -Force
        Write-Log "FLOAT OPERADOR: $(if ($Monto -ge 0) { '+' } else { '' })`$$Monto -> `$$floatOperador"
    } catch {
        Write-Log "ERROR al actualizar float operador: $_"
    }
}

$buffer        = ""
$lastHeartbeat = Get-Date
$lastCoinTime  = [DateTime]::MinValue
$lastCoinMonto = 0

Write-Log "Iniciando loop principal..."

while ($true) {

    # ─── Auto-conexión/reconexión ────────────────────────────────────
    if (-not $port.IsOpen) {
        Write-Log "Abriendo COM5..."
        try {
            $port.Open()
            $port.Write("INT0000001`r`n")
            Write-Log "COM5 ABIERTO y aceptador HABILITADO"
            $buffer = ""
        } catch {
            Write-Log "ERROR abriendo COM5: $_ - reintentando en 3s"
            Start-Sleep -Seconds 3
            continue
        }
    }

    # ─── 1. LEER MONEDAS INSERTADAS ─────────────────────────────────
    try {
        if ($port.BytesToRead -gt 0) {
            $buffer += $port.ReadExisting()

            while ($buffer -match "`n") {
                $idx    = $buffer.IndexOf("`n")
                $line   = $buffer.Substring(0, $idx).Trim()
                $buffer = $buffer.Substring($idx + 1)

                if ($line -ne '') {
                    Write-Log "RX: $line"

                    if ($line -match 'INT([0-9A-Fa-f]{4})018') {
                        $hex   = $matches[1]
                        $monto = [Convert]::ToInt32($hex, 16)

                        # Debounce: ignorar misma moneda dentro de 300ms
                        $ahora = Get-Date
                        $diff  = ($ahora - $lastCoinTime).TotalMilliseconds
                        if ($monto -eq $lastCoinMonto -and $diff -lt 300) {
                            Write-Log "REBOTE ignorado: $monto ($([int]$diff)ms)"
                        } else {
                            $esCargaMaquina = Test-CargaMaquinaActiva
                            if ($esCargaMaquina) {
                                Write-Log "MONEDA CARGA MAQUINA: +`$$monto (solo inventario)"
                                Update-Inventory $monto
                                Update-FloatOperador $monto
                            } else {
                                Write-Log "MONEDA CLIENTE: +`$$monto"
                                Update-Saldo $monto 'moneda'
                                Update-Inventory $monto
                                @{ monto=$monto; timestamp=(Get-Date -Format "yyyy-MM-dd HH:mm:ss"); hex=$hex; tipo="moneda" } `
                                    | ConvertTo-Json -Compress | Set-Content -Path $signalFile -Force
                            }
                            $lastCoinTime  = $ahora
                            $lastCoinMonto = $monto
                        }
                    } elseif ($line -match 'INT([0-9A-Fa-f]{4})028') {
                        $hex   = $matches[1]
                        $monto = [Convert]::ToInt32($hex, 16)

                        $ahora = Get-Date
                        $diff  = ($ahora - $lastCoinTime).TotalMilliseconds
                        if ($monto -eq $lastCoinMonto -and $diff -lt 300) {
                            Write-Log "REBOTE billete ignorado: $monto ($([int]$diff)ms)"
                        } elseif (Test-CargaMaquinaActiva) {
                            Write-Log "BILLETE IGNORADO (modo carga maquina): `$$monto — solo monedas al inventario"
                        } else {
                            Write-Log "BILLETE CLIENTE: +`$$monto (solo saldo, sin inventario)"
                            Update-Saldo $monto 'billete'
                            @{ monto=$monto; timestamp=(Get-Date -Format "yyyy-MM-dd HH:mm:ss"); hex=$hex; tipo="billete" } `
                                | ConvertTo-Json -Compress | Set-Content -Path $signalFile -Force
                            $lastCoinTime  = $ahora
                            $lastCoinMonto = $monto
                        }
                    }
                }
            }
        }
    } catch {
        Write-Log "Error leyendo serial: $_ - reconectando"
        if (-not $script:DispenseSessionActive) {
            try { $port.Close() } catch {}
        } else {
            Write-Log "Ignorando cierre de puerto durante dispensado"
        }
        continue
    }

        # ─── 2. DISPENSAR (monedero_dispense_queue.json) ────────────────
        if (Test-Path $dispenseQueue) {
            if (-not $port.IsOpen) {
                Wait-PortReady | Out-Null
            }
        }
        if ((Test-Path $dispenseQueue) -and $port.IsOpen) {
            $cmdRaw = $null
            try { $cmdRaw = Get-Content $dispenseQueue -Raw | ConvertFrom-Json } catch {}

            if ($cmdRaw -and $cmdRaw.status -eq 'RAW_CMD') {
                # Comando raw directo al hardware (para diagnóstico de tubos)
                Remove-Item $dispenseQueue -Force -ErrorAction SilentlyContinue
                $rawCmd = "$($cmdRaw.comando)`r`n"
                Write-Log "RAW_CMD: $($cmdRaw.comando)"
                try {
                    $port.Write($rawCmd)
                    Start-Sleep -Milliseconds 600
                    $rxBuf = ""
                    $deadline = (Get-Date).AddMilliseconds(800)
                    while ((Get-Date) -lt $deadline) {
                        if ($port.BytesToRead -gt 0) { $rxBuf += $port.ReadExisting() }
                        Start-Sleep -Milliseconds 50
                    }
                    Write-Log "RAW_RX: $rxBuf"
                    @{ success=$true; rx=$rxBuf; timestamp=[int][double]::Parse((Get-Date -UFormat %s)) } `
                        | ConvertTo-Json -Compress | Set-Content -Path $dispenseResp -Force
                } catch {
                    Write-Log "RAW_CMD ERROR: $_"
                    @{ success=$false; error="$_"; timestamp=[int][double]::Parse((Get-Date -UFormat %s)) } `
                        | ConvertTo-Json -Compress | Set-Content -Path $dispenseResp -Force
                }
            } elseif ($cmdRaw -and $cmdRaw.status -eq 'PENDING') {
                Remove-Item $dispenseQueue -Force -ErrorAction SilentlyContinue
                $script:DispenseSessionActive = $true
                Write-Log "DISPENSANDO: $($cmdRaw.monto) (sesion COM5 continua, sin re-habilitar entre monedas)"

                $totalDispensado = 0
                $errores         = @()
                $hexList         = @()

                if ($cmdRaw.tubos_cmd) {
                    $hexList = @($cmdRaw.tubos_cmd)
                } else {
                    foreach ($prop in $cmdRaw.desglose.PSObject.Properties) {
                        $denom    = [int]$prop.Name
                        $cantidad = [int]$prop.Value
                        if ($cantidad -le 0) { continue }
                        if (-not $denomHexMap.ContainsKey($denom)) {
                            $errores += "Denominacion invalida: $$denom"
                            continue
                        }
                        $hex = $denomHexMap[$denom]
                        for ($j = 0; $j -lt $cantidad; $j++) {
                            $hexList += $hex
                        }
                    }
                }

                if ($port.BytesToRead -gt 0) {
                    $preDrain = $port.ReadExisting().Trim()
                    if ($preDrain) { Write-Log "RX (pre-dispense): $preDrain" }
                }

                foreach ($hex in $hexList) {
                    $montoMoneda = 0
                    $hexKey = "$hex".ToUpper()
                    if ($hexToDenomMap.ContainsKey($hexKey)) {
                        $montoMoneda = $hexToDenomMap[$hexKey]
                    } else {
                        try { $montoMoneda = [Convert]::ToInt32($hexKey, 16) } catch {}
                    }

                    $cmdStr = "INT000${hex}003`r`n"
                    Write-Log "DISPENSE SEND: $($cmdStr.Trim())"
                    try {
                        Send-DispenseCommand -CmdStr $cmdStr
                    } catch {
                        $errores += "Puerto COM5 al dispensar `$$montoMoneda (hex=$hex): $_"
                        Write-Log "DISPENSE ABORT: $_"
                        break
                    }

                    if (Wait-DispenseAck) {
                        Write-Log "DISPENSED OK: denom hex=$hex (`$$montoMoneda)"
                        $totalDispensado += $montoMoneda
                    } else {
                        $errores += "Sin confirmacion al dispensar `$$montoMoneda (hex=$hex)"
                        Write-Log "DISPENSE FAIL: denom hex=$hex (`$$montoMoneda)"
                        break
                    }
                }

                $montoSolicitado = [int]$cmdRaw.monto
                if ($totalDispensado -lt $montoSolicitado -and $errores.Count -eq 0) {
                    $errores += "Dispensado parcial: `$$totalDispensado de `$$montoSolicitado"
                }

                Write-Log "DISPENSE_DONE: $totalDispensado de $montoSolicitado"
                @{
                    success          = ($errores.Count -eq 0 -and $totalDispensado -ge $montoSolicitado)
                    total_dispensado = $totalDispensado
                    errores          = $errores
                    timestamp        = [int][double]::Parse((Get-Date -UFormat %s))
                } | ConvertTo-Json -Compress | Set-Content -Path $dispenseResp -Force
                $script:DispenseSessionActive = $false
            }
        }

        # ─── 3. HEARTBEAT cada 30 s ─────────────────────────────────────
        if (((Get-Date) - $lastHeartbeat).TotalSeconds -ge 30) {
            Write-Log "HEARTBEAT - Puerto activo"
            $lastHeartbeat = Get-Date
        }

        Start-Sleep -Milliseconds 50
    }
PS;
    
    // Escribir con UTF-8 BOM para que PowerShell 5.1 interprete correctamente
    $bom = "\xEF\xBB\xBF";
    file_put_contents($psScript, $bom . $content);
    logManager("Script PowerShell creado: $psScript", 'INIT');
    
    return $psScript;
}

/**
 * MAIN - Iniciar Manager
 */
logManager("========================================", 'INIT');
logManager("🚀 COM5 MANAGER - PROCESO PADRE", 'INIT');
logManager("========================================", 'INIT');

// Verificar instancia única
if (checkExistingManager()) {
    logManager("❌ Ya hay un manager corriendo. Saliendo...", 'ERROR');
    exit(1);
}

// Guardar PID
$myPID = savePID();

// Crear script PowerShell
$psScript = createPowerShellManager();

// Limpiar cola de comandos
if (file_exists(CMD_QUEUE_FILE)) {
    unlink(CMD_QUEUE_FILE);
}

logManager("🎯 Iniciando proceso PowerShell...", 'INIT');

// Matar cualquier PS zombie de com5_ps_manager.ps1 que siga corriendo
$killCmd = 'powershell.exe -NoProfile -NonInteractive -Command "Get-WmiObject Win32_Process | Where-Object { $_.CommandLine -like \'*com5_ps_manager*\' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force -ErrorAction SilentlyContinue }"';
pclose(popen("start /B " . $killCmd, "r"));
sleep(1); // dar tiempo al kill
logManager("Procesos PS anteriores terminados", 'INIT');

// Ejecutar PowerShell en segundo plano
$command = sprintf(
    'powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "%s" -CommandQueueFile "%s" -LogFile "%s"',
    $psScript,
    CMD_QUEUE_FILE,
    LOG_FILE
);

logManager("📡 Comando: $command", 'DEBUG');

// Iniciar proceso
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    pclose(popen("start /B " . $command, "r"));
} else {
    exec($command . " > /dev/null 2>&1 &");
}

logManager("✅ PowerShell Manager iniciado", 'INIT');
logManager("🔓 Puerto COM5 bajo control del PADRE", 'INIT');
logManager("📥 Aceptador: ACTIVO", 'INIT');
logManager("📤 Dispensador: LISTO", 'INIT');
logManager("========================================", 'INIT');
logManager("✨ Sistema operativo - Presiona Ctrl+C para detener", 'INIT');

// Mantener proceso PHP vivo para monitoreo
while (true) {
    sleep(10);
    
    // Verificar que PowerShell sigue corriendo
    exec("tasklist /FI \"IMAGENAME eq powershell.exe\" 2>NUL", $output);
    $psRunning = false;
    foreach ($output as $line) {
        if (stripos($line, 'powershell.exe') !== false) {
            $psRunning = true;
            break;
        }
    }
    
    if (!$psRunning) {
        logManager("❌ PowerShell Manager detenido. Reiniciando...", 'ERROR');
        
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            pclose(popen("start /B " . $command, "r"));
        }
        
        sleep(2);
    }
}
