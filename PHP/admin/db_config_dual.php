<?php
/**
 * Configuración BD para proyecto NUBE / Local (vendingbox.online)
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');

// Cargar variables de entorno desde .env si existe en la raíz del proyecto
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $key = trim($key);
                $val = trim($val, " \t\n\r\0\x0B\"'");
                if ($key !== '') {
                    putenv("$key=$val");
                    $_ENV[$key] = $val;
                    $_SERVER[$key] = $val;
                }
            }
        }
    }
}

// Credenciales desde variables de entorno con fallback
$DB_USER = getenv('DB_USER') ?: (getenv('DB_NUBE_USER') ?: 'paulobot');
$DB_PASS = getenv('DB_PASS') ?: (getenv('DB_NUBE_PASS') ?: 'paulobot_password');
$DB_NAME = getenv('DB_NAME') ?: (getenv('DB_NUBE_NAME') ?: 'paulobot_store');
$DB_PORT = (int)(getenv('DB_PORT') ?: 5432);
$DB_NUBE_HOST = getenv('DB_NUBE_HOST') ?: 'cpanel.colegos.com.mx';
$DB_NUBE_USER = getenv('DB_NUBE_USER') ?: $DB_USER;
$DB_NUBE_PASS = getenv('DB_NUBE_PASS') ?: $DB_PASS;
$DB_NUBE_NAME = getenv('DB_NUBE_NAME') ?: $DB_NAME;

mysqli_report(MYSQLI_REPORT_OFF);

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';

$conn = null;
try {
    $conn = @new mysqli($dbHost, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);
} catch (\Throwable $e) {
    $conn = null;
}

if (!$conn || $conn->connect_error) {
    try {
        $conn = @new mysqli($DB_NUBE_HOST, $DB_NUBE_USER, $DB_NUBE_PASS, $DB_NUBE_NAME, $DB_PORT);
    } catch (\Throwable $e2) {
        $conn = null;
    }
}

if ($conn && !$conn->connect_error) {
    $conn->set_charset('utf8mb4');
} else {
    $errMsg = $conn ? $conn->connect_error : 'No se pudo conectar a la base de datos local ni remota.';
    error_log("ERROR BD: " . $errMsg);
}
