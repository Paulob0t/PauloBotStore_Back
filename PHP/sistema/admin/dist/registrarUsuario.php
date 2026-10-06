<?php
include 'db_config_dual.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($nombre === '' || $correo === '' || $contrasena === '') {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    // Verificar si el correo ya existe
    $stmt = $conn->prepare('SELECT id FROM usuarios WHERE correo = ?');
    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'El correo ya está registrado.']);
        exit;
    }
    $stmt->close();

    // Encriptar la contraseña
    $hash = md5($contrasena);
    $tipoUsuario = 1;
    // Insertar el usuario
    $stmt = $conn->prepare('INSERT INTO usuarios (nombre, correo, contrasena, tipo_usuario) VALUES (?, ?, ?,?)');
    $stmt->bind_param('ssss', $nombre, $correo, $hash, $tipoUsuario);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Registro exitoso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al registrar.']);
    }
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
?>
