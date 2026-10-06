<?php

namespace App\Models;

use App\Core\Database;
use Exception;
use mysqli;

class Product
{
    private ?mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener todos los productos para la tabla administrativa.
     */
    public function getAllProducts(): array
    {
        $productos = [];
        if (!$this->db || $this->db->connect_error) {
            return $productos;
        }

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
                $productos[] = [
                    'id_producto' => (int)$row['id_producto'],
                    'id_categoria' => $row['id_categoria'] ? (int)$row['id_categoria'] : null,
                    'id_subcategoria' => $row['id_subcategoria'] ? (int)$row['id_subcategoria'] : null,
                    'nombre_categoria' => $row['nombre_categoria'] ? (string)$row['nombre_categoria'] : null,
                    'nombre_subcategoria' => $row['nombre_subcategoria'] ? (string)$row['nombre_subcategoria'] : null,
                    'nombre_producto' => (string)$row['nombre_producto'],
                    'descripcion' => $row['descripcion'] ? (string)$row['descripcion'] : null,
                    'precio' => (float)$row['precio'],
                    'descuento' => $row['descuento'] !== null ? (float)$row['descuento'] : null,
                    'stock' => (int)$row['stock'],
                    'sku' => $row['sku'] ? (string)$row['sku'] : null,
                    'ubicacion' => (string)$row['ubicacion'],
                    'destacado' => (int)$row['destacado'],
                    'orden_destacado' => $row['orden_destacado'] !== null ? (int)$row['orden_destacado'] : null,
                    'activo' => (int)$row['activo'],
                    'tiene_imagen' => (int)$row['tiene_imagen']
                ];
            }
            $result->free();
        }

        return $productos;
    }

    /**
     * Obtener un producto por su ID (incluyendo imágenes).
     */
    public function findById(int $id): ?array
    {
        if (!$this->db || $this->db->connect_error) {
            return null;
        }

        $sql = "SELECT p.*, c.nombre_categoria, s.nombre_subcategoria
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
                WHERE p.id_producto = ? LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $stmt->close();

        return $product ?: null;
    }

    /**
     * Obtener datos de imagen de un producto para streaming con cache.
     */
    public function getImageData(int $id, string $type = 'main'): ?string
    {
        if (!$this->db || $this->db->connect_error) {
            return null;
        }

        $column = 'imagen_principal';
        if ($type === 'sec1') $column = 'imagen_secundaria_1';
        if ($type === 'sec2') $column = 'imagen_secundaria_2';
        if ($type === 'sec3') $column = 'imagen_secundaria_3';

        $stmt = $this->db->prepare("SELECT $column FROM productos WHERE id_producto = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->bind_result($img);
            if ($stmt->fetch()) {
                $stmt->close();
                return $img;
            }
            $stmt->close();
        }

        return null;
    }

    /**
     * Alternar estado activo / inactivo de un producto.
     */
    public function toggleStatus(int $id): ?int
    {
        if (!$this->db || $this->db->connect_error) {
            return null;
        }

        $stmt = $this->db->prepare("UPDATE productos SET activo = 1 - activo WHERE id_producto = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            $check = $this->db->prepare("SELECT activo FROM productos WHERE id_producto = ? LIMIT 1");
            if ($check) {
                $check->bind_param("i", $id);
                $check->execute();
                $check->bind_result($newStatus);
                $check->fetch();
                $check->close();
                return (int)$newStatus;
            }
        }

        return null;
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

        if (!$stmt->execute()) {
            throw new Exception("Error al ejecutar la operación en BD: " . $stmt->error, 500);
        }

        $insertedId = $isUpdate ? $data['id_producto'] : $stmt->insert_id;
        $stmt->close();

        return [
            'id_producto' => (int)$insertedId,
            'accion' => $isUpdate ? 'actualizado' : 'creado'
        ];
    }

    /**
     * Eliminar producto.
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
     * Obtener productos destacados ordenados por su orden de destacado.
     */
    public function getFeaturedProducts(): array
    {
        $productos = [];
        if (!$this->db || $this->db->connect_error) {
            return $productos;
        }

        $sql = "SELECT DISTINCT p.id_producto, p.id_categoria, p.id_subcategoria, 
                p.nombre_producto, p.descripcion, p.precio, p.descuento, p.stock, 
                p.sku, p.ubicacion, p.destacado, p.orden_destacado, p.activo,
                (CASE WHEN CHAR_LENGTH(p.imagen_principal) > 10 THEN 1 ELSE 0 END) AS tiene_imagen,
                c.nombre_categoria, s.nombre_subcategoria 
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
                WHERE p.activo = 1 AND p.destacado = 1
                ORDER BY COALESCE(p.orden_destacado, 999) ASC, p.id_producto DESC";

        if ($result = $this->db->query($sql)) {
            while ($row = $result->fetch_assoc()) {
                $productos[] = [
                    'id_producto' => (int)$row['id_producto'],
                    'id_categoria' => $row['id_categoria'] ? (int)$row['id_categoria'] : null,
                    'id_subcategoria' => $row['id_subcategoria'] ? (int)$row['id_subcategoria'] : null,
                    'nombre_categoria' => $row['nombre_categoria'] ? (string)$row['nombre_categoria'] : null,
                    'nombre_subcategoria' => $row['nombre_subcategoria'] ? (string)$row['nombre_subcategoria'] : null,
                    'nombre_producto' => (string)$row['nombre_producto'],
                    'descripcion' => $row['descripcion'] ? (string)$row['descripcion'] : null,
                    'precio' => (float)$row['precio'],
                    'descuento' => $row['descuento'] !== null ? (float)$row['descuento'] : null,
                    'stock' => (int)$row['stock'],
                    'sku' => $row['sku'] ? (string)$row['sku'] : null,
                    'ubicacion' => (string)$row['ubicacion'],
                    'destacado' => (int)$row['destacado'],
                    'orden_destacado' => $row['orden_destacado'] !== null ? (int)$row['orden_destacado'] : null,
                    'activo' => (int)$row['activo'],
                    'tiene_imagen' => (int)$row['tiene_imagen']
                ];
            }
            $result->free();
        }

        return $productos;
    }
}
