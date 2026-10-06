<?php
/**
 * Configuración BD para proyecto NUBE (vendingbox.online)
 *
 * - En el servidor (cPanel/Linux): MySQL en localhost
 * - En tu PC (XAMPP/Windows probando este proyecto): MySQL remoto de la nube
 */

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Mexico_City');

// Credenciales de la BD en hosting
$DB_USER = 'colegos_vending';
$DB_PASS = 'IfbUK2ClF~bV';
$DB_NAME = 'colegos_vending';
$DB_PORT = 3306;

/**
 * ¿Corre en el servidor de producción o en XAMPP probando la copia nube?
 */
function isCloudServerRuntime(): bool
{
    if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
        return true;
    }

    $host = strtolower($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '');
    if ($host !== '' && strpos($host, 'vendingbox.online') !== false) {
        return true;
    }

    return false;
}

$isOnServer = isCloudServerRuntime();
$dbHost = $isOnServer ? 'localhost' : 'cpanel.colegos.com.mx';

try {
    $conn = new mysqli($dbHost, $DB_USER, $DB_PASS, $DB_NAME, $DB_PORT);

    if ($conn->connect_error) {
        error_log("ERROR BD NUBE ($dbHost): " . $conn->connect_error);
        die(json_encode([
            'error'   => 'Error de conexión a base de datos',
            'details' => $isOnServer
                ? 'No se pudo conectar a la BD del servidor.'
                : 'No se pudo conectar a la BD remota desde tu PC. Verifica internet y que cPanel permita acceso remoto a MySQL.',
            'host'    => $dbHost,
        ]));
    }

    $conn->set_charset('utf8mb4');
} catch (Exception $e) {
    error_log("ERROR BD: " . $e->getMessage());
    die(json_encode([
        'error'   => 'Error de conexión a base de datos',
        'details' => $e->getMessage(),
        'host'    => $dbHost,
    ]));
}

define('USING_DB', $isOnServer ? 'NUBE_CPANEL' : 'NUBE_REMOTA_DESDE_PC');
define('IS_LOCAL', false);

$conn_nube = null;

function getMainDB()
{
    global $conn;
    return $conn;
}

function getBackupDB()
{
    return null;
}

function checkDBStatus()
{
    global $conn, $isOnServer, $dbHost;

    return [
        'ambiente'  => USING_DB,
        'principal' => [
            'connected' => $conn && !$conn->connect_error,
            'host'      => $conn ? ($conn->host_info ?? 'N/A') : 'N/A',
            'usando'    => USING_DB,
        ],
        'respaldo'  => ['connected' => false, 'disponible' => false],
        'is_local'  => false,
        'debug'     => [
            'php_os'      => PHP_OS,
            'http_host'   => $_SERVER['HTTP_HOST'] ?? 'N/A',
            'db_host'     => $dbHost,
            'on_server'   => $isOnServer,
        ],
    ];
}

function hasBackupConnection()
{
    return false;
}

if ($conn) {
    $test = $conn->query('SELECT 1 AS test');
    if ($test) {
        $test->free();
    }
}
