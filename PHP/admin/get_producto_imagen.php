<?php
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Database;

$id = (int)($_GET['id'] ?? 0);
$type = $_GET['type'] ?? 'main';

if ($id <= 0) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$column = 'imagen_principal';
if ($type === 'sec1') $column = 'imagen_secundaria_1';
if ($type === 'sec2') $column = 'imagen_secundaria_2';
if ($type === 'sec3') $column = 'imagen_secundaria_3';

$db = Database::getConnection();
$stmt = $db->prepare("SELECT $column FROM productos WHERE id_producto = ? LIMIT 1");
if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($imageData);
    if ($stmt->fetch() && !empty($imageData)) {
        $stmt->close();

        // Si es Data URI Base64 (data:image/png;base64,...)
        if (str_starts_with($imageData, 'data:image/')) {
            $parts = explode(',', $imageData, 2);
            $mimePart = $parts[0];
            $base64Data = $parts[1] ?? '';

            preg_match('/data:(image\/[a-zA-Z0-9\+\-\.]+);base64/', $mimePart, $matches);
            $mimeType = $matches[1] ?? 'image/jpeg';

            header("Content-Type: $mimeType");
            header("Cache-Control: public, max-age=86400"); // Cache de 24h
            echo base64_decode($base64Data);
            exit();
        }

        // Si es URL directa o binario
        if (filter_var($imageData, FILTER_VALIDATE_URL)) {
            header("Location: " . $imageData);
            exit();
        }

        header("Content-Type: image/jpeg");
        header("Cache-Control: public, max-age=86400");
        echo $imageData;
        exit();
    }
    $stmt->close();
}

// Placeholder de fallback si no hay imagen
header("Content-Type: image/svg+xml");
echo '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
