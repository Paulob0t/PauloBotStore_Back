<?php
session_start();
include "db_config_dual.php";

header('Content-Type: application/json');

function verifyAndUpgradePassword(mysqli $conn, int $userId, string $plainPassword, string $storedHash): bool
{
    $storedHash = trim((string) $storedHash);
    if ($storedHash === '') {
        return false;
    }

    if (password_get_info($storedHash)['algo'] !== 0) {
        if (password_verify($plainPassword, $storedHash)) {
            return true;
        }
    }

    if (hash_equals($storedHash, $plainPassword)) {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $upd = $conn->prepare('UPDATE usuarios SET contrasena = ? WHERE id = ?');
        if ($upd) {
            $upd->bind_param('si', $newHash, $userId);
            $upd->execute();
            $upd->close();
        }
        return true;
    }

    // Legacy MD5 (usuarios viejos)
    if (strlen($storedHash) === 32 && ctype_xdigit($storedHash) && hash_equals($storedHash, md5($plainPassword))) {
        $newHash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $upd = $conn->prepare('UPDATE usuarios SET contrasena = ? WHERE id = ?');
        if ($upd) {
            $upd->bind_param('si', $newHash, $userId);
            $upd->execute();
            $upd->close();
        }
        return true;
    }

    return false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = trim($_POST['correo'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($correo === '' || $contrasena === '') {
        echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios.']);
        exit;
    }

    if (!$conn || $conn->connect_error) {
        echo json_encode(['success' => false, 'message' => 'Error de conexión a la base de datos.']);
        exit;
    }

    $hasActivo = false;
    $colCheck = $conn->query("SHOW COLUMNS FROM usuarios LIKE 'activo'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $hasActivo = true;
    }

    $sql = $hasActivo
        ? 'SELECT id, contrasena, tipo_usuario, nombre, activo FROM usuarios WHERE correo = ? LIMIT 1'
        : 'SELECT id, contrasena, tipo_usuario, nombre FROM usuarios WHERE correo = ? LIMIT 1';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar consulta de login.']);
        exit;
    }

    $stmt->bind_param('s', $correo);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows !== 1) {
        echo json_encode(['success' => false, 'message' => 'El correo no está registrado.']);
        $stmt->close();
        exit;
    }

    if ($hasActivo) {
        $stmt->bind_result($id, $hash, $tipo_usuario, $nombre, $activo);
    } else {
        $stmt->bind_result($id, $hash, $tipo_usuario, $nombre);
    }
    $stmt->fetch();
    $stmt->close();

    if ($hasActivo && (int) $activo !== 1) {
        echo json_encode(['success' => false, 'message' => 'Usuario inactivo. Contacta al administrador.']);
        exit;
    }

    if (verifyAndUpgradePassword($conn, (int) $id, $contrasena, $hash)) {
        $_SESSION['uid'] = $id;
        $_SESSION['login'] = true;
        $_SESSION['tipo_usuario'] = $tipo_usuario;
        $_SESSION['nombre_usuario'] = $nombre;
        echo json_encode(['success' => true, 'message' => 'Inicio de sesión exitoso.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Contraseña incorrecta.']);
    }

    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido.']);
}
