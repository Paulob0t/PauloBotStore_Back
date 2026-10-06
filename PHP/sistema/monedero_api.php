<?php
/**
 * API para interactuar con el monedero
 * Endpoints para obtener saldo, resetear, etc.
 * 
 * Fecha: 21-01-2026
 */

// Prevenir cualquier salida antes de los headers
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

define('SALDO_FILE', __DIR__ . '/admin/dist/logs/saldo_actual.json');
define('INSERT_MODE_FILE', __DIR__ . '/admin/dist/logs/coin_insert_mode.json');
define('SIGNAL_FILE', __DIR__ . '/admin/dist/logs/nueva_moneda_signal.json');
define('COIN_INVENTORY_FILE', __DIR__ . '/admin/dist/logs/coin_inventory.log');

// PROTOCOLO DEL HARDWARE - Comandos específicos
define('CMD_RESET', "INT0000000\r\n");
define('CMD_HABILITAR', "INT0000001\r\n");
define('CMD_DESHABILITAR', "INT0000002\r\n");
define('CMD_CAMBIO', "INT000A003\r\n");  // A = 10 pesos (ajustar según denominación)
define('CMD_PAX_COBRO', "INT000A006\r\n");
define('CMD_PAX_CANCELAR', "INT0000007\r\n");
define('CMD_PAX_VENTA_OK', "INT0000009\r\n");
define('CMD_PAX_VENTA_ERROR', "INT000000:\r\n");

// Mapeo de denominaciones a valores hexadecimales
// Formato: INT000[HEX]00Z\r\n donde HEX es el valor en hexadecimal
$DENOMINACION_HEX = [
    1   => '1',   // 1 peso = 0x1
    2   => '2',   // 2 pesos = 0x2
    5   => '5',   // 5 pesos = 0x5
    10  => 'A',   // 10 pesos = 0xA
    20  => '14',  // 20 pesos = 0x14
    50  => '32',  // 50 pesos = 0x32
    100 => '64',  // 100 pesos = 0x64
    200 => 'C8',  // 200 pesos = 0xC8
    500 => '1F4', // 500 pesos = 0x1F4
];

// Crear directorio de logs si no existe
$logsDir = __DIR__ . '/admin/dist/logs';
if (!file_exists($logsDir)) {
    @mkdir($logsDir, 0755, true);
}

define('TUBE_CONFIG_FILE', __DIR__ . '/admin/dist/logs/tube_config.json');
define('HARDWARE_DENOMS', [1, 2, 5, 10]);

/** Hex de denominación para dispensar (protocolo INT000[HEX]003, NO número de tubo) */
define('DISPENSE_HEX_MAP', [
    1   => '1',
    2   => '2',
    5   => '5',
    10  => 'A',
    20  => '14',
    50  => '32',
    100 => '64',
]);

function getDispenseHexForDenom($denom) {
    $denom = (int)$denom;
    if (isset(DISPENSE_HEX_MAP[$denom])) {
        return DISPENSE_HEX_MAP[$denom];
    }
    return strtoupper(dechex($denom));
}

function buildDispenseCommand($denom) {
    return 'INT000' . getDispenseHexForDenom($denom) . '003';
}

/**
 * Configuración física de tubos: A=$1, B=$1, C=$10, D=$2, E=$5
 * cmd_hex = hex de DENOMINACIÓN para dispensar (ej. $10 → A, no tubo 3)
 */
function getTubeConfig() {
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    if (file_exists(TUBE_CONFIG_FILE)) {
        $data = json_decode(file_get_contents(TUBE_CONFIG_FILE), true);
        if (!empty($data['tubos'])) {
            $config = $data;
            return $config;
        }
    }
    $config = [
        'tubos' => [
            'A' => ['denominacion' => 1,  'cmd_hex' => '1', 'etiqueta' => '$1'],
            'B' => ['denominacion' => 1,  'cmd_hex' => '1', 'etiqueta' => '$1'],
            'C' => ['denominacion' => 10, 'cmd_hex' => 'A', 'etiqueta' => '$10'],
            'D' => ['denominacion' => 2,  'cmd_hex' => '2', 'etiqueta' => '$2'],
            'E' => ['denominacion' => 5,  'cmd_hex' => '5', 'etiqueta' => '$5'],
        ]
    ];
    return $config;
}

function initEmptyTubos() {
    $tubos = [];
    foreach (array_keys(getTubeConfig()['tubos']) as $id) {
        $tubos[$id] = ['cantidad' => 0];
    }
    return $tubos;
}

function syncDenominacionesFromTubos(&$inventory) {
    $config = getTubeConfig();
    $denoms = [];
    foreach (HARDWARE_DENOMS as $d) {
        $denoms[(string)$d] = 0;
    }
    if (!empty($inventory['tubos'])) {
        foreach ($inventory['tubos'] as $tubeId => $tubeData) {
            $denom = (int)($config['tubos'][$tubeId]['denominacion'] ?? 0);
            if ($denom > 0) {
                $denoms[(string)$denom] = ($denoms[(string)$denom] ?? 0) + (int)($tubeData['cantidad'] ?? 0);
            }
        }
    }
    $inventory['denominaciones'] = $denoms;
    $total = 0;
    foreach ($denoms as $denom => $cantidad) {
        $total += (int)$denom * (int)$cantidad;
    }
    $inventory['total_pesos'] = $total;
}

function ensureTubeInventory(&$inventory) {
    if (empty($inventory['tubos'])) {
        $inventory['tubos'] = initEmptyTubos();
        if (!empty($inventory['denominaciones'])) {
            foreach (['10' => 'C', '5' => 'E', '2' => 'D'] as $denom => $tubeId) {
                $cant = (int)($inventory['denominaciones'][$denom] ?? 0);
                if ($cant > 0) {
                    $inventory['tubos'][$tubeId]['cantidad'] = $cant;
                }
            }
            $unos = (int)($inventory['denominaciones']['1'] ?? 0);
            if ($unos > 0) {
                $inventory['tubos']['A']['cantidad'] = (int)ceil($unos / 2);
                $inventory['tubos']['B']['cantidad'] = $unos - $inventory['tubos']['A']['cantidad'];
            }
        }
    }
    syncDenominacionesFromTubos($inventory);
}

function pickTubeForDenom($denom, $tubos, $preferMore = true) {
    $config = getTubeConfig();
    $candidates = [];
    foreach ($config['tubos'] as $tubeId => $info) {
        if ((int)$info['denominacion'] !== (int)$denom) {
            continue;
        }
        $stock = (int)($tubos[$tubeId]['cantidad'] ?? 0);
        if ($stock > 0) {
            $candidates[] = ['id' => $tubeId, 'stock' => $stock];
        }
    }
    if (empty($candidates)) {
        return null;
    }
    usort($candidates, function ($a, $b) use ($preferMore) {
        return $preferMore ? ($b['stock'] - $a['stock']) : ($a['stock'] - $b['stock']);
    });
    return $candidates[0]['id'];
}

function expandDesgloseToTubeCommands($desglose, &$inventory) {
    ensureTubeInventory($inventory);
    $config = getTubeConfig();
    $commands = [];
    $plan = [];

    foreach ($desglose as $denomStr => $cantidad) {
        $denom = (int)$denomStr;
        $cantidad = (int)$cantidad;
        for ($i = 0; $i < $cantidad; $i++) {
            $tubeId = pickTubeForDenom($denom, $inventory['tubos'], true);
            if (!$tubeId) {
                return [
                    'ok' => false,
                    'mensaje' => "Sin monedas de $$denom en los tubos físicos"
                ];
            }
            $hex = getDispenseHexForDenom($denom);
            $commands[] = $hex;
            $plan[] = [
                'tubo' => $tubeId,
                'denominacion' => $denom,
                'cmd_hex' => $hex,
                'comando' => buildDispenseCommand($denom),
            ];
            $inventory['tubos'][$tubeId]['cantidad']--;
        }
    }

    syncDenominacionesFromTubos($inventory);
    return ['ok' => true, 'commands' => $commands, 'plan' => $plan];
}

function registerCoinInTubes(&$inventory, $denominacion) {
    ensureTubeInventory($inventory);
    $tubeId = pickTubeForDenom($denominacion, $inventory['tubos'], false);
    if (!$tubeId) {
        $config = getTubeConfig();
        foreach ($config['tubos'] as $id => $info) {
            if ((int)$info['denominacion'] === (int)$denominacion) {
                $tubeId = $id;
                break;
            }
        }
    }
    if ($tubeId) {
        $inventory['tubos'][$tubeId]['cantidad'] = (int)($inventory['tubos'][$tubeId]['cantidad'] ?? 0) + 1;
    }
    syncDenominacionesFromTubos($inventory);
    return $tubeId;
}

/**
 * Convierte una denominación en pesos a su valor hexadecimal
 */
function denominacionToHex($pesos) {
    return strtoupper(dechex($pesos));
}

/**
 * Convierte un valor hexadecimal a pesos
 */
function hexToDenominacion($hex) {
    return hexdec($hex);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 🔍 DEBUG: Registrar TODO antes de ejecutar
error_log("============================================");
error_log("🔍 monedero_api.php INICIO");
error_log("Action recibida: " . $action);
error_log("GET: " . json_encode($_GET));
error_log("POST: " . json_encode($_POST));
error_log("============================================");

// Si no hay action, reportar error inmediato
if (empty($action)) {
    echo json_encode([
        'error' => true,
        'mensaje' => 'No se especificó ninguna acción',
        'debug' => [
            'GET' => $_GET,
            'POST' => $_POST
        ]
    ]);
    exit;
}

try {
    switch ($action) {
        case 'get_saldo':
            getSaldo();
            break;
        
        case 'reset_saldo':
            resetSaldo();
            break;
        
        case 'check_nueva_moneda':
            checkNuevaMoneda();
            break;
        
        case 'set_saldo':
            setSaldo();
            break;
        
        case 'dispensar_cambio':
            error_log("🎯 Ejecutando dispensarCambio()...");
            dispensarCambio();
            error_log("✅ dispensarCambio() completado");
            break;
        
        case 'hardware_reset':
            hardwareReset();
            break;
        
        case 'hardware_habilitar':
            hardwareHabilitar();
            break;
        
        case 'hardware_deshabilitar':
            hardwareDeshabilitar();
            break;
        
        case 'check_change_availability':
            checkChangeAvailability();
            break;
        
        case 'register_coin_received':
            registerCoinReceived();
            break;
        
        case 'get_coin_inventory':
            getCoinInventory();
            break;
        
        case 'set_inventory':
            setInventory();
            break;

        case 'get_insert_mode':
            getInsertMode();
            break;

        case 'set_insert_mode':
            setInsertMode();
            break;

        case 'registrar_pago_efectivo':
            registrarPagoEfectivo();
            break;

        case 'enviar_comando':
            enviarComandoRaw();
            break;
        
        default:
            echo json_encode([
                'error' => true,
                'mensaje' => 'Acción no válida',
                'acciones_disponibles' => [
                    'get_saldo', 'reset_saldo', 'check_nueva_moneda', 'set_saldo', 
                    'dispensar_cambio', 'hardware_reset', 'hardware_habilitar', 'hardware_deshabilitar',
                    'check_change_availability', 'register_coin_received', 'get_coin_inventory'
                ]
            ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'error' => true,
        'mensaje' => 'Error en el servidor: ' . $e->getMessage()
    ]);
}

function readInsertMode() {
    if (file_exists(INSERT_MODE_FILE)) {
        $data = json_decode(file_get_contents(INSERT_MODE_FILE), true);
        if (is_array($data)) {
            return $data;
        }
    }
    return [
        'activo_carga_maquina' => false,
        'modo' => 'cliente',
        'fecha' => date('Y-m-d H:i:s'),
    ];
}

function getInsertMode() {
    $mode = readInsertMode();
    echo json_encode([
        'success' => true,
        'activo_carga_maquina' => (bool)($mode['activo_carga_maquina'] ?? false),
        'modo' => ($mode['activo_carga_maquina'] ?? false) ? 'maquina' : 'cliente',
        'fecha' => $mode['fecha'] ?? null,
    ]);
}

function setInsertMode() {
    $activo = $_POST['activo_carga_maquina'] ?? $_GET['activo_carga_maquina'] ?? null;
    if ($activo === null) {
        echo json_encode(['error' => true, 'mensaje' => 'Falta activo_carga_maquina (0 o 1)']);
        return;
    }
    $activoBool = filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($activoBool === null) {
        $activoBool = ((string)$activo === '1');
    }
    $mode = [
        'activo_carga_maquina' => $activoBool,
        'modo' => $activoBool ? 'maquina' : 'cliente',
        'fecha' => date('Y-m-d H:i:s'),
        'actualizado_por' => 'monedero_api',
    ];
    file_put_contents(INSERT_MODE_FILE, json_encode($mode, JSON_PRETTY_PRINT));
    echo json_encode([
        'success' => true,
        'mensaje' => $activoBool
            ? 'Modo CARGA MAQUINA activo — monedas solo al inventario'
            : 'Modo CLIENTE activo — monedas al saldo del cliente',
        'activo_carga_maquina' => $activoBool,
        'modo' => $mode['modo'],
    ]);
}

/**
 * Saldo del cliente: dinero insertado disponible para comprar
 */
function readSaldoCliente() {
    if (!file_exists(SALDO_FILE)) {
        return 0.0;
    }
    $data = json_decode(file_get_contents(SALDO_FILE), true);
    return (float)($data['saldo'] ?? 0);
}

/**
 * Saldo de la máquina: inventario físico de monedas para dar cambio
 */
function readSaldoMaquina() {
    $inventory = loadCoinInventory();
    return (float)($inventory['float_operador'] ?? 0);
}

/**
 * Ajusta el float del operador (saldo máquina para cambio)
 */
function adjustFloatOperador($delta) {
    $inventory = loadCoinInventory();
    $float = (int)($inventory['float_operador'] ?? 0);
    $inventory['float_operador'] = max(0, $float + (int)round($delta));
    $inventory['ultima_actualizacion'] = date('Y-m-d H:i:s');
    saveCoinInventory($inventory);
    return (int)$inventory['float_operador'];
}

/**
 * Al cobrar en efectivo: el dinero del cliente pasa al saldo máquina.
 * El cambio se descuenta después en dispensar_cambio.
 */
function registrarPagoEfectivo() {
    $montoPagado = (float)($_POST['monto_pagado'] ?? 0);
    $cambio = (float)($_POST['cambio'] ?? 0);

    if ($montoPagado <= 0) {
        echo json_encode(['error' => true, 'mensaje' => 'Monto pagado inválido']);
        return;
    }

    $floatNuevo = adjustFloatOperador($montoPagado);

    error_log("COBRO EFECTIVO: +$$montoPagado al float maquina -> $$floatNuevo (cambio pendiente: $$cambio)");

    echo json_encode([
        'success' => true,
        'mensaje' => 'Cobro registrado en saldo máquina',
        'monto_pagado' => $montoPagado,
        'cambio_pendiente' => $cambio,
        'float_operador' => $floatNuevo,
        'saldo_maquina' => $floatNuevo,
    ]);
}

function readInventarioFisicoTotal() {
    $inventory = loadCoinInventory();
    return (float)($inventory['total_pesos'] ?? 0);
}

/**
 * Valida que el cambio se pueda dispensar SOLO con monedas del inventario (no billetes)
 */
function validarCambioDispensableEnMonedas($monto) {
    $monto = (float)$monto;
    if ($monto <= 0) {
        return [
            'available' => true,
            'posible' => true,
            'mensaje' => 'No se requiere cambio',
            'desglose' => [],
            'faltante' => 0,
            'cambio_solo_monedas' => true,
        ];
    }

    $inventory = loadCoinInventory();
    $denominaciones = $inventory['denominaciones'];
    $resultado = calcularMejorDistribucionCambio($monto, $denominaciones);

    if (!$resultado['posible']) {
        $faltante = $resultado['faltante'] ?? $monto;
        return array_merge($resultado, [
            'available' => false,
            'cambio_solo_monedas' => true,
            'mensaje' => 'No hay suficientes monedas para dar $' . number_format($monto, 2) . ' de cambio. '
                . 'El cambio se entrega únicamente en monedas (no billetes). Falta: $' . number_format($faltante, 2),
            'faltante' => $faltante,
        ]);
    }

    return array_merge($resultado, [
        'available' => true,
        'cambio_solo_monedas' => true,
        'mensaje' => 'Cambio disponible en monedas',
    ]);
}

/**
 * Obtiene saldo del cliente y de la máquina (inventario físico)
 */
function getSaldo() {
    $saldoCliente = readSaldoCliente();
    $saldoMaquina = readSaldoMaquina();
    $meta = [];

    if (file_exists(SALDO_FILE)) {
        $data = json_decode(file_get_contents(SALDO_FILE), true);
        $meta['timestamp'] = $data['timestamp'] ?? null;
        $meta['fecha'] = $data['fecha'] ?? null;
        $meta['saldo_monedas'] = (float)($data['saldo_monedas'] ?? $saldoCliente);
        $meta['saldo_billetes'] = (float)($data['saldo_billetes'] ?? 0);
    }

    $insertMode = readInsertMode();
    echo json_encode(array_merge([
        'success' => true,
        'saldo_cliente' => $saldoCliente,
        'saldo_maquina' => $saldoMaquina,
        'inventario_fisico_total' => readInventarioFisicoTotal(),
        'saldo' => $saldoCliente,
        'saldo_monedas' => (float)($meta['saldo_monedas'] ?? $saldoCliente),
        'saldo_billetes' => (float)($meta['saldo_billetes'] ?? 0),
        'modo_insercion' => ($insertMode['activo_carga_maquina'] ?? false) ? 'maquina' : 'cliente',
        'carga_maquina_activa' => (bool)($insertMode['activo_carga_maquina'] ?? false),
        'cambio_solo_monedas' => true,
    ], $meta));
}

/**
 * Resetea el saldo a 0
 */
function resetSaldo() {
    $data = [
        'saldo' => 0,
        'saldo_monedas' => 0,
        'saldo_billetes' => 0,
        'timestamp' => time(),
        'fecha' => date('Y-m-d H:i:s')
    ];
    file_put_contents(SALDO_FILE, json_encode($data, JSON_PRETTY_PRINT));
    
    // También eliminar señal de nueva moneda
    if (file_exists(SIGNAL_FILE)) {
        @unlink(SIGNAL_FILE);
    }
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Saldo reseteado a $0',
        'saldo' => 0
    ]);
}

/**
 * Verifica si hay una nueva moneda insertada
 * Retorna los datos y elimina la señal
 */
function checkNuevaMoneda() {
    if (file_exists(SIGNAL_FILE)) {
        $signal = json_decode(file_get_contents(SIGNAL_FILE), true);
        
        // Eliminar archivo de señal después de leerlo
        @unlink(SIGNAL_FILE);
        
        echo json_encode([
            'success' => true,
            'nueva_moneda' => true,
            'monto' => (float)$signal['monto'],
            'timestamp' => $signal['timestamp'],
            'fecha' => $signal['fecha']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'nueva_moneda' => false
        ]);
    }
}

/**
 * Establece un saldo manualmente (para testing)
 */
function setSaldo() {
    $saldo = $_POST['saldo'] ?? 0;
    $saldo = (float)$saldo;
    
    if ($saldo < 0) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'El saldo no puede ser negativo'
        ]);
        return;
    }
    
    $data = [
        'saldo' => $saldo,
        'timestamp' => time(),
        'fecha' => date('Y-m-d H:i:s')
    ];
    file_put_contents(SALDO_FILE, json_encode($data, JSON_PRETTY_PRINT));
    
    echo json_encode([
        'success' => true,
        'mensaje' => "Saldo establecido a $$saldo",
        'saldo' => $saldo
    ]);
}

/**
 * 🧠 ALGORITMO INTELIGENTE: Calcula la mejor distribución de cambio
 * Usa programación dinámica para encontrar la combinación óptima
 * Prioriza usar menos monedas pero asegura que sea posible
 * 
 * @param float $monto - Monto de cambio a dispensar
 * @param array $inventario - Array asociativo [denominacion => cantidad_disponible]
 * @return array - ['posible' => bool, 'desglose' => array, 'mensaje' => string, 'faltante' => float]
 */
function calcularMejorDistribucionCambio($monto, $inventario) {
    // Convertir a centavos para evitar problemas de punto flotante
    $montoCentavos = (int)round($monto * 100);
    
    // Preparar denominaciones disponibles (de menor a mayor para DP)
    $denomsDisponibles = [];
    foreach (HARDWARE_DENOMS as $denom) {
        $key = (string)$denom;
        $cantidad = (int)($inventario[$denom] ?? $inventario[$key] ?? 0);
        if ($cantidad > 0) {
            $denomsDisponibles[$denom] = $cantidad;
        }
    }
    
    if (empty($denomsDisponibles)) {
        return [
            'posible' => false,
            'mensaje' => 'No hay monedas disponibles en el inventario',
            'faltante' => $monto,
            'desglose' => []
        ];
    }
    
    // � NUEVO: GENERAR MÚLTIPLES SOLUCIONES Y ELEGIR AL AZAR
    // Esto da variedad en las combinaciones de cambio
    $denoms = array_keys($denomsDisponibles);
    rsort($denoms); // Mayor a menor
    
    $desglose = [];
    $restante = $montoCentavos;
    
    foreach ($denoms as $denom) {
        $denomCentavos = $denom * 100;
        $cantidadDisponible = $denomsDisponibles[$denom];
        
        if ($restante >= $denomCentavos && $cantidadDisponible > 0) {
            $cantidadNecesaria = (int)floor($restante / $denomCentavos);
            $cantidadUsar = min($cantidadNecesaria, $cantidadDisponible);
            
            if ($cantidadUsar > 0) {
                $desglose[$denom] = $cantidadUsar;
                $restante -= ($denomCentavos * $cantidadUsar);
            }
        }
    }
    
    if ($restante == 0) {
        error_log("Cambio calculado (greedy): " . json_encode($desglose));
        return [
            'posible' => true,
            'desglose' => $desglose,
            'mensaje' => 'Cambio disponible',
            'total_monedas' => array_sum($desglose),
            'faltante' => 0
        ];
    }

    $faltantePesos = $restante / 100;
    return [
        'posible' => false,
        'mensaje' => "Cambio insuficiente. Falta: $" . number_format($faltantePesos, 2),
        'faltante' => $faltantePesos,
        'desglose' => $desglose
    ];
}

/**
 * 🎲 Genera múltiples soluciones válidas con variedad
 * Encuentra diferentes combinaciones de monedas para dar cambio
 * y retorna las mejores opciones para elegir aleatoriamente
 */
function generarSolucionesVariadas($montoCentavos, $denomsDisponibles) {
    $soluciones = [];
    $denoms = array_keys($denomsDisponibles);
    rsort($denoms); // Mayor a menor
    
    // 🔄 Función recursiva para generar combinaciones
    $generarCombinaciones = function($restante, $desglose, $indiceDenom, $profundidad = 0) 
        use (&$generarCombinaciones, &$soluciones, $denoms, $denomsDisponibles, $montoCentavos) {
        
        // Límite de profundidad para evitar explosión combinatoria
        if ($profundidad > 50) return;
        
        // ✅ Solución encontrada
        if ($restante == 0) {
            $soluciones[] = $desglose;
            return;
        }
        
        // ❌ No válido
        if ($restante < 0 || $indiceDenom >= count($denoms)) {
            return;
        }
        
        // 🎯 Poda: Limitar a 10 soluciones para mantener performance
        if (count($soluciones) >= 10) {
            return;
        }
        
        $denom = $denoms[$indiceDenom];
        $denomCentavos = $denom * 100;
        $cantidadMax = $denomsDisponibles[$denom];
        
        // Calcular máximo posible de esta denominación
        $cantidadMaxPosible = min($cantidadMax, (int)floor($restante / $denomCentavos));
        
        // 🎲 CLAVE: Probar DIFERENTES cantidades (no solo el máximo)
        // Esto genera variedad en las soluciones
        
        // 1️⃣ Intentar con cantidad máxima (greedy clásico)
        if ($cantidadMaxPosible > 0) {
            $nuevoDesglose = $desglose;
            $nuevoDesglose[$denom] = $cantidadMaxPosible;
            $generarCombinaciones(
                $restante - ($denomCentavos * $cantidadMaxPosible),
                $nuevoDesglose,
                $indiceDenom + 1,
                $profundidad + 1
            );
        }
        
        // 2️⃣ Intentar con 75% de la cantidad máxima
        $cantidad75 = (int)ceil($cantidadMaxPosible * 0.75);
        if ($cantidad75 > 0 && $cantidad75 != $cantidadMaxPosible) {
            $nuevoDesglose = $desglose;
            $nuevoDesglose[$denom] = $cantidad75;
            $generarCombinaciones(
                $restante - ($denomCentavos * $cantidad75),
                $nuevoDesglose,
                $indiceDenom + 1,
                $profundidad + 1
            );
        }
        
        // 3️⃣ Intentar con 50% de la cantidad máxima
        $cantidad50 = (int)ceil($cantidadMaxPosible * 0.5);
        if ($cantidad50 > 0 && $cantidad50 != $cantidadMaxPosible && $cantidad50 != $cantidad75) {
            $nuevoDesglose = $desglose;
            $nuevoDesglose[$denom] = $cantidad50;
            $generarCombinaciones(
                $restante - ($denomCentavos * $cantidad50),
                $nuevoDesglose,
                $indiceDenom + 1,
                $profundidad + 1
            );
        }
        
        // 4️⃣ Intentar SIN usar esta denominación (fuerza otras combinaciones)
        $generarCombinaciones(
            $restante,
            $desglose,
            $indiceDenom + 1,
            $profundidad + 1
        );
    };
    
    // 🚀 Generar soluciones
    $generarCombinaciones($montoCentavos, [], 0);
    
    // 🎯 Filtrar solo las "mejores" soluciones (menos monedas)
    if (!empty($soluciones)) {
        // Calcular total de monedas por solución
        $solucionesConTotal = array_map(function($sol) {
            return ['desglose' => $sol, 'total' => array_sum($sol)];
        }, $soluciones);
        
        // Ordenar por total de monedas
        usort($solucionesConTotal, function($a, $b) {
            return $a['total'] - $b['total'];
        });
        
        // Tomar las mejores (las que usan menos monedas + margen del 50%)
        $mejorTotal = $solucionesConTotal[0]['total'];
        $umbral = $mejorTotal + ceil($mejorTotal * 0.5); // Acepta hasta 50% más monedas
        
        $mejoresSoluciones = array_filter($solucionesConTotal, function($sol) use ($umbral) {
            return $sol['total'] <= $umbral;
        });
        
        // Retornar solo los desgloses
        return array_map(function($sol) {
            return $sol['desglose'];
        }, $mejoresSoluciones);
    }
    
    return [];
}

/**
 * Dispensa cambio físicamente desde el monedero
 * Escribe a la cola del listener, que ya tiene COM5 abierto
 */
function dispensarCambio() {
    error_log("🎯 dispensarCambio() INICIO");
    
    if (ob_get_level()) {
        ob_clean();
    }
    
    $monto = $_POST['monto'] ?? 0;
    $monto = (float)$monto;
    
    error_log("💰 Monto a dispensar: $$monto");
    
    if ($monto <= 0) {
        echo json_encode(['error' => true, 'mensaje' => 'El monto debe ser mayor a 0']);
        return;
    }
    
    // Rutas de cola (mismas que usa monedero_listener.php)
    $dispenseQueueFile  = __DIR__ . '/admin/dist/logs/monedero_dispense_queue.json';
    $dispenseResponseFile = __DIR__ . '/admin/dist/logs/monedero_dispense_response.json';
    
    // Verificar que el listener está corriendo (existe su log y fue actualizado recientemente)
    $listenerLog = __DIR__ . '/admin/dist/logs/monedero_listener.log';
    if (!file_exists($listenerLog) || (time() - filemtime($listenerLog)) > 120) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'El listener de monedas no está activo. Inicia MonederoMonitor.exe primero.'
        ]);
        return;
    }
    
    // Validar: solo se puede dispensar cambio en monedas (no billetes)
    $validacion = validarCambioDispensableEnMonedas($monto);
    if (!$validacion['available']) {
        echo json_encode([
            'error' => true,
            'mensaje' => $validacion['mensaje'],
            'monto_solicitado' => $monto,
            'faltante' => $validacion['faltante'] ?? $monto,
            'cambio_solo_monedas' => true,
            'inventario_actual' => loadCoinInventory()['denominaciones'] ?? []
        ]);
        return;
    }

    // Calcular desglose óptimo
    $inventory = loadCoinInventory();
    $inventarioDenoms = $inventory['denominaciones'];
    $resultado = $validacion;
    $desglose = $resultado['desglose'];
    error_log("📊 Desglose: " . json_encode($desglose));

    $inventoryDraft = $inventory;
    $tubeExpansion = expandDesgloseToTubeCommands($desglose, $inventoryDraft);
    if (!$tubeExpansion['ok']) {
        echo json_encode([
            'error' => true,
            'mensaje' => $tubeExpansion['mensaje'],
            'monto_solicitado' => $monto,
            'desglose' => $desglose,
            'inventario_actual' => $inventarioDenoms
        ]);
        return;
    }
    error_log("🧪 Plan tubos: " . json_encode($tubeExpansion['plan']));
    
    // Limpiar respuesta anterior
    if (file_exists($dispenseResponseFile)) {
        @unlink($dispenseResponseFile);
    }
    
    // Escribir comando a la cola del listener
    $comando = [
        'status'     => 'PENDING',
        'monto'      => $monto,
        'desglose'   => $desglose,
        'tubos_cmd'  => $tubeExpansion['commands'],
        'tubos_plan' => $tubeExpansion['plan'],
        'timestamp'  => microtime(true),
        'fecha'      => date('Y-m-d H:i:s')
    ];
    file_put_contents($dispenseQueueFile, json_encode($comando, JSON_PRETTY_PRINT));
    error_log("📤 Comando escrito en cola");
    
    // Esperar respuesta del listener (máx 60 segundos — varias monedas con pausa mecánica)
    $timeout = time() + 60;
    $respuesta = null;
    
    while (time() < $timeout) {
        if (file_exists($dispenseResponseFile)) {
            $respuesta = json_decode(file_get_contents($dispenseResponseFile), true);
            if ($respuesta) break;
        }
        usleep(200000); // 200ms
    }
    
    if (!$respuesta) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'Timeout: el listener no respondió en 30 segundos. Verifica que MonederoMonitor esté corriendo.'
        ]);
        return;
    }
    
    $totalReal = (int)($respuesta['total_dispensado'] ?? 0);

    if ($respuesta['success']) {
        $inventory = $inventoryDraft;
        $floatPrev = (int)($inventory['float_operador'] ?? 0);
        $inventory['float_operador'] = max(0, $floatPrev - $totalReal);
        $inventory['ultima_actualizacion'] = date('Y-m-d H:i:s');
        saveCoinInventory($inventory);
        $inventarioDenoms = $inventory['denominaciones'];
        
        echo json_encode([
            'success' => true,
            'mensaje' => "Cambio de $$monto dispensado correctamente",
            'monto' => $monto,
            'desglose' => $desglose,
            'total_dispensado' => $totalReal,
            'inventario_actualizado' => $inventarioDenoms,
            'tubos_plan' => $tubeExpansion['plan']
        ]);
        error_log("✅ dispensarCambio() EXITOSO: $" . $totalReal);
    } elseif ($totalReal > 0) {
        $inventory = $inventoryDraft;
        $floatPrev = (int)($inventory['float_operador'] ?? 0);
        $inventory['float_operador'] = max(0, $floatPrev - $totalReal);
        $inventory['ultima_actualizacion'] = date('Y-m-d H:i:s');
        saveCoinInventory($inventory);

        echo json_encode([
            'error' => true,
            'mensaje' => 'Cambio dispensado parcialmente: $' . $totalReal . ' de $' . $monto,
            'monto' => $monto,
            'desglose' => $desglose,
            'total_dispensado' => $totalReal,
            'detalle' => $respuesta
        ]);
        error_log("⚠️ dispensarCambio() PARCIAL: $" . $totalReal . " de $" . $monto);
    } else {
        echo json_encode([
            'error' => true,
            'mensaje' => 'Error al dispensar: ' . ($respuesta['errores'][0] ?? 'Error desconocido'),
            'monto' => $monto,
            'desglose' => $desglose,
            'detalle' => $respuesta
        ]);
        error_log("❌ dispensarCambio() FALLO");
    }
}

/**
 * Envía un comando al puerto serial del hardware
 * Los comandos ya incluyen \r\n al final
 */
function enviarComandoSerial($comando) {
    // ✅ Definir solo si NO están definidos (para evitar errores al dispensar múltiples monedas)
    if (!defined('PUERTO_DISPENSER')) {
        define('PUERTO_DISPENSER', 'COM5');
    }
    if (!defined('BAUDRATE_DISPENSER')) {
        define('BAUDRATE_DISPENSER', 9600);
    }
    
    $puerto = PUERTO_DISPENSER;
    $baudrate = BAUDRATE_DISPENSER;
    
    // Log del comando (mostrar sin caracteres invisibles)
    $comandoVisual = str_replace(["\r", "\n"], ['\\r', '\\n'], $comando);
    error_log("📡 Enviando: $comandoVisual");
    
    // Script PowerShell para enviar comando
    // IMPORTANTE: Write() en lugar de WriteLine() porque el comando ya trae \r\n
    $psScript = <<<'EOD'
$ErrorActionPreference = "Stop"
try {
    $port = New-Object System.IO.Ports.SerialPort
    $port.PortName = 'PUERTO_PLACEHOLDER'
    $port.BaudRate = BAUDRATE_PLACEHOLDER
    $port.DataBits = 8
    $port.Parity = [System.IO.Ports.Parity]::None
    $port.StopBits = [System.IO.Ports.StopBits]::One
    $port.ReadTimeout = 1000
    $port.WriteTimeout = 1000
    $port.DtrEnable = $true
    $port.RtsEnable = $true
    
    $port.Open()
    Write-Output "Puerto abierto: $($port.PortName)"
    
    # Enviar comando (el comando ya incluye \r\n)
    $port.Write('COMANDO_PLACEHOLDER')
    Start-Sleep -Milliseconds 300
    
    # Intentar leer respuesta (formato: INT0000XXX\r\n)
    $respuesta = ""
    $intentos = 0
    while ($port.BytesToRead -gt 0 -and $intentos -lt 15) {
        $respuesta += $port.ReadExisting()
        Start-Sleep -Milliseconds 50
        $intentos++
    }
    
    if ($respuesta) {
        Write-Output "Respuesta: $respuesta"
    } else {
        Write-Output "Sin respuesta (timeout)"
    }
    
    $port.Close()
    Write-Output "OK"
    exit 0
} catch {
    Write-Error "Error: $_"
    exit 1
}
EOD;
    
    // Reemplazar placeholders
    $psScript = str_replace('PUERTO_PLACEHOLDER', $puerto, $psScript);
    $psScript = str_replace('BAUDRATE_PLACEHOLDER', $baudrate, $psScript);
    $psScript = str_replace('COMANDO_PLACEHOLDER', addslashes($comando), $psScript);
    
    // Guardar script temporal
    $tempScript = sys_get_temp_dir() . '/hw_command_' . uniqid() . '.ps1';
    file_put_contents($tempScript, $psScript);
    
    // Ejecutar
    $output = [];
    $returnCode = 0;
    exec("powershell.exe -ExecutionPolicy Bypass -File \"$tempScript\" 2>&1", $output, $returnCode);
    
    // Limpiar
    @unlink($tempScript);
    
    // Log resultado
    $outputStr = implode("\n", $output);
    error_log("📥 Respuesta: $outputStr");
    
    // Verificar si hubo respuesta válida del hardware
    $success = $returnCode === 0 && (strpos($outputStr, 'OK') !== false || strpos($outputStr, 'INT0') !== false);
    
    return [
        'success' => $success,
        'output' => $outputStr,
        'return_code' => $returnCode
    ];
}

/**
 * Envía comando RESET al hardware
 * Comando: INT0000000\r\n
 */
function hardwareReset() {
    error_log("🔄 RESET del hardware");
    $resultado = enviarComandoSerial(CMD_RESET);
    
    echo json_encode([
        'success' => $resultado['success'],
        'mensaje' => $resultado['success'] ? 'Hardware reseteado' : 'Error al resetear hardware',
        'comando' => 'INT0000000',
        'output' => $resultado['output']
    ]);
}

/**
 * Habilita el monedero/billetero
 * Comando: INT0000001\r\n
 */
function hardwareHabilitar() {
    error_log("✅ HABILITANDO monedero/billetero");
    $resultado = enviarComandoSerial(CMD_HABILITAR);
    
    echo json_encode([
        'success' => $resultado['success'],
        'mensaje' => $resultado['success'] ? 'Monedero/billetero habilitado' : 'Error al habilitar',
        'comando' => 'INT0000001',
        'output' => $resultado['output']
    ]);
}

/**
 * Deshabilita el monedero/billetero
 * Comando: INT0000002\r\n
 */
function hardwareDeshabilitar() {
    error_log("🚫 DESHABILITANDO monedero/billetero");
    $resultado = enviarComandoSerial(CMD_DESHABILITAR);
    
    echo json_encode([
        'success' => $resultado['success'],
        'mensaje' => $resultado['success'] ? 'Monedero/billetero deshabilitado' : 'Error al deshabilitar',
        'comando' => 'INT0000002',
        'output' => $resultado['output']
    ]);
}

/**
 * 💰 NUEVO: Verifica si hay suficiente cambio disponible para un monto
 */
function checkChangeAvailability() {
    $monto = $_POST['monto'] ?? $_GET['monto'] ?? 0;
    $monto = (float)$monto;

    $resultado = validarCambioDispensableEnMonedas($monto);
    $inventory = loadCoinInventory();

    echo json_encode([
        'success' => true,
        'available' => $resultado['available'],
        'monto_solicitado' => $monto,
        'desglose_propuesto' => $resultado['desglose'] ?? [],
        'faltante' => $resultado['faltante'] ?? 0,
        'total_monedas' => $resultado['total_monedas'] ?? 0,
        'inventario_actual' => $inventory['denominaciones'] ?? [],
        'total_disponible' => $inventory['total_pesos'] ?? 0,
        'cambio_solo_monedas' => true,
        'mensaje' => $resultado['mensaje']
    ]);
}

/**
 * 📥 NUEVO: Registra una moneda recibida en el inventario
 */
function registerCoinReceived() {
    $denominacion = $_POST['denominacion'] ?? 0;
    $denominacion = (int)$denominacion;
    
    // Validar denominación
    $denomsValidas = [1, 2, 5, 10, 20, 50, 100, 200, 500];
    if (!in_array($denominacion, $denomsValidas)) {
        echo json_encode([
            'error' => true,
            'mensaje' => 'Denominación no válida',
            'denominaciones_validas' => $denomsValidas
        ]);
        return;
    }
    
    $inventory = loadCoinInventory();
    $tuboUsado = registerCoinInTubes($inventory, $denominacion);
    
    // Agregar al log
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'tipo' => 'INGRESO',
        'denominacion' => $denominacion,
        'cantidad' => 1,
        'total_denominacion_despues' => $inventory['denominaciones'][(string)$denominacion] ?? 0,
        'tubo' => $tuboUsado
    ];
    if (!isset($inventory['log'])) {
        $inventory['log'] = [];
    }
    $inventory['log'][] = $logEntry;
    
    // Mantener solo últimas 100 entradas del log
    if (count($inventory['log']) > 100) {
        $inventory['log'] = array_slice($inventory['log'], -100);
    }
    
    $inventory['ultima_actualizacion'] = date('Y-m-d H:i:s');
    
    // Guardar
    saveCoinInventory($inventory);
    
    echo json_encode([
        'success' => true,
        'mensaje' => "Moneda de $$denominacion registrada",
        'denominacion' => $denominacion,
        'cantidad_actual' => $inventory['denominaciones'][(string)$denominacion] ?? 0,
        'tubo' => $tuboUsado,
        'total_pesos' => $inventory['total_pesos']
    ]);
}

/**
 * 📊 NUEVO: Obtiene el inventario completo de monedas
 */
function getCoinInventory() {
    $inventory = loadCoinInventory();
    
    echo json_encode([
        'success' => true,
        'inventario' => $inventory
    ]);
}

/**
 * Establece manualmente las cantidades de monedas en el inventario
 * Útil para cargar el inventario inicial al llenar la máquina
 */
function setInventory() {
    $inventory = loadCoinInventory();
    ensureTubeInventory($inventory);

    foreach (array_keys(getTubeConfig()['tubos']) as $tubeId) {
        $postKey = 'tubo_' . $tubeId;
        if (isset($_POST[$postKey])) {
            $inventory['tubos'][$tubeId]['cantidad'] = max(0, (int)$_POST[$postKey]);
        }
    }

    foreach (HARDWARE_DENOMS as $d) {
        $key = (string)$d;
        if (isset($_POST[$key])) {
            $cant = max(0, (int)$_POST[$key]);
            if ($d === 1) {
                $inventory['tubos']['A']['cantidad'] = (int)ceil($cant / 2);
                $inventory['tubos']['B']['cantidad'] = $cant - $inventory['tubos']['A']['cantidad'];
            } elseif ($d === 2) {
                $inventory['tubos']['D']['cantidad'] = $cant;
            } elseif ($d === 5) {
                $inventory['tubos']['E']['cantidad'] = $cant;
            } elseif ($d === 10) {
                $inventory['tubos']['C']['cantidad'] = $cant;
            }
        }
    }

    syncDenominacionesFromTubos($inventory);
    $inventory['ultima_actualizacion'] = date('Y-m-d H:i:s');

    saveCoinInventory($inventory);

    echo json_encode([
        'success'    => true,
        'mensaje'    => 'Inventario actualizado',
        'inventario' => $inventory
    ]);
}

/**
 * Envía un comando raw a la cola del PS para que lo ejecute en COM5.
 * Útil para diagnosticar el mapeo de tubos del hardware.
 */
function enviarComandoRaw() {
    $cmd = trim($_POST['comando'] ?? '');
    if (!$cmd) {
        echo json_encode(['error' => true, 'mensaje' => 'Falta el parámetro "comando"']);
        return;
    }
    // Sanitize: solo letras, números, salto de línea
    $cmd = preg_replace('/[^A-Za-z0-9]/', '', $cmd);
    if (!$cmd) {
        echo json_encode(['error' => true, 'mensaje' => 'Comando inválido']);
        return;
    }

    $dispenseQueueFile  = __DIR__ . '/admin/dist/logs/monedero_dispense_queue.json';
    $dispenseResponseFile = __DIR__ . '/admin/dist/logs/monedero_dispense_response.json';

    // Borrar respuesta anterior
    if (file_exists($dispenseResponseFile)) @unlink($dispenseResponseFile);

    // Encolar como desglose especial de 1 unidad con denominación 0 (raw)
    // El PS detecta status=RAW_CMD y ejecuta el comando directamente
    $payload = [
        'status'    => 'RAW_CMD',
        'comando'   => $cmd,
        'timestamp' => microtime(true),
        'fecha'     => date('Y-m-d H:i:s')
    ];
    file_put_contents($dispenseQueueFile, json_encode($payload, JSON_PRETTY_PRINT));

    // Esperar respuesta máx 10s
    $timeout = time() + 10;
    $resp = null;
    while (time() < $timeout) {
        if (file_exists($dispenseResponseFile)) {
            $resp = json_decode(file_get_contents($dispenseResponseFile), true);
            if ($resp) break;
        }
        usleep(200000);
    }

    if (!$resp) {
        echo json_encode(['error' => true, 'mensaje' => 'Timeout - MonederoMonitor no respondió en 10s']);
        return;
    }
    echo json_encode(['success' => true, 'comando' => $cmd, 'respuesta' => $resp]);
}


function loadCoinInventory() {
    error_log("📂 loadCoinInventory() INICIO");
    error_log("📁 Ruta archivo: " . COIN_INVENTORY_FILE);
    
    if (!file_exists(COIN_INVENTORY_FILE)) {
        error_log("⚠️ Archivo no existe, creando inventario inicial...");
        
        // Crear inventario inicial vacío
        $defaultInventory = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tubos' => initEmptyTubos(),
            'denominaciones' => ['1' => 0, '2' => 0, '5' => 0, '10' => 0],
            'total_pesos' => 0,
            'float_operador' => 0,
            'ultima_actualizacion' => date('Y-m-d H:i:s'),
            'log' => []
        ];
        
        saveCoinInventory($defaultInventory);
        error_log("✅ Inventario inicial creado");
        return $defaultInventory;
    }
    
    error_log("✅ Archivo existe, leyendo...");
    $content = file_get_contents(COIN_INVENTORY_FILE);
    error_log("📄 Contenido length: " . strlen($content));
    
    $data = json_decode($content, true);
    
    if (!$data) {
        error_log("⚠️ JSON corrupto, creando nuevo inventario");
        // Si el archivo está corrupto, crear uno nuevo
        $data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'tubos' => initEmptyTubos(),
            'denominaciones' => ['1' => 0, '2' => 0, '5' => 0, '10' => 0],
            'total_pesos' => 0,
            'ultima_actualizacion' => date('Y-m-d H:i:s'),
            'log' => []
        ];
        return $data;
    }

    ensureTubeInventory($data);
    if (!isset($data['float_operador'])) {
        $data['float_operador'] = 0;
    }
    error_log("✅ Inventario cargado OK");
    return $data;
}

/**
 * 💾 HELPER: Guarda el inventario de monedas en el archivo
 */
function saveCoinInventory($inventory) {
    error_log("💾 saveCoinInventory() INICIO");
    error_log("📁 Ruta: " . COIN_INVENTORY_FILE);
    
    // Crear directorio si no existe
    $dir = dirname(COIN_INVENTORY_FILE);
    if (!is_dir($dir)) {
        error_log("📂 Creando directorio: $dir");
        if (!@mkdir($dir, 0755, true)) {
            error_log("❌ No se pudo crear directorio: $dir");
            throw new Exception("No se pudo crear el directorio de inventario");
        }
    }
    
    // Intentar escribir archivo
    $json = json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $result = @file_put_contents(COIN_INVENTORY_FILE, $json);
    
    if ($result === false) {
        error_log("❌ Error escribiendo archivo");
        throw new Exception("No se pudo guardar el inventario");
    }
    
    error_log("✅ Inventario guardado OK ($result bytes)");
    return true;
}



