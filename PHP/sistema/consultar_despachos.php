<?php
/**
 * Consultar estado de despachos al Arduino
 * Este archivo permite ver el historial y estado de los despachos
 * 
 * Fecha: 16-10-2025
 */

header('Content-Type: application/json');
include "./admin/dist/db_config_dual.php";

if (!$conn) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

// Obtener parámetros
$id_comanda = isset($_GET['id_comanda']) ? intval($_GET['id_comanda']) : null;
$estatus = isset($_GET['estatus']) ? intval($_GET['estatus']) : null;
$limite = isset($_GET['limite']) ? intval($_GET['limite']) : 50;

// Construir consulta
$sql = "SELECT 
    d.id_despacho,
    d.id_comanda,
    d.id_producto,
    d.sku,
    d.cantidad,
    d.ubicacion,
    d.id_pago,
    d.estatus_despacho,
    d.fecha_registro,
    d.fecha_enviado,
    d.fecha_despachado,
    d.respuesta_arduino,
    d.intentos_envio,
    d.notas,
    p.nombre_producto,
    v.folio,
    v.total as total_venta,
    v.metodo_pago,
    CASE d.estatus_despacho
        WHEN 0 THEN 'Pendiente'
        WHEN 1 THEN 'Enviado al Arduino'
        WHEN 2 THEN 'Despachado'
        WHEN 3 THEN 'Error'
        ELSE 'Desconocido'
    END as estatus_texto
FROM despachos_arduino d
INNER JOIN productos p ON d.id_producto = p.id_producto
INNER JOIN ventas_comanda v ON d.id_comanda = v.id_comanda
WHERE 1=1";

$params = [];
$types = '';

if ($id_comanda !== null) {
    $sql .= " AND d.id_comanda = ?";
    $params[] = $id_comanda;
    $types .= 'i';
}

if ($estatus !== null) {
    $sql .= " AND d.estatus_despacho = ?";
    $params[] = $estatus;
    $types .= 'i';
}

$sql .= " ORDER BY d.fecha_registro DESC LIMIT ?";
$params[] = $limite;
$types .= 'i';

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode(['error' => 'Error al preparar consulta: ' . mysqli_error($conn)]);
    exit;
}

// Bind parámetros dinámicamente
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$despachos = [];
while ($row = mysqli_fetch_assoc($result)) {
    // Decodificar respuesta del Arduino si existe
    if ($row['respuesta_arduino']) {
        $row['respuesta_arduino'] = json_decode($row['respuesta_arduino'], true);
    }
    $despachos[] = $row;
}

mysqli_stmt_close($stmt);

// Obtener estadísticas
$sql_stats = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN estatus_despacho = 0 THEN 1 ELSE 0 END) as pendientes,
    SUM(CASE WHEN estatus_despacho = 1 THEN 1 ELSE 0 END) as enviados,
    SUM(CASE WHEN estatus_despacho = 2 THEN 1 ELSE 0 END) as despachados,
    SUM(CASE WHEN estatus_despacho = 3 THEN 1 ELSE 0 END) as errores
FROM despachos_arduino";

$result_stats = mysqli_query($conn, $sql_stats);
$estadisticas = mysqli_fetch_assoc($result_stats);

mysqli_close($conn);

echo json_encode([
    'success' => true,
    'despachos' => $despachos,
    'total_registros' => count($despachos),
    'estadisticas' => $estadisticas
]);
?>
