<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db_config_dual.php';

try {
    // Accept JSON or form-encoded
    $raw = file_get_contents('php://input');
    $data = [];
    if (!empty($_POST)) {
        $data = $_POST;
    } elseif ($raw) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $data = $decoded;
    }

    $page = isset($data['page']) ? trim($data['page']) : '';
    $title = isset($data['title']) ? trim($data['title']) : '';

    if ($page === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Parámetros inválidos']);
        exit;
    }

    // Create table if not exists
    $createSql = "CREATE TABLE IF NOT EXISTS page_titles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(50) NOT NULL UNIQUE,
        title VARCHAR(255) NOT NULL DEFAULT '',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    if (!$conn->query($createSql)) {
        throw new Exception('Error creando tabla: ' . $conn->error);
    }

    // Upsert title - actualiza si existe, inserta si no existe
    $stmt = $conn->prepare("INSERT INTO page_titles (page, title) VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE title = ?, updated_at = CURRENT_TIMESTAMP");
    if (!$stmt) {
        throw new Exception('Error preparando consulta: ' . $conn->error);
    }
    $stmt->bind_param('sss', $page, $title, $title);
    if (!$stmt->execute()) {
        throw new Exception('Error guardando título: ' . $stmt->error);
    }
    $stmt->close();

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
