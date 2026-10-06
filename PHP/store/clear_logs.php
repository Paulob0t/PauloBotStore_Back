<?php
/**
 * Limpia el archivo de logs de Apache
 */
$logFile = 'C:/xampp/apache/logs/error.log';

if (file_exists($logFile)) {
    file_put_contents($logFile, '');
    echo json_encode(['success' => true, 'mensaje' => 'Logs limpiados']);
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Archivo no encontrado']);
}
?>
