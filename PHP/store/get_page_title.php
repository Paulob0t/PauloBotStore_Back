<?php
header('Content-Type: application/json; charset=utf-8');
require_once "./admin/dist/db_config_dual.php";

$page = isset($_GET['page']) ? trim($_GET['page']) : '';
if ($page === '') {
    echo json_encode(['success' => false, 'title' => '', 'error' => 'Parámetro page requerido']);
    exit;
}

$title = '';
try {
    $exists = $conn->query("SHOW TABLES LIKE 'page_titles'");
    if ($exists && $exists->num_rows > 0) {
        $stmt = $conn->prepare("SELECT title FROM page_titles WHERE page = ? LIMIT 1");
        $stmt->bind_param('s', $page);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $title = $row['title'];
            }
        }
        $stmt->close();
    }
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'title' => '', 'error' => $e->getMessage()]);
    exit;
}

echo json_encode(['success' => true, 'title' => $title]);
?>
