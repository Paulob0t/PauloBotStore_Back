<?php
// Configuración de errores para PRODUCCIÓN (NUBE) - ANTES de cualquier output
error_reporting(E_ALL);
ini_set('display_errors', 0);        // ✅ NO mostrar errores en output (rompe JSON)
ini_set('log_errors', 1);            // ✅ Guardar errores en log file
ini_set('error_log', __DIR__ . '/../../logs/php_errors.log');

// Iniciar buffer de salida para capturar cualquier output inesperado
ob_start();

session_start();

require_once 'db_config_dual.php';

// Limpiar cualquier output generado hasta ahora
ob_end_clean();

// AHORA establecer el header JSON (sin output previo)
header('Content-Type: application/json');

try {
    // Verificar método POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido", 405);
    }

    // Validar campos requeridos
    $required = ['nombre_producto', 'descripcion', 'precio', 'stock', 'id_categoria', 'ubicacion'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            throw new Exception("El campo $field es requerido", 400);
        }
    }
    
    // Validar formato de ubicación (Letra + Número, ej: A1, B2)
    $ubicacion = strtoupper(trim($_POST['ubicacion']));
    if (!preg_match('/^[A-Z][0-9]$/', $ubicacion)) {
        throw new Exception("Formato de ubicación inválido. Use letra + número (Ej: A1, B2)", 400);
    }

    // Manejo compatible con ambos nombres de campos
    $imagen_principal = $_POST['imagen_principal'];
    
    // Para imágenes secundarias, probamos ambos nombres posibles
    $imagen_secundaria_1 = $_POST['imagen_secundaria_1'] ?? $_POST['img_secundaria_1_base64'] ?? null;
    $imagen_secundaria_2 = $_POST['imagen_secundaria_2'] ?? $_POST['img_secundaria_2_base64'] ?? null;
    $imagen_secundaria_3 = $_POST['imagen_secundaria_3'] ?? $_POST['img_secundaria_3_base64'] ?? null;

    // Procesar datos
    $datos = [
        'id_usuario' => $_SESSION['user_id'] ?? 1,
        'id_categoria' => intval($_POST['id_categoria']),
        'id_subcategoria' => !empty($_POST['id_subcategoria']) ? intval($_POST['id_subcategoria']) : null,
        'nombre_producto' => trim($_POST['nombre_producto']),
        'descripcion' => trim($_POST['descripcion']),
        'precio' => floatval($_POST['precio']),
        'descuento' => isset($_POST['descuento']) && $_POST['descuento'] !== '' ? floatval($_POST['descuento']) : null,
        'stock' => intval($_POST['stock']),
        'sku' => !empty($_POST['sku']) ? trim($_POST['sku']) : null,
        'ubicacion' => $ubicacion,
        'imagen_principal' => $imagen_principal,
        'imagen_secundaria_1' => $imagen_secundaria_1,
        'imagen_secundaria_2' => $imagen_secundaria_2,
        'imagen_secundaria_3' => $imagen_secundaria_3,
        'destacado' => isset($_POST['destacado']) ? 1 : 0,
        'orden_destacado' => null,
        'activo' => isset($_POST['activo']) ? 1 : 0
    ];
    
    // Validar orden de destacado
    if ($datos['destacado'] == 1) {
        if (empty($_POST['orden_destacado'])) {
            throw new Exception("El orden es requerido para productos destacados", 400);
        }
        
        $orden = intval($_POST['orden_destacado']);
        if ($orden < 1) {
            throw new Exception("El orden debe ser un número mayor a 0", 400);
        }
        
        // Verificar que el orden no esté ocupado
        $id_producto_actual = !empty($_POST['id_producto']) ? intval($_POST['id_producto']) : null;
        $sql_check = "SELECT id_producto FROM productos WHERE orden_destacado = ? AND destacado = 1";
        
        if ($id_producto_actual) {
            $sql_check .= " AND id_producto != ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ii", $orden, $id_producto_actual);
        } else {
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("i", $orden);
        }
        
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();
        
        if ($result_check->num_rows > 0) {
            $stmt_check->close();
            throw new Exception("El orden $orden ya está ocupado por otro producto. Elige otro número.", 400);
        }
        $stmt_check->close();
        
        $datos['orden_destacado'] = $orden;
    }
    
    // Log detallado para depuración
    error_log("=== DEBUGGING DESCUENTO ===");
    error_log("POST descuento: " . (isset($_POST['descuento']) ? $_POST['descuento'] : 'NO EXISTE'));
    error_log("POST precio_descuento: " . (isset($_POST['precio_descuento']) ? $_POST['precio_descuento'] : 'NO EXISTE'));
    error_log("POST usa_descuento: " . (isset($_POST['usa_descuento']) ? $_POST['usa_descuento'] : 'NO EXISTE'));
    error_log("Descuento procesado: " . ($datos['descuento'] ?? 'NULL'));
    error_log("Todos los campos POST: " . print_r($_POST, true));
    error_log("=== FIN DEBUG ===");

    // Validar imagen principal (Base64)
    if (!empty($datos['imagen_principal']) && !preg_match('/^data:image\/(\w+);base64,/', $datos['imagen_principal'], $matches)) {
        // Si no es Base64, asumimos que es una URL válida existente
        if (!filter_var($datos['imagen_principal'], FILTER_VALIDATE_URL)) {
            throw new Exception("Formato de imagen principal no válido", 400);
        }
    }

    // Validar imágenes secundarias (si existen)
    for ($i = 1; $i <= 3; $i++) {
        $field = "imagen_secundaria_$i";
        if (!empty($datos[$field])) {
            // Si no es Base64, validar como URL
            if (!preg_match('/^data:image\/(\w+);base64,/', $datos[$field])) {
                if (!filter_var($datos[$field], FILTER_VALIDATE_URL)) {
                    throw new Exception("Formato de imagen secundaria $i no válido", 400);
                }
            }
        }
    }
    
    error_log("Datos recibidos para producto: " . print_r($datos, true));

    // Si recibimos id_producto, hacemos UPDATE, si no, INSERT
    if (!empty($_POST['id_producto'])) {
        $id_producto = intval($_POST['id_producto']);
        
        // Construir consulta UPDATE dinámica para actualizar solo los campos con valores
        $updates = [];
        $params = [];
        $types = '';
        
        $fields_map = [
            'id_usuario' => 'i',
            'id_categoria' => 'i',
            'id_subcategoria' => 'i',
            'nombre_producto' => 's',
            'descripcion' => 's',
            'precio' => 'd',
            'descuento' => 'd',
            'stock' => 'i',
            'sku' => 's',
            'ubicacion' => 's',
            'imagen_principal' => 's',
            'imagen_secundaria_1' => 's',
            'imagen_secundaria_2' => 's',
            'imagen_secundaria_3' => 's',
            'destacado' => 'i',
            'orden_destacado' => 'i',
            'activo' => 'i'
        ];
        
        foreach ($fields_map as $field => $type) {
            if (array_key_exists($field, $datos)) {
                $updates[] = "$field = ?";
                $types .= $type;
                $params[] = $datos[$field];
            }
        }
        
        $types .= 'i'; // Para el id_producto
        $params[] = $id_producto;
        
        $sql = "UPDATE productos SET " . implode(', ', $updates) . " WHERE id_producto = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el producto: " . $stmt->error);
        }
        
        $stmt->close();
        
        // ✅ REGISTRAR EN SINCRONIZACION_LOG (UPDATE) - OPCIONAL, no debe fallar todo
        try {
            $stmt_log = $conn->prepare("INSERT INTO sincronizacion_log (uuid, tabla, accion, id_registro, datos, origen, sincronizado) VALUES (UUID(), 'productos', 'UPDATE', ?, ?, 'NUBE', 0)");
            if ($stmt_log) {
                $datos_json = json_encode($datos);
                $stmt_log->bind_param("is", $id_producto, $datos_json);
                if (!$stmt_log->execute()) {
                    error_log("⚠️ No se pudo registrar en sincronizacion_log (UPDATE): " . $stmt_log->error);
                }
                $stmt_log->close();
            } else {
                error_log("⚠️ No se pudo preparar INSERT a sincronizacion_log: " . $conn->error);
            }
        } catch (Exception $e) {
            error_log("⚠️ Error en sincronizacion_log (UPDATE): " . $e->getMessage());
        }
        echo json_encode([
            'success' => true,
            'message' => 'Producto actualizado correctamente',
            'producto_id' => $id_producto
        ]);
    } else {
        // Insertar producto
        $stmt = $conn->prepare("INSERT INTO productos (
            id_usuario, id_categoria, id_subcategoria, nombre_producto, descripcion,
            precio, descuento, stock, sku, ubicacion, imagen_principal, imagen_secundaria_1,
            imagen_secundaria_2, imagen_secundaria_3, destacado, orden_destacado, activo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "iiisssddssssssiii",
            $datos['id_usuario'],
            $datos['id_categoria'],
            $datos['id_subcategoria'],
            $datos['nombre_producto'],
            $datos['descripcion'],
            $datos['precio'],
            $datos['descuento'],
            $datos['stock'],
            $datos['sku'],
            $datos['ubicacion'],
            $datos['imagen_principal'],
            $datos['imagen_secundaria_1'],
            $datos['imagen_secundaria_2'],
            $datos['imagen_secundaria_3'],
            $datos['destacado'],
            $datos['orden_destacado'],
            $datos['activo']
        );

        if (!$stmt->execute()) {
            throw new Exception("Error al guardar el producto: " . $stmt->error);
        }

        $producto_id = $conn->insert_id;
        $stmt->close();
        
        // ✅ REGISTRAR EN SINCRONIZACION_LOG (INSERT) - OPCIONAL, no debe fallar todo
        try {
            $stmt_log = $conn->prepare("INSERT INTO sincronizacion_log (uuid, tabla, accion, id_registro, datos, origen, sincronizado) VALUES (UUID(), 'productos', 'INSERT', ?, ?, 'NUBE', 0)");
            if ($stmt_log) {
                $datos_json = json_encode($datos);
                $stmt_log->bind_param("is", $producto_id, $datos_json);
                if (!$stmt_log->execute()) {
                    error_log("⚠️ No se pudo registrar en sincronizacion_log (INSERT): " . $stmt_log->error);
                }
                $stmt_log->close();
            } else {
                error_log("⚠️ No se pudo preparar INSERT a sincronizacion_log: " . $conn->error);
            }
        } catch (Exception $e) {
            error_log("⚠️ Error en sincronizacion_log (INSERT): " . $e->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Producto guardado correctamente',
            'producto_id' => $producto_id
        ]);
    }

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getCode()
    ]);
    
    error_log("Error en guardar_producto.php: " . $e->getMessage());
}

$conn->close();
?>
