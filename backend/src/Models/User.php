<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use Throwable;

class User
{
    private ?PDO $db;

    public function __construct()
    {
        $this->db = Database::getPdoConnection();
    }

    /**
     * Obtener todos los usuarios registrados en el sistema.
     */
    public function getAll(): array
    {
        $users = [];
        if (!$this->db) {
            return $users;
        }

        try {
            $sql = "SELECT id, nombre, correo, tipo_usuario, activo, created_at 
                    FROM usuarios 
                    ORDER BY id DESC";
            $stmt = $this->db->query($sql);
            if ($stmt) {
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tipoRaw = $row['tipo_usuario'];
                    $tipoLabel = is_numeric($tipoRaw) ? match ((int)$tipoRaw) {
                        0 => 'Super Administrador',
                        1 => 'Administrador',
                        default => 'Operador'
                    } : (string)$tipoRaw;

                    $users[] = [
                        'id' => (int)$row['id'],
                        'nombre' => (string)$row['nombre'],
                        'correo' => (string)$row['correo'],
                        'tipo_usuario' => $tipoRaw,
                        'tipo_usuario_label' => $tipoLabel,
                        'activo' => (int)($row['activo'] ?? 1),
                        'created_at' => $row['created_at'] ?? null
                    ];
                }
            }
        } catch (Throwable $e) {
            // fallback
        }

        return $users;
    }

    /**
     * Buscar un usuario por su correo electrónico.
     */
    public function findByEmail(string $email): ?array
    {
        if (!$this->db) {
            return null;
        }

        try {
            $sql = "SELECT id, contrasena, tipo_usuario, nombre, activo, correo FROM usuarios WHERE LOWER(correo) = LOWER(?) LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Buscar un usuario por su ID.
     */
    public function findById(int $id): ?array
    {
        if (!$this->db) {
            return null;
        }

        try {
            $sql = "SELECT id, tipo_usuario, nombre, correo, activo FROM usuarios WHERE id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            return $user ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Crear un nuevo usuario en el sistema.
     */
    public function create(string $name, string $email, string $plainPassword, string|int $tipoUsuario = 'Administrador', int $activo = 1): int
    {
        if (!$this->db) {
            return 0;
        }

        try {
            $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
            $sql = 'INSERT INTO usuarios (nombre, correo, contrasena, tipo_usuario, activo) VALUES (?, ?, ?, ?, ?)';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$name, $email, $hash, (string)$tipoUsuario, $activo]);
            return (int)$this->db->lastInsertId();
        } catch (Throwable $e) {
            return 0;
        }
    }

    /**
     * Actualizar estado activo/inactivo de un usuario.
     */
    public function updateStatus(int $userId, int $active): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
            return $stmt->execute([$active, $userId]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Actualizar la contraseña de un usuario existente.
     */
    public function updatePassword(int $userId, string $newPlainPassword): bool
    {
        if (!$this->db) {
            return false;
        }

        try {
            $newHash = password_hash($newPlainPassword, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare('UPDATE usuarios SET contrasena = ? WHERE id = ?');
            return $stmt->execute([$newHash, $userId]);
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Verifica la contraseña probando BCrypt, Texto Plano y MD5 Legacy.
     */
    public function verifyAndUpgradePassword(int $userId, string $plainPassword, string $storedHash): bool
    {
        $storedHash = trim($storedHash);
        if ($storedHash === '') {
            return false;
        }

        // 1. Algoritmo estándar BCrypt / Argon2
        if (password_get_info($storedHash)['algo'] !== 0) {
            if (password_verify($plainPassword, $storedHash)) {
                return true;
            }
        }

        // 2. Texto plano (Legacy fallback)
        if (hash_equals($storedHash, $plainPassword)) {
            $this->updatePassword($userId, $plainPassword);
            return true;
        }

        // 3. MD5 (Legacy fallback usuarios antiguos)
        if (strlen($storedHash) === 32 && ctype_xdigit($storedHash) && hash_equals($storedHash, md5($plainPassword))) {
            $this->updatePassword($userId, $plainPassword);
            return true;
        }

        return false;
    }
}
