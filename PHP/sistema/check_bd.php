<?php
/**
 * Script para verificar qué BD se está usando
 */
require_once 'admin/dist/db_config_dual.php';

header('Content-Type: application/json');

$status = checkDBStatus();

echo json_encode([
    'usando_bd' => USING_DB,
    'es_local' => IS_LOCAL,
    'detalles' => $status,
    'mensaje' => IS_LOCAL ? 
        '🏠 Estás usando BD LOCAL (XAMPP - vending)' : 
        '☁️ Estás usando BD NUBE (cpanel.colegos.com.mx - colegos_vending)'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
