<?php

namespace App\Models;

use App\Core\Database;
use Exception;
use mysqli;

class Product
{
    private mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener todos los productos para la tabla administrativa (Optimizado sin blobs pesados Base64).
     */
    public function getAllProductsTable(): array
    {
        $productos = [];
        if (!$this->db || $this->db->connect_error) {
            return $productos;
        }

        // Se excluyen las columnas Base64 pesadas de la lista principal para carga instantánea
        $sql = "SELECT DISTINCT p.id_producto, p.id_usuario, p.id_categoria, p.id_subcategoria, 
                p.nombre_producto, p.descripcion, p.precio, p.descuento, p.stock, 
                p.sku, p.ubicacion, p.destacado, p.orden_destacado, p.activo,
                p.fecha_creacion, p.fecha_actualizacion,
                (CASE WHEN CHAR_LENGTH(p.imagen_principal) > 10 THEN 1 ELSE 0 END) AS tiene_imagen,
                c.nombre_categoria, s.nombre_subcategoria 
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
                ORDER BY p.id_producto DESC";

        if ($result = $this->db->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
            $result->free();
        }

        return $productos;
    }

    /**
     * Eliminar un producto por su ID.
     */
    public function delete(int $id): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM productos WHERE id_producto = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }

        return false;
    }

    /**
     * Verificar si un número de orden de destacado está ocupado.
     */
    public function isFeaturedOrderOccupied(int $orden, ?int $excludeProductId = null): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        $sql = "SELECT id_producto FROM productos WHERE orden_destacado = ? AND destacado = 1";
        if ($excludeProductId) {
            $sql .= " AND id_producto != ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $orden, $excludeProductId);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $orden);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $occupied = $res->num_rows > 0;
        $stmt->close();

        return $occupied;
    }

    /**
     * Guardar o actualizar un producto.
     */
    public function save(array $data): array
    {
        if (!$this->db || $this->db->connect_error) {
            throw new Exception("Error de conexión a la base de datos", 500);
        }

        $isUpdate = !empty($data['id_producto']);

        if ($isUpdate) {
            $id = (int)$data['id_producto'];
            $sql = "UPDATE productos SET
                        id_categoria = ?,
                        id_subcategoria = ?,
                        nombre_producto = ?,
                        descripcion = ?,
                        precio = ?,
                        descuento = ?,
                        stock = ?,
                        sku = ?,
                        ubicacion = ?,
                        imagen_principal = ?,
                        imagen_secundaria_1 = ?,
                        imagen_secundaria_2 = ?,
                        imagen_secundaria_3 = ?,
                        destacado = ?,
                        orden_destacado = ?,
                        activo = ?
                    WHERE id_producto = ?";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar consulta de actualización: " . $this->db->error, 500);
            }

            $stmt->bind_param(
                "iissddissssssiiii",
                $data['id_categoria'],
                $data['id_subcategoria'],
                $data['nombre_producto'],
                $data['descripcion'],
                $data['precio'],
                $data['descuento'],
                $data['stock'],
                $data['sku'],
                $data['ubicacion'],
                $data['imagen_principal'],
                $data['imagen_secundaria_1'],
                $data['imagen_secundaria_2'],
                $data['imagen_secundaria_3'],
                $data['destacado'],
                $data['orden_destacado'],
                $data['activo'],
                $id
            );
        } else {
            $sql = "INSERT INTO productos (
                        id_usuario, id_categoria, id_subcategoria, nombre_producto, descripcion,
                        precio, descuento, stock, sku, ubicacion,
                        imagen_principal, imagen_secundaria_1, imagen_secundaria_2, imagen_secundaria_3,
                        destacado, orden_destacado, activo
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar consulta de inserción: " . $this->db->error, 500);
            }

            $stmt->bind_param(
                "iiissddissssssiii",
                $data['id_usuario'],
                $data['id_categoria'],
                $data['id_subcategoria'],
                $data['nombre_producto'],
                $data['descripcion'],
                $data['precio'],
                $data['descuento'],
                $data['stock'],
                $data['sku'],
                $data['ubicacion'],
                $data['imagen_principal'],
                $data['imagen_secundaria_1'],
                $data['imagen_secundaria_2'],
                $data['imagen_secundaria_3'],
                $data['destacado'],
                $data['orden_destacado'],
                $data['activo']
            );
        }

        $success = $stmt->execute();
        $insertId = $isUpdate ? (int)$data['id_producto'] : $stmt->insert_id;
        $stmt->close();

        if (!$success) {
            throw new Exception("Error al guardar el producto en la base de datos", 500);
        }

        return [
            'id_producto' => $insertId,
            'accion' => $isUpdate ? 'actualizado' : 'creado'
        ];
    }
}
