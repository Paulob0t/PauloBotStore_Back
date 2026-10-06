<?php
/**
 * 🚀 EJECUTAR SINCRONIZACIÓN MANUAL
 * Ejecuta la sincronización desde PHP en lugar de PowerShell
 */
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', 0);

$accion = $_GET['accion'] ?? 'help';
$resultado = ['success' => false, 'mensaje' => '', 'datos' => []];

try {
    require_once 'db_config_dual.php';
    
    if (!$conn) {
        throw new Exception('No hay conexión a la base de datos');
    }
    
    // ====================================
    // SINCRONIZAR LOCAL → NUBE
    // ====================================
    if ($accion === 'sync_nube') {
        // Verificar que existe la tabla
        $check = $conn->query("SHOW TABLES LIKE 'sincronizacion_log'");
        if (!$check || $check->num_rows === 0) {
            throw new Exception('La tabla sincronizacion_log no existe');
        }
        
        // Obtener registros pendientes
        $pendientes = $conn->query("SELECT * FROM sincronizacion_log WHERE sincronizado = 0 ORDER BY id_sync ASC LIMIT 100");
        
        if (!$pendientes || $pendientes->num_rows === 0) {
            $resultado['success'] = true;
            $resultado['mensaje'] = 'No hay registros pendientes de sincronizar';
            $resultado['datos']['procesados'] = 0;
        } else {
            // Preparar registros para enviar
            $registros = [];
            while ($row = $pendientes->fetch_assoc()) {
                $registros[] = [
                    'id_sync' => (int)$row['id_sync'],
                    'tabla' => $row['tabla'],
                    'accion' => $row['accion'],
                    'id_registro' => (int)$row['id_registro'],
                    'datos' => json_decode($row['datos'] ?? '{}')
                ];
            }
            
            // Enviar al endpoint en la nube
            $endpoint_url = 'https://vendingbox.online/sistema/sincronizar_endpoint.php';
            $payload = json_encode(['registros' => $registros]);
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $endpoint_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($payload)
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            if ($http_code !== 200) {
                throw new Exception("Error HTTP $http_code: " . ($curl_error ?: 'Error al contactar el endpoint'));
            }
            
            $respuesta_nube = json_decode($response, true);
            
            if (!$respuesta_nube) {
                throw new Exception('Respuesta inválida del endpoint: ' . substr($response, 0, 200));
            }
            
            // Marcar como sincronizados los exitosos
            if (isset($respuesta_nube['exitosos']) && count($respuesta_nube['exitosos']) > 0) {
                $ids_exitosos = implode(',', array_map('intval', $respuesta_nube['exitosos']));
                $conn->query("UPDATE sincronizacion_log SET sincronizado = 1 WHERE id_sync IN ($ids_exitosos)");
            }
            
            $resultado['success'] = true;
            $resultado['mensaje'] = 'Sincronización completada';
            $resultado['datos'] = [
                'total_enviados' => count($registros),
                'exitosos' => count($respuesta_nube['exitosos'] ?? []),
                'fallidos' => count($respuesta_nube['fallidos'] ?? []),
                'respuesta_nube' => $respuesta_nube
            ];
        }
    }
    
    // ====================================
    // SINCRONIZAR NUBE → LOCAL
    // ====================================
    elseif ($accion === 'sync_local') {
        // Obtener datos de la nube
        $endpoint_url = 'https://vendingbox.online/sistema/sincronizar_local.php';
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $endpoint_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code !== 200) {
            throw new Exception("Error HTTP $http_code: " . ($curl_error ?: 'Error al contactar el endpoint'));
        }
        
        $datos_nube = json_decode($response, true);
        
        if (!$datos_nube) {
            throw new Exception('Respuesta inválida del endpoint de la nube');
        }
        
        // Aplicar cambios en BD local
        $tablas_actualizadas = 0;
        $registros_actualizados = 0;
        
        foreach ($datos_nube as $tabla => $registros) {
            if (!is_array($registros) || count($registros) === 0) continue;
            
            // Verificar que la tabla existe
            $check = $conn->query("SHOW TABLES LIKE '$tabla'");
            if (!$check || $check->num_rows === 0) continue;
            
            // Por simplicidad, truncar y reinsertar (en producción usa lógica más sofisticada)
            $conn->query("TRUNCATE TABLE `$tabla`");
            
            foreach ($registros as $registro) {
                $columnas = array_keys($registro);
                $valores = array_values($registro);
                
                $cols_str = implode('`, `', $columnas);
                $placeholders = implode(', ', array_fill(0, count($valores), '?'));
                
                $stmt = $conn->prepare("INSERT INTO `$tabla` (`$cols_str`) VALUES ($placeholders)");
                
                // Bind dinámico
                $types = str_repeat('s', count($valores)); // Usar string para todos por simplicidad
                $stmt->bind_param($types, ...$valores);
                $stmt->execute();
                $stmt->close();
                
                $registros_actualizados++;
            }
            
            $tablas_actualizadas++;
        }
        
        $resultado['success'] = true;
        $resultado['mensaje'] = 'Sincronización desde la nube completada';
        $resultado['datos'] = [
            'tablas_actualizadas' => $tablas_actualizadas,
            'registros_insertados' => $registros_actualizados
        ];
    }
    
    // ====================================
    // LIMPIAR SINCRONIZADOS
    // ====================================
    elseif ($accion === 'limpiar_sincronizados') {
        $result = $conn->query("DELETE FROM sincronizacion_log WHERE sincronizado = 1");
        $eliminados = $conn->affected_rows;
        
        $resultado['success'] = true;
        $resultado['mensaje'] = "Se eliminaron $eliminados registros ya sincronizados";
        $resultado['datos']['eliminados'] = $eliminados;
    }
    
    // ====================================
    // HELP
    // ====================================
    else {
        $resultado['success'] = false;
        $resultado['mensaje'] = 'Acción no válida';
        $resultado['datos'] = [
            'acciones_disponibles' => [
                'sync_nube' => 'Sincronizar registros pendientes hacia la nube',
                'sync_local' => 'Descargar y aplicar datos desde la nube',
                'limpiar_sincronizados' => 'Eliminar registros ya sincronizados'
            ],
            'uso' => 'ejecutar_sync.php?accion=[sync_nube|sync_local|limpiar_sincronizados]'
        ];
    }
    
} catch (Exception $e) {
    $resultado['success'] = false;
    $resultado['mensaje'] = $e->getMessage();
    $resultado['error'] = true;
}

echo json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
