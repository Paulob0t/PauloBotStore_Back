<?php
require_once 'db_config_dual.php';
header('Content-Type: application/json');

$id = $_GET['id'] ?? 0;
$response = ['success' => false];

$stmt = $conn->prepare('SELECT nombre_categoria FROM categorias WHERE id_categoria = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$stmt->bind_result($nombre);
if ($stmt->fetch()) {
    $response['success'] = true;
    $response['nombre'] = $nombre;
}
$stmt->close();

echo json_encode($response);
?>
