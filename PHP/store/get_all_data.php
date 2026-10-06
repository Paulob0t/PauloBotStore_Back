<?php
/**
 * �0�4 API OPTIMIZADA - Carga todo de una vez
 * Reduce m��ltiples peticiones HTTP a una sola
 */

require_once "./admin/dist/db_config_dual.php";

// Headers deben ir ANTES de cualquier output
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Limpiar cualquier output buffer previo
while (ob_get_level()) {
    ob_end_clean();
}

$response = [
    'success' => true,
    'timestamp' => time(),
    'data' => [
        'categories' => [],
        'products' => [],
        'featured_products' => []
    ]
];

try {
    // 1�1�5�6�6 CATEGOR�0�1AS + SUBCATEGOR�0�1AS (1 query con JOIN)
    $sql_categories = "
        SELECT 
            c.id_categoria, 
            c.nombre_categoria, 
            c.imagen_categoria, 
            s.id_subcategoria, 
            s.nombre_subcategoria, 
            s.imagen_subcategoria 
        FROM categorias c 
        LEFT JOIN subcategorias s ON s.id_categoria = c.id_categoria 
        ORDER BY c.nombre_categoria, s.nombre_subcategoria
    ";
    
    $result_cat = mysqli_query($conn, $sql_categories);
    $categories = [];
    
    if ($result_cat && mysqli_num_rows($result_cat) > 0) {
        while ($row = mysqli_fetch_assoc($result_cat)) {
            $id_cat = $row['id_categoria'];
            
            if (!isset($categories[$id_cat])) {
                $categories[$id_cat] = [
                    'id_categoria' => $id_cat,
                    'nombre_categoria' => $row['nombre_categoria'],
                    'imagen_categoria' => $row['imagen_categoria'],
                    'subcategorias' => []
                ];
            }
            
            if (!empty($row['id_subcategoria'])) {
                $categories[$id_cat]['subcategorias'][] = [
                    'id_subcategoria' => $row['id_subcategoria'],
                    'nombre_subcategoria' => $row['nombre_subcategoria'],
                    'imagen_subcategoria' => $row['imagen_subcategoria']
                ];
            }
        }
        mysqli_free_result($result_cat);
    }
    
    $response['data']['categories'] = array_values($categories);
    
    // 2�1�5�6�6 TODOS LOS PRODUCTOS (1 query optimizada)
    $sql_products = "
        SELECT 
            id_producto, 
            id_categoria, 
            id_subcategoria, 
            nombre_producto, 
            precio, 
            descuento, 
            imagen_principal,
            destacado,
            orden_destacado
        FROM productos 
        WHERE activo = 1
        ORDER BY destacado DESC, orden_destacado ASC, nombre_producto ASC
    ";
    
    $result_prod = mysqli_query($conn, $sql_products);
    $products = [];
    $featured = [];
    
    if ($result_prod && mysqli_num_rows($result_prod) > 0) {
        while ($row = mysqli_fetch_assoc($result_prod)) {
            $product = [
                'id_producto' => $row['id_producto'],
                'id_categoria' => $row['id_categoria'],
                'id_subcategoria' => $row['id_subcategoria'],
                'nombre_producto' => $row['nombre_producto'],
                'precio' => $row['precio'],
                'descuento' => $row['descuento'],
                'imagen_principal' => $row['imagen_principal'],
                'orden_destacado' => $row['orden_destacado']
            ];
            
            $products[] = $product;
            
            // Separar productos destacados (solo los que tienen destacado = 1 Y orden_destacado)
            if ($row['destacado'] == 1 && !empty($row['orden_destacado'])) {
                $featured[] = $product;
            }
        }
        mysqli_free_result($result_prod);
    }
    
    $response['data']['products'] = $products;
    $response['data']['featured_products'] = $featured;
    
    // Estad��sticas ��tiles
    $response['stats'] = [
        'total_categories' => count($response['data']['categories']),
        'total_products' => count($products),
        'total_featured' => count($featured)
    ];
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = 'Error al cargar datos';
    error_log('Error en get_all_data.php: ' . $e->getMessage());
}

// Cerrar conexi��n antes de enviar respuesta
if (isset($conn)) {
    mysqli_close($conn);
}

// Enviar respuesta JSON limpia
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
exit;
