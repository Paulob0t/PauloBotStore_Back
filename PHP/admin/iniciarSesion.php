<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

use App\Controllers\AuthController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['correo'] ?? '';
    $password = $_POST['contrasena'] ?? '';

    $auth = new AuthController();
    $response = $auth->login($email, $password);

    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
