<?php

namespace App\Core;

use mysqli;
use PDO;
use Throwable;

class Database
{
    private static ?mysqli $mysqliInstance = null;
    private static ?PDO $pdoInstance = null;

    public static function loadEnv(): void
    {
        $paths = [
            __DIR__ . '/../../.env',
            __DIR__ . '/../../../.env'
        ];

        foreach ($paths as $path) {
            if (file_exists($path)) {
                $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines !== false) {
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if ($line === '' || str_starts_with($line, '#')) {
                            continue;
                        }
                        if (str_contains($line, '=')) {
                            [$key, $val] = explode('=', $line, 2);
                            $key = trim($key);
                            $val = trim($val);
                            // Quitar comillas si las tiene
                            $val = trim($val, "\"'");
                            if ($key !== '') {
                                putenv("$key=$val");
                                $_ENV[$key] = $val;
                                $_SERVER[$key] = $val;
                            }
                        }
                    }
                }
                break;
            }
        }
    }

    public static function getConnection(): ?mysqli
    {
        if (self::$mysqliInstance !== null) {
            return self::$mysqliInstance;
        }

        self::loadEnv();

        // Desactivar excepciones automáticas para poder realizar fallback limpio
        mysqli_report(MYSQLI_REPORT_OFF);

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $name = getenv('DB_NAME') ?: 'paulobot_vending';
        $port = (int)(getenv('DB_PORT') ?: 3306);

        $conn = null;

        try {
            $conn = @new mysqli($host, $user, $pass, $name, $port);
        } catch (Throwable $e) {
            $conn = null;
        }

        if (!$conn || $conn->connect_error) {
            $remoteHost = getenv('DB_NUBE_HOST');
            if ($remoteHost) {
                $remoteUser = getenv('DB_NUBE_USER') ?: $user;
                $remotePass = getenv('DB_NUBE_PASS') ?: $pass;
                $remoteName = getenv('DB_NUBE_NAME') ?: $name;
                try {
                    $conn = @new mysqli($remoteHost, $remoteUser, $remotePass, $remoteName, $port);
                } catch (Throwable $e) {
                    $conn = null;
                }
            }
        }

        if ($conn && !$conn->connect_error) {
            $conn->set_charset('utf8mb4');
            self::$mysqliInstance = $conn;
            return self::$mysqliInstance;
        }

        return null;
    }

    public static function getPdoConnection(): ?PDO
    {
        if (self::$pdoInstance !== null) {
            return self::$pdoInstance;
        }

        self::loadEnv();

        $driver = strtolower((string)(getenv('DB_TYPE') ?: 'pgsql'));
        $configuredHost = getenv('DB_HOST') ?: 'db';
        $user = getenv('DB_USER') ?: 'paulobot';
        $pass = getenv('DB_PASS') ?: 'paulobot_password';
        $name = getenv('DB_NAME') ?: 'paulobot_store';
        $port = (int)(getenv('DB_PORT') ?: ($driver === 'pgsql' ? 5432 : 3306));

        $hostsToTry = array_unique([$configuredHost, 'db', 'localhost', '127.0.0.1']);

        foreach ($hostsToTry as $host) {
            try {
                if ($driver === 'pgsql') {
                    $dsn = "pgsql:host=$host;port=$port;dbname=$name;";
                } else {
                    $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";
                }

                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$pdoInstance = $pdo;
                return self::$pdoInstance;
            } catch (Throwable $e) {
                // Continuar probando el siguiente host
            }
        }

        $remoteHost = getenv('DB_NUBE_HOST');
        if ($remoteHost) {
            try {
                $remoteUser = getenv('DB_NUBE_USER') ?: $user;
                $remotePass = getenv('DB_NUBE_PASS') ?: $pass;
                $remoteName = getenv('DB_NUBE_NAME') ?: $name;
                $dsn = ($driver === 'pgsql')
                    ? "pgsql:host=$remoteHost;port=$port;dbname=$remoteName;"
                    : "mysql:host=$remoteHost;port=$port;dbname=$remoteName;charset=utf8mb4";
                $pdo = new PDO($dsn, $remoteUser, $remotePass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                self::$pdoInstance = $pdo;
                return self::$pdoInstance;
            } catch (Throwable $e2) {
                return null;
            }
        }

        return null;
    }
}
