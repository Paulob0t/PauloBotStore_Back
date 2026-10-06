<?php
/**
 * Listener de Monedero - Script que escucha el puerto serial constantemente
 * Este script debe ejecutarse como servicio o tarea programada
 * 
 * Detecta cuando se insertan monedas y actualiza el saldo disponible
 * Fecha: 21-01-2026
 */

// Configuración
define('PUERTO_SERIAL', 'COM5');
define('BAUDRATE', 9600);
define('DATA_BITS', 8);
define('PARITY', 'None');
define('LOG_FILE', __DIR__ . '/admin/dist/logs/monedero_listener.log');
define('SALDO_FILE', __DIR__ . '/admin/dist/logs/saldo_actual.json');
define('COIN_INVENTORY_FILE', __DIR__ . '/admin/dist/logs/coin_inventory.log');

// 💰 NUEVO: Archivos para comunicación con sistema de dispensado
define('DISPENSE_QUEUE_FILE', __DIR__ . '/admin/dist/logs/monedero_dispense_queue.json');
define('DISPENSE_RESPONSE_FILE', __DIR__ . '/admin/dist/logs/monedero_dispense_response.json');
define('DISPENSE_CHECK_INTERVAL', 0.5); // Verificar comandos de dispensar cada 500ms

// Configuración anti-duplicados
define('DEBOUNCE_TIEMPO', 0.3); // Segundos entre inserciones válidas (evita duplicados causados por rebotes del hardware)
define('MONEDAS_VALIDAS', [1, 2, 5, 10, 20]); // Monedas aceptadas
define('BILLETES_VALIDOS', [20, 50, 100, 200, 500, 1000]); // Billetes aceptados
define('MAX_REGISTROS_CACHE', 10); // Últimas N lecturas en caché

// Variables globales
$ultimaMonedaTimestamp = 0;
$ultimaMonedaMonto = 0;
$ultimaMonedaHash = ''; // Hash único de la última moneda procesada
$cacheDatos = [];

// Asegurar que el directorio de logs existe
$logDir = dirname(LOG_FILE);
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

/**
 * Registra mensaje en log
 */
function logMessage($mensaje) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $mensaje\n";
    file_put_contents(LOG_FILE, $logEntry, FILE_APPEND);
    echo $logEntry; // También imprimir en consola
}

/**
 * Obtiene el saldo actual
 */
function getSaldo() {
    if (file_exists(SALDO_FILE)) {
        $data = json_decode(file_get_contents(SALDO_FILE), true);
        return $data['saldo'] ?? 0;
    }
    return 0;
}

/**
 * Actualiza el saldo
 */
function setSaldo($saldo) {
    $data = [
        'saldo' => (float)$saldo,
        'timestamp' => time(),
        'fecha' => date('Y-m-d H:i:s')
    ];
    file_put_contents(SALDO_FILE, json_encode($data, JSON_PRETTY_PRINT));
}

/**
 * 💰 NUEVO: Registra una moneda recibida en el inventario de cambio
 */
function registrarMonedaEnInventario($denominacion) {
    try {
        // Cargar inventario actual
        if (!file_exists(COIN_INVENTORY_FILE)) {
            $inventory = [
                'timestamp' => date('Y-m-d H:i:s'),
                'denominaciones' => ['1' => 0, '2' => 0, '5' => 0, '10' => 0, '20' => 0],
                'total_pesos' => 0,
                'ultima_actualizacion' => date('Y-m-d H:i:s'),
                'log' => []
            ];
        } else {
            $inventory = json_decode(file_get_contents(COIN_INVENTORY_FILE), true);
            if (!$inventory) {
                $inventory = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'denominaciones' => ['1' => 0, '2' => 0, '5' => 0, '10' => 0, '20' => 0],
                    'total_pesos' => 0,
                    'ultima_actualizacion' => date('Y-m-d H:i:s'),
                    'log' => []
                ];
            }
        }
        
        // Incrementar cantidad de esta denominación
        $denomStr = (string)$denominacion;
        if (!isset($inventory['denominaciones'][$denomStr])) {
            $inventory['denominaciones'][$denomStr] = 0;
        }
        $inventory['denominaciones'][$denomStr]++;
        
        // Recalcular total en pesos
        $total = 0;
        foreach ($inventory['denominaciones'] as $denom => $cantidad) {
            $total += ((int)$denom * (int)$cantidad);
        }
        $inventory['total_pesos'] = $total;
        
        // Agregar al log
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tipo' => 'INGRESO',
            'denominacion' => $denominacion,
            'cantidad' => 1,
            'total_denominacion_despues' => $inventory['denominaciones'][$denomStr]
        ];
        $inventory['log'][] = $logEntry;
        
        // Mantener solo últimas 100 entradas del log
        if (count($inventory['log']) > 100) {
            $inventory['log'] = array_slice($inventory['log'], -100);
        }
        
        $inventory['ultima_actualizacion'] = date('Y-m-d H:i:s');
        
        // Guardar
        file_put_contents(COIN_INVENTORY_FILE, json_encode($inventory, JSON_PRETTY_PRINT));
        
        logMessage("💰 INVENTARIO: Moneda $$denominacion registrada. Total de $$denominacion: {$inventory['denominaciones'][$denomStr]}");
        
    } catch (Exception $e) {
        logMessage("⚠️ ERROR INVENTARIO: " . $e->getMessage());
    }
}

/**
 * Agrega monto al saldo con validación anti-duplicados
 */
function agregarSaldo($monto, $tipo = 'desconocido') {
    global $ultimaMonedaTimestamp, $ultimaMonedaMonto, $ultimaMonedaHash, $cacheDatos;
    
    $ahora = microtime(true);
    
    // 1. VALIDAR QUE EL MONTO ES VÁLIDO (moneda o billete)
    $esMoneda = in_array($monto, MONEDAS_VALIDAS);
    $esBillete = in_array($monto, BILLETES_VALIDOS);
    
    if (!$esMoneda && !$esBillete) {
        logMessage("⚠️ RECHAZADO: Monto $$monto no es válido (ni moneda ni billete)");
        return false;
    }
    
    // Determinar tipo si no se especificó
    if ($tipo === 'desconocido') {
        $tipo = $esMoneda ? 'moneda' : 'billete';
    }
    
    // 2. CREAR HASH ÚNICO PARA ESTA LECTURA
    // Incluye timestamp con precisión de milisegundos para diferenciar lecturas reales
    $hashDato = md5($monto . $tipo . floor($ahora * 1000));
    
    // 3. DEBOUNCING MEJORADO - Solo rechazar si:
    //    a) Es el MISMO hash (lectura exactamente idéntica)
    //    b) O es el mismo monto Y tipo en menos de 0.3 segundos (rebote de hardware)
    $tiempoTranscurrido = $ahora - $ultimaMonedaTimestamp;
    
    // Verificar si es el mismo hash (100% duplicado)
    if ($hashDato === $ultimaMonedaHash) {
        logMessage("⛔ DUPLICADO EXACTO RECHAZADO: $$monto ($tipo) - mismo hash");
        return false;
    }
    
    // Verificar rebote de hardware (mismo monto+tipo en ventana muy corta)
    if ($tiempoTranscurrido < DEBOUNCE_TIEMPO && $monto == $ultimaMonedaMonto) {
        logMessage("⛔ REBOTE RECHAZADO: $$monto ($tipo) hace " . round($tiempoTranscurrido, 3) . "s - posible ruido de hardware");
        return false;
    }
    
    // 4. VERIFICAR CACHÉ - Evitar mismo hash procesado múltiples veces
    if (in_array($hashDato, $cacheDatos)) {
        logMessage("⛔ CACHÉ RECHAZADO: $$monto ($tipo) - hash ya procesado");
        return false;
    }
    
    // 5. TODO OK - Agregar moneda/billete
    $saldoActual = getSaldo();
    $nuevoSaldo = $saldoActual + $monto;
    setSaldo($nuevoSaldo);
    
    // 💰 REGISTRAR EN INVENTARIO DE MONEDAS (solo monedas, no billetes)
    if ($tipo === 'moneda') {
        registrarMonedaEnInventario($monto);
    }
    
    // Actualizar tracking
    $ultimaMonedaTimestamp = $ahora;
    $ultimaMonedaMonto = $monto;
    $ultimaMonedaHash = $hashDato;
    $cacheDatos[] = $hashDato;
    
    // Mantener caché limitado
    if (count($cacheDatos) > MAX_REGISTROS_CACHE) {
        array_shift($cacheDatos);
    }
    
    $emoji = $tipo === 'billete' ? '💵' : '🪙';
    $tipoStr = strtoupper($tipo);
    logMessage("✅ $emoji $tipoStr ACEPTADA: $$monto | Saldo total: $$nuevoSaldo");
    return true;
}

/**
 * Parsea la respuesta del monedero
 * Diferentes monederos tienen diferentes formatos de salida
 */
function parsearRespuestaMonedero($respuesta) {
    // Log del dato crudo recibido
    $respuestaHex = bin2hex($respuesta);
    $longitudDatos = strlen($respuesta);
    
    logMessage("🔍 DEBUG RAW: ASCII='$respuesta' HEX=$respuestaHex LEN=$longitudDatos");
    
    // Filtrar datos corruptos o muy cortos/largos
    if ($longitudDatos < 2 || $longitudDatos > 50) {
        logMessage("⚠️ DATOS IGNORADOS: Longitud inválida ($longitudDatos bytes)");
        return null;
    }
    
    // Limpiar espacios y caracteres de control
    $respuestaLimpia = trim($respuesta);
    $respuestaLimpia = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $respuestaLimpia);
    
    // Verificar que después de limpiar aún hay datos
    if (empty($respuestaLimpia)) {
        logMessage("⚠️ DATOS VACÍOS después de limpieza");
        return null;
    }
    
    logMessage("🔍 DEBUG LIMPIO: '$respuestaLimpia'");
    
    // *** FORMATO MONEDERO: INT + 4 dígitos hex + 018 (MONEDA) ***
    // Ejemplos: INT000A018 (10), INT0001018 (1), INT0002018 (2)
    if (preg_match('/INT([0-9A-F]{4})018/i', $respuestaLimpia, $matches)) {
        $valorHex = $matches[1];
        $valor = hexdec($valorHex);
        logMessage("✅ MATCH MONEDA (INT-HEX-018): HEX=$valorHex → DECIMAL=$valor");
        return ['valor' => (float)$valor, 'tipo' => 'moneda'];
    }
    
    // *** FORMATO BILLETERO: INT + 4 dígitos hex + 028 (BILLETE) ***
    // Ejemplos: INT0064028 (100), INT0014028 (20), INT0032028 (50), INT00C8028 (200)
    if (preg_match('/INT([0-9A-F]{4})028/i', $respuestaLimpia, $matches)) {
        $valorHex = $matches[1];
        $valor = hexdec($valorHex);
        logMessage("✅ MATCH BILLETE (INT-HEX-028): HEX=$valorHex → DECIMAL=$valor");
        return ['valor' => (float)$valor, 'tipo' => 'billete'];
    }
    
    // *** FORMATO ALTERNATIVO BILLETERO: Solo 4 hex + 028 ***
    // Ejemplos: 0064028 (100), 0014028 (20), 0032028 (50), 00C8028 (200)
    if (preg_match('/([0-9A-F]{4})028/i', $respuestaLimpia, $matches)) {
        $valorHex = $matches[1];
        $valor = hexdec($valorHex);
        logMessage("✅ MATCH BILLETE ALT (HEX-028): HEX=$valorHex → DECIMAL=$valor");
        return ['valor' => (float)$valor, 'tipo' => 'billete'];
    }
    
    // *** FORMATO ALTERNATIVO MONEDERO: Solo 4 hex + 018 ***
    if (preg_match('/([0-9A-F]{4})018/i', $respuestaLimpia, $matches)) {
        $valorHex = $matches[1];
        $valor = hexdec($valorHex);
        logMessage("✅ MATCH MONEDA ALT (HEX-018): HEX=$valorHex → DECIMAL=$valor");
        return ['valor' => (float)$valor, 'tipo' => 'moneda'];
    }
    
    // Intentar diferentes patrones (formatos genéricos)
    $patrones = [
        '/COIN[:\s]*(\d+\.?\d*)/',        // COIN:10 o COIN 10
        '/BILL[:\s]*(\d+\.?\d*)/',        // BILL:100
        '/\$(\d+\.?\d*)/',                 // $10
        '/(\d+\.?\d*)\s*(PESOS?|MXN|USD)?/i', // 10 PESOS
        '/CREDIT[:\s]*(\d+\.?\d*)/',      // CREDIT:10
        '/PULSE[:\s]*(\d+)/',              // PULSE:1 (pulsos)
        '/VALUE[:\s]*(\d+\.?\d*)/',       // VALUE:10
        '/MONEY[:\s]*(\d+\.?\d*)/',       // MONEY:10
        '/(\d+\.?\d*)/',                   // Solo números
    ];
    
    foreach ($patrones as $index => $patron) {
        if (preg_match($patron, $respuestaLimpia, $matches)) {
            $valor = (float)$matches[1];
            logMessage("✅ MATCH en patrón #$index: Valor extraído = $valor");
            return ['valor' => $valor, 'tipo' => 'desconocido'];
        }
    }
    
    // Intentar como número puro después de limpiar
    if (is_numeric($respuestaLimpia)) {
        $valor = (float)$respuestaLimpia;
        logMessage("✅ MATCH numérico directo: Valor = $valor");
        return ['valor' => $valor, 'tipo' => 'desconocido'];
    }
    
    // Buscar cualquier secuencia de dígitos
    if (preg_match('/(\d+)/', $respuestaLimpia, $matches)) {
        $valor = (float)$matches[1];
        logMessage("⚠️ MATCH parcial (solo dígitos): Valor = $valor");
        
        // VALIDACIÓN FINAL: Solo retornar si está en el rango esperado
        if ($valor >= 1 && $valor <= 1000) {
            return ['valor' => $valor, 'tipo' => 'desconocido'];
        } else {
            logMessage("❌ VALOR FUERA DE RANGO: $valor (se esperaba 1-1000)");
            return null;
        }
    }
    
    logMessage("❌ NO SE PUDO PARSEAR: '$respuesta'");
    return null;
}

/**
 * Script de PowerShell para escuchar puerto serial continuamente
 */
function escucharPuertoSerial() {
    $puerto = PUERTO_SERIAL;
    $baudrate = BAUDRATE;
    $databits = DATA_BITS;
    $parity = PARITY;
    $dispenseQueuePath = str_replace('\\', '\\\\', DISPENSE_QUEUE_FILE);
    $dispenseResponsePath = str_replace('\\', '\\\\', DISPENSE_RESPONSE_FILE);
    
    // Script PowerShell que escucha el puerto indefinidamente CON RECONEXIÓN
    $psScript = <<<POWERSHELL
\$ErrorActionPreference = "Continue"
\$puerto = "$puerto"
\$baudrate = $baudrate
\$reintentos = 0
\$maxReintentos = 999999

while (\$reintentos -lt \$maxReintentos) {
    try {
        \$port = New-Object System.IO.Ports.SerialPort
        \$port.PortName = \$puerto
        \$port.BaudRate = \$baudrate
        \$port.DataBits = $databits
        \$port.Parity = [System.IO.Ports.Parity]::$parity
        \$port.StopBits = [System.IO.Ports.StopBits]::One
        \$port.ReadTimeout = 1000
        \$port.WriteTimeout = 1000
        \$port.DtrEnable = \$true
        \$port.RtsEnable = \$true
        
        Write-Output "PUERTO_ABIERTO:\$puerto@\$baudrate"
        \$port.Open()
        Write-Output "DEBUG:Puerto abierto exitosamente (intento \$(\$reintentos+1))"
        \$reintentos = 0
        
        # Esperar que el hardware inicialice antes de enviar activacion
        Start-Sleep -Milliseconds 300
        
        # Enviar comando de activacion al hardware (equivalente a Hercules)
        # Envia: INT0000001 mas CRLF (carriage return + line feed)
        try {
            \$cmdActivacion = "INT0000001"
            # Enviar como string con CRLF - equivale a INT0000001 + 0x0D 0x0A
            \$port.Write(\$cmdActivacion + "`r`n")
            Write-Output "ACTIVACION:Comando enviado: \$cmdActivacion + CRLF (0x0D 0x0A)"
        } catch {
            Write-Output "DEBUG:Advertencia al enviar activacion: \$_"
        }
        
        # Pausa para que el hardware procese el comando antes de empezar a leer
        Start-Sleep -Milliseconds 200
        
        # Mapa de denominaciones a hex para dispensar
        \$hexMap = @{1='1'; 2='2'; 5='5'; 10='A'; 20='14'; 50='32'; 100='64'; 200='C8'; 500='1F4'}
        \$dispenseQueueFile = "$dispenseQueuePath"
        \$dispenseResponseFile = "$dispenseResponsePath"
        
        # Loop infinito leyendo datos
        \$buffer = ""
        \$ultimoHeartbeat = Get-Date
        
        while (\$true) {
            try {
                # Heartbeat cada 30 segundos para verificar que sigue vivo
                \$ahora = Get-Date
                if ((\$ahora - \$ultimoHeartbeat).TotalSeconds -gt 30) {
                    Write-Output "HEARTBEAT:OK"
                    \$ultimoHeartbeat = \$ahora
                }
                
                # Verificar que el puerto sigue abierto
                if (-not \$port.IsOpen) {
                    Write-Output "ERROR:Puerto cerrado inesperadamente"
                    break
                }
                
                if (\$port.BytesToRead -gt 0) {
                    # Leer todos los datos disponibles
                    \$bytesDisponibles = \$port.BytesToRead
                    Write-Output "DEBUG:Bytes disponibles: \$bytesDisponibles"
                    
                    \$data = \$port.ReadExisting()
                    \$buffer += \$data
                    
                    # Si hay salto de línea, procesar
                    if (\$buffer -match "[\r\n]") {
                        \$lines = \$buffer -split "[\r\n]+"
                        foreach (\$line in \$lines) {
                            if (\$line.Trim().Length -gt 0) {
                                \$hexData = [System.BitConverter]::ToString([System.Text.Encoding]::ASCII.GetBytes(\$line)) -replace '-',''
                                Write-Output "DATA:\$line"
                                Write-Output "HEX:\$hexData"
                            }
                        }
                        \$buffer = ""
                    } elseif (\$buffer.Length -gt 100) {
                        # Si el buffer es muy largo sin saltos, procesarlo
                        if (\$buffer.Trim().Length -gt 0) {
                            \$hexData = [System.BitConverter]::ToString([System.Text.Encoding]::ASCII.GetBytes(\$buffer)) -replace '-',''
                            Write-Output "DATA:\$buffer"
                            Write-Output "HEX:\$hexData"
                        }
                        \$buffer = ""
                    }
                }
                
                # ====================================================
                # PROCESAR COMANDOS DE DISPENSADO (misma conexion COM5)
                # ====================================================
                if (Test-Path \$dispenseQueueFile) {
                    try {
                        \$queueContent = Get-Content \$dispenseQueueFile -Raw
                        \$cmd = \$queueContent | ConvertFrom-Json
                        
                        if (\$cmd -and \$cmd.status -eq 'PENDING') {
                            Remove-Item \$dispenseQueueFile -Force
                            
                            Write-Output "DISPENSING:\$(\$cmd.monto)"
                            \$totalDispensado = 0
                            \$erroresDispensado = @()
                            
                            foreach (\$prop in \$cmd.desglose.PSObject.Properties) {
                                \$denom = [int]\$prop.Name
                                \$cant = [int]\$prop.Value
                                
                                if (-not \$hexMap.ContainsKey(\$denom)) {
                                    \$erroresDispensado += "Denominacion invalida: \$denom"
                                    continue
                                }
                                
                                \$hex = \$hexMap[\$denom]
                                
                                for (\$i = 0; \$i -lt \$cant; \$i++) {
                                    try {
                                        \$command = "INT000\${hex}003"
                                        \$port.Write(\$command + "`r`n")
                                        Write-Output "DISPENSED:\$denom"
                                        \$totalDispensado += \$denom
                                        Start-Sleep -Milliseconds 300
                                    } catch {
                                        \$erroresDispensado += "Error dispensando \$denom: \$_"
                                        Write-Output "DEBUG:Error al dispensar \$denom - \$_"
                                    }
                                }
                            }
                            
                            # Escribir respuesta para que la API la lea
                            \$respuesta = @{
                                success = (\$erroresDispensado.Count -eq 0)
                                monto_solicitado = \$cmd.monto
                                total_dispensado = \$totalDispensado
                                errores = \$erroresDispensado
                                fecha = (Get-Date).ToString("yyyy-MM-dd HH:mm:ss")
                                timestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
                            } | ConvertTo-Json -Compress
                            Set-Content -Path \$dispenseResponseFile -Value \$respuesta
                            
                            Write-Output "DISPENSE_DONE:\$totalDispensado"
                        }
                    } catch {
                        Write-Output "DEBUG:Error procesando cola de dispensado: \$_"
                        if (Test-Path \$dispenseQueueFile) { Remove-Item \$dispenseQueueFile -Force }
                    }
                }
                
                Start-Sleep -Milliseconds 50
            } catch {
                Write-Output "DEBUG:Error en lectura: \$_"
                Start-Sleep -Seconds 1
            }
        }
    } catch {
        Write-Output "ERROR:\$_"
        \$reintentos++
        if (\$reintentos -lt \$maxReintentos) {
            Write-Output "DEBUG:Reintentando en 3 segundos... (intento \$reintentos)"
            Start-Sleep -Seconds 3
        }
    } finally {
        if (\$port -and \$port.IsOpen) {
            Write-Output "DEBUG:Cerrando puerto"
            \$port.Close()
            \$port.Dispose()
        }
    }
}

Write-Output "DEBUG:Listener terminado después de \$maxReintentos reintentos"
POWERSHELL;

    return $psScript;
}

/**
 * Función principal
 */
function main() {
    logMessage("========================================");
    logMessage("🚀 Iniciando listener de MONEDERO + BILLETERO");
    logMessage("📡 Puerto: " . PUERTO_SERIAL . " @ " . BAUDRATE . " baud");
    logMessage("💰 Saldo inicial: $" . getSaldo());
    logMessage("⚙️ Configuración anti-duplicados MEJORADA:");
    logMessage("   - Debounce: " . DEBOUNCE_TIEMPO . " segundos (solo para rebotes de hardware)");
    logMessage("   - 🪙 Monedas válidas: $" . implode(', $', MONEDAS_VALIDAS));
    logMessage("   - 💵 Billetes válidos: $" . implode(', $', BILLETES_VALIDOS));
    logMessage("   - 📦 Caché: " . MAX_REGISTROS_CACHE . " registros");
    logMessage("   - ✨ Permite múltiples monedas de la misma denominación");
    logMessage("========================================");
    
    // Crear script temporal en el mismo directorio del script (evita rutas con espacios o nombres 8.3 como VENDIN~1)
    $tempScript = __DIR__ . DIRECTORY_SEPARATOR . 'monedero_ps_listener.ps1';
    $psScript = escucharPuertoSerial();
    file_put_contents($tempScript, $psScript);
    
    // Verificar que se creó el archivo
    if (!file_exists($tempScript)) {
        logMessage("❌ ERROR: No se pudo crear el script temporal en: $tempScript");
        return;
    }
    
    // Comando PowerShell - SIN 2>&1 (tenemos pipe separado para stderr), con -NoProfile para inicio rápido
    $psCommand = sprintf(
        'powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File "%s"',
        str_replace('/', DIRECTORY_SEPARATOR, $tempScript)
    );
    
    logMessage("📝 Script creado: $tempScript");
    logMessage("⚡ Comando: $psCommand");
    logMessage("🔄 Iniciando lectura continua del puerto...");
    
    // Abrir proceso
    $descriptorspec = [
        0 => ["pipe", "r"],  // stdin
        1 => ["pipe", "w"],  // stdout
        2 => ["pipe", "w"]   // stderr
    ];
    
    $process = proc_open($psCommand, $descriptorspec, $pipes);
    
    if (!is_resource($process)) {
        logMessage("❌ ERROR: No se pudo iniciar el proceso de PowerShell");
        @unlink($tempScript);
        return;
    }
    
    // Configurar pipes como no bloqueantes
    stream_set_blocking($pipes[1], false);
    stream_set_blocking($pipes[2], false);
    
    $puertoAbierto = false;
    $ultimoHeartbeat = time();
    
    try {
        // Loop principal - leer salida del proceso
        while (true) {
            // Leer stdout
            $output = fgets($pipes[1]);
            
            if ($output !== false) {
                $output = trim($output);
                
                if (empty($output)) {
                    continue;
                }
                
                // Puerto abierto exitosamente
                if (strpos($output, 'PUERTO_ABIERTO:') === 0) {
                    $info = substr($output, 15);
                    logMessage("✅ Puerto serial abierto: $info");
                    $puertoAbierto = true;
                    $ultimoHeartbeat = time();
                    continue;
                }
                
                // Confirmacion de comando de activacion enviado
                if (strpos($output, 'ACTIVACION:') === 0) {
                    $info = substr($output, 11);
                    logMessage("⚡ ACTIVACION: $info");
                    $ultimoHeartbeat = time();
                    continue;
                }
                
                // Heartbeat - el listener sigue vivo
                if (strpos($output, 'HEARTBEAT:') === 0) {
                    $status = substr($output, 10);
                    logMessage("💓 Heartbeat: $status");
                    $ultimoHeartbeat = time();
                    continue;
                }
                
                // Datos recibidos del monedero/billetero
                if (strpos($output, 'DATA:') === 0) {
                    $data = substr($output, 5);
                    logMessage("📥 Dato recibido: $data");
                    
                    // Parsear el monto (ahora retorna array con valor y tipo)
                    $resultado = parsearRespuestaMonedero($data);
                    
                    if ($resultado !== null && isset($resultado['valor']) && $resultado['valor'] > 0) {
                        $monto = $resultado['valor'];
                        $tipo = $resultado['tipo'] ?? 'desconocido';
                        
                        logMessage("💰 Monto parseado: $$monto ($tipo) - Validando...");
                        
                        // agregarSaldo() valida duplicados y montos válidos
                        $agregado = agregarSaldo($monto, $tipo);
                        
                        if ($agregado) {
                            // Notificar al sistema solo si se agregó exitosamente
                            notificarNuevaMoneda($monto);
                        }
                    } else {
                        logMessage("⚠️ Dato ignorado o inválido: $data");
                    }
                    $ultimoHeartbeat = time();
                    continue;
                }
                
                // Debug info
                if (strpos($output, 'DEBUG:') === 0) {
                    $debug = substr($output, 6);
                    logMessage("🐛 DEBUG: $debug");
                    continue;
                }
                
                // Hex data (para debugging)
                if (strpos($output, 'HEX:') === 0) {
                    $hex = substr($output, 4);
                    logMessage("📦 HEX: $hex");
                    continue;
                }
                
                // Dispensado - moneda individual dispensada
                if (strpos($output, 'DISPENSED:') === 0) {
                    $denom = substr($output, 10);
                    logMessage("💸 DISPENSADO: moneda de $$denom");
                    continue;
                }
                
                // Dispensado iniciado
                if (strpos($output, 'DISPENSING:') === 0) {
                    $monto = substr($output, 11);
                    logMessage("📤 DISPENSANDO: $$monto");
                    continue;
                }
                
                // Dispensado completado
                if (strpos($output, 'DISPENSE_DONE:') === 0) {
                    $total = substr($output, 14);
                    logMessage("✅ DISPENSADO COMPLETO: $$total");
                    continue;
                }
                
                // Error
                if (strpos($output, 'ERROR:') === 0) {
                    $error = substr($output, 6);
                    logMessage("❌ ERROR: $error");
                    
                    if (!$puertoAbierto) {
                        logMessage("💡 Sugerencia: Verifica que el puerto " . PUERTO_SERIAL . " esté disponible");
                    }
                    
                    // El script de PowerShell reintentará automáticamente
                    logMessage("🔄 El listener reintentará reconectar automáticamente...");
                }
            }
            
            // Leer stderr (puede contener errores de parse de PowerShell)
            while (($error = fgets($pipes[2])) !== false) {
                $errorTrim = trim($error);
                if ($errorTrim !== '') {
                    logMessage("🔴 PS-STDERR: $errorTrim");
                }
            }
            
            // Verificar heartbeat - si pasaron más de 60 segundos sin señal, reiniciar
            if ($puertoAbierto && (time() - $ultimoHeartbeat) > 60) {
                logMessage("⚠️ No se recibe heartbeat hace 60 segundos - posible problema");
                logMessage("💡 El script PowerShell debería reconectar automáticamente");
                $ultimoHeartbeat = time(); // Reset para evitar spam
            }
            
            // 💰 NUEVO: Verificar si hay comandos de dispensar pendientes
            // Solo cuando el puerto está abierto y funcionando
            if ($puertoAbierto) {
                static $ultimaVerificacionDispensado = 0;
                $ahora = microtime(true);
                
                // Verificar cada 500ms (no en cada iteración para no saturar)
                if (($ahora - $ultimaVerificacionDispensado) >= DISPENSE_CHECK_INTERVAL) {
                    $ultimaVerificacionDispensado = $ahora;
                    
                    // Procesar comandos de dispensado
                    // NOTA: Esta función usa el mismo puerto COM5, pero lo abre/cierra
                    // rápidamente para no interferir con el listener principal
                    procesarComandosDispensado($process);
                }
            }
            
            // Pequeña pausa para no saturar CPU
            usleep(50000); // 50ms
            
            // Verificar si el proceso sigue vivo
            $status = proc_get_status($process);
            if (!$status['running']) {
                $exitCode = $status['exitcode'];
                logMessage("⚠️ El proceso PowerShell terminó - código: $exitCode");
                if ($exitCode === 1) {
                    logMessage("💡 Código 1 = error de PowerShell (parse error, acceso denegado, o script no encontrado)");
                    logMessage("💡 Script usado: $tempScript");
                }
                break; // Salir del loop interno para reiniciar
            }
        }
    } catch (Exception $e) {
        logMessage("❌ Excepción: " . $e->getMessage());
    } finally {
        // Cerrar pipes y proceso
        @fclose($pipes[0]);
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        @proc_terminate($process);
        @proc_close($process);
        
        // NO eliminar el script para poder inspeccionarlo si hay errores
        // @unlink($tempScript);
        logMessage("🔄 Reiniciando en 3 segundos...");
        sleep(3);
    }
}

/**
 * Notifica al sistema que se insertó una moneda
 */
function notificarNuevaMoneda($monto) {
    // Crear archivo de señal para el frontend
    $signalFile = __DIR__ . '/admin/dist/logs/nueva_moneda_signal.json';
    $signal = [
        'monto' => $monto,
        'timestamp' => microtime(true),
        'fecha' => date('Y-m-d H:i:s')
    ];
    file_put_contents($signalFile, json_encode($signal));
}

/**
 * 💰 NUEVO: Procesa comandos de dispensar cambio
 * Lee el archivo de cola y ejecuta el dispensado físicamente usando COM5
 */
function procesarComandosDispensado($puerto) {
    // Verificar si hay comandos pendientes
    if (!file_exists(DISPENSE_QUEUE_FILE)) {
        return false; // No hay comandos
    }
    
    try {
        $comando = json_decode(file_get_contents(DISPENSE_QUEUE_FILE), true);
        
        if (!$comando || !isset($comando['status']) || $comando['status'] !== 'PENDING') {
            return false; // Ya fue procesado
        }
        
        logMessage("💰 DISPENSAR: Comando recibido - Monto: $" . $comando['monto']);
        
        $monto = $comando['monto'];
        $desglose = $comando['desglose'] ?? [];
        
        if (empty($desglose)) {
            logMessage("❌ DISPENSAR: Error - desglose vacío");
            escribirRespuestaDispensado(false, "Desglose vacío", $comando);
            @unlink(DISPENSE_QUEUE_FILE);
            return false;
        }
        
        // Mapeo de denominaciones a código hexadecimal
        $hexMap = [
            1   => '1',
            2   => '2',
            5   => '5',
            10  => 'A',
            20  => '14',
            50  => '32',
            100 => '64',
            200 => 'C8',
            500 => 'FA'
        ];
        
        $comandosEnviados = [];
        $totalDispensado = 0;
        
        // Ejecutar dispensado para cada denominación
        foreach ($desglose as $denominacion => $cantidad) {
            $denomInt = (int)$denominacion;
            $cantInt = (int)$cantidad;
            
            if (!isset($hexMap[$denomInt])) {
                logMessage("⚠️ DISPENSAR: Denominación $denomInt no válida, saltando");
                continue;
            }
            
            $hex = $hexMap[$denomInt];
            
            // Dispensar cada moneda individualmente
            for ($i = 0; $i < $cantInt; $i++) {
                $comandoSerial = "INT000{$hex}003\r\n"; // 003 = comando de dispensar
                
                logMessage("📤 DISPENSAR: Enviando INT000{$hex}003 ($$denomInt moneda " . ($i + 1) . "/$cantInt)");
                
                // Enviar comando al puerto serial usando PowerShell
                $resultado = enviarComandoSerialPowerShell($comandoSerial);
                
                if ($resultado['success']) {
                    $comandosEnviados[] = [
                        'denominacion' => $denomInt,
                        'comando' => str_replace(["\r", "\n"], ['', ''], $comandoSerial),
                        'iteracion' => $i + 1,
                        'respuesta' => $resultado['output']
                    ];
                    
                    $totalDispensado += $denomInt;
                    logMessage("✅ DISPENSAR: Moneda $$denomInt dispensada OK");
                } else {
                    logMessage("❌ DISPENSAR: Error - " . $resultado['output']);
                }
                
                // Esperar pequeño delay entre monedas
                usleep(300000); // 300ms
            }
        }
        
        // Actualizar inventario
        if (!empty($comandosEnviados)) {
            actualizarInventarioDespuesDeDispensado($desglose);
        }
        
        // Escribir respuesta
        $success = ($totalDispensado == $monto);
        $mensaje = $success 
            ? "Cambio dispensado exitosamente: $$totalDispensado"
            : "Dispensado parcial: $$totalDispensado de $$monto";
        
        escribirRespuestaDispensado($success, $mensaje, $comando, $comandosEnviados, $totalDispensado);
        
        // Eliminar archivo de cola
        @unlink(DISPENSE_QUEUE_FILE);
        
        logMessage("✅ DISPENSAR: Completado - Total: $$totalDispensado");
        
        return true;
        
    } catch (Exception $e) {
        logMessage("❌ DISPENSAR: Error crítico - " . $e->getMessage());
        escribirRespuestaDispensado(false, "Error: " . $e->getMessage(), $comando ?? []);
        @unlink(DISPENSE_QUEUE_FILE);
        return false;
    }
}

/**
 * 💾 Actualiza el inventario de monedas después de dispensar
 */
function actualizarInventarioDespuesDeDispensado($desglose) {
    try {
        if (!file_exists(COIN_INVENTORY_FILE)) {
            logMessage("⚠️ INVENTARIO: Archivo no existe, no se puede actualizar");
            return;
        }
        
        $inventory = json_decode(file_get_contents(COIN_INVENTORY_FILE), true);
        
        foreach ($desglose as $denominacion => $cantidad) {
            $denomStr = (string)$denominacion;
            if (isset($inventory['denominaciones'][$denomStr])) {
                $inventory['denominaciones'][$denomStr] -= (int)$cantidad;
                
                // Asegurar que no sea negativo
                if ($inventory['denominaciones'][$denomStr] < 0) {
                    $inventory['denominaciones'][$denomStr] = 0;
                }
                
                // Registrar en log
                $inventory['log'][] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'tipo' => 'DISPENSADO',
                    'denominacion' => (int)$denominacion,
                    'cantidad' => (int)$cantidad,
                    'total_denominacion_despues' => $inventory['denominaciones'][$denomStr]
                ];
            }
        }
        
        // Recalcular total
        $total = 0;
        foreach ($inventory['denominaciones'] as $denom => $cantidad) {
            $total += ((int)$denom * (int)$cantidad);
        }
        $inventory['total_pesos'] = $total;
        $inventory['ultima_actualizacion'] = date('Y-m-d H:i:s');
        
        // Mantener solo últimas 100 entradas del log
        if (count($inventory['log']) > 100) {
            $inventory['log'] = array_slice($inventory['log'], -100);
        }
        
        file_put_contents(COIN_INVENTORY_FILE, json_encode($inventory, JSON_PRETTY_PRINT));
        
        logMessage("💾 INVENTARIO: Actualizado después de dispensar - Total: $" . $inventory['total_pesos']);
        
    } catch (Exception $e) {
        logMessage("❌ INVENTARIO: Error al actualizar - " . $e->getMessage());
    }
}

/**
 * 📝 Escribe la respuesta del dispensado para que la API la lea
 */
function escribirRespuestaDispensado($success, $mensaje, $comandoOriginal, $comandosEnviados = [], $totalDispensado = 0) {
    $respuesta = [
        'success' => $success,
        'mensaje' => $mensaje,
        'timestamp' => microtime(true),
        'fecha' => date('Y-m-d H:i:s'),
        'monto_solicitado' => $comandoOriginal['monto'] ?? 0,
        'total_dispensado' => $totalDispensado,
        'comandos_enviados' => $comandosEnviados,
        'desglose_original' => $comandoOriginal['desglose'] ?? []
    ];
    
    file_put_contents(DISPENSE_RESPONSE_FILE, json_encode($respuesta, JSON_PRETTY_PRINT));
    
    logMessage("📝 RESPUESTA: Escrita en " . DISPENSE_RESPONSE_FILE);
}

/**
 * 📡 Envía un comando al puerto serial usando PowerShell
 */
function enviarComandoSerialPowerShell($comando) {
    $puerto = PUERTO_SERIAL;
    $baudrate = BAUDRATE;
    
    // Escapar el comando para PowerShell
    $comandoEscaped = addslashes($comando);
    
    // Script PowerShell para enviar comando
    $psScript = <<<POWERSHELL
try {
    \$port = New-Object System.IO.Ports.SerialPort
    \$port.PortName = "$puerto"
    \$port.BaudRate = $baudrate
    \$port.DataBits = 8
    \$port.Parity = [System.IO.Ports.Parity]::None
    \$port.StopBits = [System.IO.Ports.StopBits]::One
    \$port.ReadTimeout = 1000
    \$port.WriteTimeout = 1000
    
    \$port.Open()
    \$port.Write("$comandoEscaped")
    Start-Sleep -Milliseconds 100
    \$port.Close()
    
    Write-Output "SUCCESS"
} catch {
    Write-Error "Error: \$_"
}
POWERSHELL;
    
    // Ejecutar PowerShell
    $tempScript = tempnam(sys_get_temp_dir(), 'hw_dispense_') . '.ps1';
    file_put_contents($tempScript, $psScript);
    
    $output = shell_exec("powershell.exe -NoProfile -ExecutionPolicy Bypass -File \"$tempScript\" 2>&1");
    @unlink($tempScript);
    
    $success = (stripos($output, 'SUCCESS') !== false);
    
    return [
        'success' => $success,
        'output' => $output ?? ''
    ];
}

// Manejo de señales para detener el script limpiamente
if (function_exists('pcntl_signal')) {
    declare(ticks = 1);
    pcntl_signal(SIGTERM, function() {
        logMessage("📢 Señal SIGTERM recibida, deteniendo...");
        exit(0);
    });
    pcntl_signal(SIGINT, function() {
        logMessage("📢 Señal SIGINT recibida, deteniendo...");
        exit(0);
    });
}

// Ejecutar con restart loop - si main() termina, reiniciar automaticamente
$intentosReinicio = 0;
while (true) {
    logMessage("▶️ Iniciando ciclo del listener (intento: " . ($intentosReinicio + 1) . ")");
    main();
    $intentosReinicio++;
    logMessage("⚠️ Listener terminó - reintentando en 5 segundos (reinicios: $intentosReinicio)");
    sleep(5);
}
