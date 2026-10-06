<?php

namespace App\Models;

use App\Core\Database;
use Exception;
use mysqli;

class CompanyConfig
{
    private ?mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function get(): array
    {
        $default = [
            'nombre_empresa' => 'PauloBot Store',
            'direccion' => '',
            'ciudad' => '',
            'estado' => '',
            'telefono' => '',
            'rfc' => '',
            'email' => '',
            'website' => 'https://paulobot.store',
            'mensaje_ticket' => '¡GRACIAS POR SU COMPRA!'
        ];

        if (!$this->db || $this->db->connect_error) {
            return $default;
        }

        $res = $this->db->query("SELECT nombre_empresa, direccion, ciudad, estado, telefono, rfc, email, website, mensaje_ticket 
                                 FROM configuracion_empresa WHERE activo = 1 LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return [
                'nombre_empresa' => (string)($row['nombre_empresa'] ?: 'PauloBot Store'),
                'direccion' => $row['direccion'] ?: null,
                'ciudad' => $row['ciudad'] ?: null,
                'estado' => $row['estado'] ?: null,
                'telefono' => $row['telefono'] ?: null,
                'rfc' => $row['rfc'] ?: null,
                'email' => $row['email'] ?: null,
                'website' => $row['website'] ?: null,
                'mensaje_ticket' => $row['mensaje_ticket'] ?: '¡GRACIAS POR SU COMPRA!'
            ];
        }

        return $default;
    }

    public function update(array $data): bool
    {
        if (!$this->db || $this->db->connect_error) {
            throw new Exception("Error de conexión con la base de datos.");
        }

        $nombre = trim($data['nombre_empresa'] ?? 'PauloBot Store');
        $direccion = trim($data['direccion'] ?? '');
        $ciudad = trim($data['ciudad'] ?? '');
        $estado = trim($data['estado'] ?? '');
        $telefono = trim($data['telefono'] ?? '');
        $rfc = strtoupper(trim($data['rfc'] ?? ''));
        $email = trim($data['email'] ?? '');
        $website = trim($data['website'] ?? '');
        $mensajeTicket = trim($data['mensaje_ticket'] ?? '¡GRACIAS POR SU COMPRA!');

        $sql = "INSERT INTO configuracion_empresa 
                (id, nombre_empresa, direccion, ciudad, estado, telefono, rfc, email, website, mensaje_ticket, activo, fecha_actualizacion)
                VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    nombre_empresa = VALUES(nombre_empresa),
                    direccion = VALUES(direccion),
                    ciudad = VALUES(ciudad),
                    estado = VALUES(estado),
                    telefono = VALUES(telefono),
                    rfc = VALUES(rfc),
                    email = VALUES(email),
                    website = VALUES(website),
                    mensaje_ticket = VALUES(mensaje_ticket),
                    activo = 1,
                    fecha_actualizacion = NOW()";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $this->db->error);
        }

        $stmt->bind_param(
            "sssssssss",
            $nombre,
            $direccion,
            $ciudad,
            $estado,
            $telefono,
            $rfc,
            $email,
            $website,
            $mensajeTicket
        );

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
