<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/Product.php';
require_once __DIR__ . '/../app/Controllers/ProductController.php';

use App\Controllers\ProductController;
use Exception;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Método no permitido", 405);
    }

    $id_producto = (int)($_POST['id_producto'] ?? 0);
    if ($id_producto <= 0) {
        throw new Exception("ID de producto inválido", 400);
    }

    $controller = new ProductController();
    $response = $controller->deleteProduct($id_producto);

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code($e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
