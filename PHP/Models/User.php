<?php

namespace App\Models;

use App\Core\Database;
use mysqli;

class User
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Buscar un usuario por su correo electrónico.
     */
    public function findByEmail(string $email): ?array
    {
        if (!$this->db || $this->db->connect_error) {
            return null;
        }

        $hasActivo = false;
        $colCheck = $this->db->query("SHOW COLUMNS FROM usuarios LIKE 'activo'");
        if ($colCheck && $colCheck->num_rows > 0) {
            $hasActivo = true;
        }

        $sql = $hasActivo
            ? 'SELECT id, contrasena, tipo_usuario, nombre, activo FROM usuarios WHERE correo = ? LIMIT 1'
            : 'SELECT id, contrasena, tipo_usuario, nombre FROM usuarios WHERE correo = ? LIMIT 1';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();

        if ($hasActivo) {
            $stmt->bind_result($id, $hash, $tipo_usuario, $nombre, $activo);
        } else {
            $stmt->bind_result($id, $hash, $tipo_usuario, $nombre);
            $activo = 1;
        }

        $user = null;
        if ($stmt->fetch()) {
            $user = [
                'id' => (int) $id,
                'contrasena' => (string) $hash,
                'tipo_usuario' => (string) $tipo_usuario,
                'nombre' => (string) $nombre,
                'activo' => (int) $activo
            ];
        }

        $stmt->close();
        return $user;
    }

    /**
     * Crear un nuevo usuario en el sistema.
     */
    public function create(string $name, string $email, string $plainPassword, int $tipoUsuario = 1): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $sql = 'INSERT INTO usuarios (nombre, correo, contrasena, tipo_usuario) VALUES (?, ?, ?, ?)';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('ssss', $name, $email, $hash, $tipoUsuario);
        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }

    /**
     * Actualizar la contraseña de un usuario existente.
     */
    public function updatePassword(int $userId, string $newPlainPassword): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        $newHash = password_hash($newPlainPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE usuarios SET contrasena = ? WHERE id = ?');
        if ($stmt) {
            $stmt->bind_param('si', $newHash, $userId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }

        return false;
    }

    /**
     * Verifica la contraseña probando BCrypt, Texto Plano y MD5 Legacy.
     * Si coincide con MD5 o texto plano, actualiza automáticamente la contraseña a BCrypt.
     */
    public function verifyAndUpgradePassword(int $userId, string $plainPassword, string $storedHash): bool
    {
        $storedHash = trim($storedHash);
        if ($storedHash === '') {
            return false;
        }

        // 1. Algoritmo estándar BCrypt (Password Hash oficial PHP)
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

        // 3. MD5 (Legacy fallback usuarios antiguos 32 caracteres hex)
        if (strlen($storedHash) === 32 && ctype_xdigit($storedHash) && hash_equals($storedHash, md5($plainPassword))) {
            $this->updatePassword($userId, $plainPassword);
            return true;
        }

        return false;
    }
}
