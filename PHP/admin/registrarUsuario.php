<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/User.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

use App\Controllers\AuthController;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['nombre'] ?? '';
    $email = $_POST['correo'] ?? '';
    $password = $_POST['contrasena'] ?? '';
    $confirmPassword = $_POST['confirmar_contrasena'] ?? $password;

    $auth = new AuthController();
    $response = $auth->register($name, $email, $password, $confirmPassword);

    echo json_encode($response);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
