<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir conexión a la base de datos
include 'db_config_dual.php';

// Verificar que se reciba el ID de la comanda
if (!isset($_POST['id_comanda']) || empty($_POST['id_comanda'])) {
    echo json_encode(['error' => 'ID de comanda no proporcionado']);
    exit;
}

$id_comanda = intval($_POST['id_comanda']);

try {
    // Consulta para obtener los detalles de la venta con información del producto
    $sql = "SELECT 
        vd.id_detalle,
        vd.cantidad,
        vd.precio_unitario,
        vd.descuento_unitario,
        vd.subtotal,
        vd.iva_unitario,
        vd.total,
        vd.notas as notas_producto,
        p.nombre_producto,
        p.descripcion,
        p.sku,
        vc.folio,
        vc.fecha_venta,
        vc.total as total_venta
    FROM ventas_detalle vd 
    INNER JOIN productos p ON vd.id_producto = p.id_producto
    INNER JOIN ventas_comanda vc ON vd.id_comandC = vc.id_comanda
    WHERE vd.id_comandC = ?
    ORDER BY vd.id_detalle ASC";

    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception("Error en la preparación de la consulta: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "i", $id_comanda);
    mysqli_stmt_execute($stmt);
    $resultado = mysqli_stmt_get_result($stmt);
    
    if (!$resultado) {
        throw new Exception("Error en la ejecución de la consulta: " . mysqli_error($conn));
    }
    
    $productos = [];
    $info_venta = null;
    $total_general = 0;
    
    while ($fila = mysqli_fetch_assoc($resultado)) {
        // Guardar información de la venta (solo la primera vez)
        if ($info_venta === null) {
            $info_venta = [
                'folio' => $fila['folio'],
                'fecha_venta' => $fila['fecha_venta'],
                'total_venta' => $fila['total_venta']
            ];
        }
        
        // Convertir a números para cálculos precisos
        $precio_unitario = floatval($fila['precio_unitario']);
        $descuento_unitario = floatval($fila['descuento_unitario']);
        $subtotal = floatval($fila['subtotal']);
        $iva_unitario = floatval($fila['iva_unitario']);
        $total = floatval($fila['total']);
        
        // Agregar producto al array (formatear solo para mostrar)
        $productos[] = [
            'id_detalle' => $fila['id_detalle'],
            'nombre_producto' => $fila['nombre_producto'],
            'descripcion' => $fila['descripcion'],
            'sku' => $fila['sku'],
            'cantidad' => intval($fila['cantidad']),
            'precio_unitario' => number_format($precio_unitario, 2, '.', ''),
            'descuento_unitario' => number_format($descuento_unitario, 2, '.', ''),
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'iva_unitario' => number_format($iva_unitario, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
            'notas_producto' => $fila['notas_producto']
        ];
        
        // Sumar al total general usando el valor sin formatear
        $total_general += $total;
    }
    
    mysqli_stmt_close($stmt);
    
    // Verificar si se encontraron productos
    if (empty($productos)) {
        echo json_encode([
            'error' => 'No se encontraron productos para esta venta',
            'id_comanda' => $id_comanda
        ]);
        exit;
    }
    
    // Usar el total de la venta desde ventas_comanda (más preciso)
    $total_oficial = floatval($info_venta['total_venta']);
    
    // Respuesta exitosa
    echo json_encode([
        'success' => true,
        'info_venta' => $info_venta,
        'productos' => $productos,
        'total_general' => number_format($total_oficial, 2, '.', ''),
        'total_calculado' => number_format($total_general, 2, '.', ''), // Para depuración
        'cantidad_productos' => count($productos)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
