<?php

namespace App\Core;

use mysqli;

class Database
{
    private static ?mysqli $instance = null;

    public static function getConnection(): ?mysqli
    {
        if (self::$instance === null) {
            $configPath = __DIR__ . '/../../admin/db_config_dual.php';
            if (file_exists($configPath)) {
                require_once $configPath;
                /** @var mysqli $conn */
                if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
                    self::$instance = $conn;
                    return self::$instance;
                }
            }

            // Fallback manual connection
            $envPath = __DIR__ . '/../../.env';
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

            mysqli_report(MYSQLI_REPORT_OFF);

            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $user = getenv('DB_USER') ?: 'paulobot';
            $pass = getenv('DB_PASS') ?: 'paulobot_password';
            $name = getenv('DB_NAME') ?: 'paulobot_store';
            $port = (int)(getenv('DB_PORT') ?: 5432);

            $conn = null;
            try {
                $conn = @new mysqli($host, $user, $pass, $name, $port);
            } catch (\Throwable $e) {
                $conn = null;
            }

            if (!$conn || $conn->connect_error) {
                $remoteHost = getenv('DB_NUBE_HOST') ?: 'cpanel.colegos.com.mx';
                $remoteUser = getenv('DB_NUBE_USER') ?: $user;
                $remotePass = getenv('DB_NUBE_PASS') ?: $pass;
                $remoteName = getenv('DB_NUBE_NAME') ?: $name;
                try {
                    $conn = @new mysqli($remoteHost, $remoteUser, $remotePass, $remoteName, $port);
                } catch (\Throwable $e) {
                    $conn = null;
                }
            }

            if ($conn && !$conn->connect_error) {
                $conn->set_charset('utf8mb4');
            }

            self::$instance = $conn;
        }

        return self::$instance;
    }
}
