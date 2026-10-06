<?php

namespace App\Models;

use App\Core\Database;
use Exception;
use mysqli;

class Category
{
    private ?mysqli $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Obtener todas las categorías con sus subcategorías agrupadas.
     */
    public function getCategoriesWithSubcategories(): array
    {
        $categorias = [];
        if (!$this->db || $this->db->connect_error) {
            return $categorias;
        }

        $sql = "SELECT c.id_categoria, c.nombre_categoria, 
                (CASE WHEN CHAR_LENGTH(c.imagen_categoria) > 10 THEN 1 ELSE 0 END) AS tiene_imagen,
                s.id_subcategoria, s.nombre_subcategoria
                FROM categorias c
                LEFT JOIN subcategorias s ON c.id_categoria = s.id_categoria
                ORDER BY c.nombre_categoria ASC, s.nombre_subcategoria ASC";

        if ($res = $this->db->query($sql)) {
            while ($row = $res->fetch_assoc()) {
                $cat_id = (int)$row['id_categoria'];
                if (!isset($categorias[$cat_id])) {
                    $categorias[$cat_id] = [
                        'id' => $cat_id,
                        'nombre' => (string)$row['nombre_categoria'],
                        'tiene_imagen' => (int)$row['tiene_imagen'],
                        'subcategorias' => []
                    ];
                }
                if (!empty($row['id_subcategoria'])) {
                    $categorias[$cat_id]['subcategorias'][] = [
                        'id' => (int)$row['id_subcategoria'],
                        'nombre' => (string)$row['nombre_subcategoria']
                    ];
                }
            }
        }

        return array_values($categorias);
    }

    /**
     * Obtener imagen binaria de una categoría.
     */
    public function getCategoryImage(int $id): ?string
    {
        if (!$this->db || $this->db->connect_error) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT imagen_categoria FROM categorias WHERE id_categoria = ? LIMIT 1");
        if (!$stmt) return null;

        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->bind_result($imageData);
        $result = $stmt->fetch() ? $imageData : null;
        $stmt->close();

        return $result;
    }

    /**
     * Obtener todas las subcategorías con su nombre de categoría padre.
     */
    public function getAllSubcategories(): array
    {
        $subcats = [];
        if (!$this->db || $this->db->connect_error) {
            return $subcats;
        }

        $sql = "SELECT s.id_subcategoria, s.id_categoria, s.nombre_subcategoria, s.imagen_subcategoria, c.nombre_categoria
                FROM subcategorias s
                JOIN categorias c ON s.id_categoria = c.id_categoria
                ORDER BY c.nombre_categoria ASC, s.nombre_subcategoria ASC";

        if ($res = $this->db->query($sql)) {
            while ($row = $res->fetch_assoc()) {
                $subcats[] = [
                    'id_subcategoria' => (int)$row['id_subcategoria'],
                    'id_categoria' => (int)$row['id_categoria'],
                    'nombre_subcategoria' => (string)$row['nombre_subcategoria'],
                    'nombre_categoria' => (string)$row['nombre_categoria'],
                    'imagen_subcategoria' => $row['imagen_subcategoria'] ?: null
                ];
            }
        }

        return $subcats;
    }

    /**
     * Crear una nueva categoría con subcategorías opcionales.
     */
    public function create(string $name, ?string $image = null, array $subcategories = []): array
    {
        if (!$this->db || $this->db->connect_error) {
            throw new Exception("Error de conexión a la base de datos", 500);
        }

        $stmt = $this->db->prepare("INSERT INTO categorias (nombre_categoria, imagen_categoria) VALUES (?, ?)");
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $this->db->error, 500);
        }

        $stmt->bind_param("ss", $name, $image);
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar categoría: " . $stmt->error, 500);
        }

        $categoryId = (int)$stmt->insert_id;
        $stmt->close();

        $subcatsCreated = [];
        if (!empty($subcategories)) {
            $subStmt = $this->db->prepare("INSERT INTO subcategorias (id_categoria, nombre_subcategoria) VALUES (?, ?)");
            foreach ($subcategories as $subName) {
                $subName = trim($subName);
                if ($subName === '') continue;
                $subStmt->bind_param("is", $categoryId, $subName);
                if ($subStmt->execute()) {
                    $subcatsCreated[] = [
                        'id' => (int)$subStmt->insert_id,
                        'nombre' => $subName
                    ];
                }
            }
            $subStmt->close();
        }

        return [
            'id' => $categoryId,
            'nombre' => $name,
            'subcategorias' => $subcatsCreated
        ];
    }

    /**
     * Actualizar una categoría existente.
     */
    public function update(int $id, string $name, ?string $image = null): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        if ($image !== null) {
            $stmt = $this->db->prepare("UPDATE categorias SET nombre_categoria = ?, imagen_categoria = ? WHERE id_categoria = ?");
            $stmt->bind_param("ssi", $name, $image, $id);
        } else {
            $stmt = $this->db->prepare("UPDATE categorias SET nombre_categoria = ? WHERE id_categoria = ?");
            $stmt->bind_param("si", $name, $id);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Eliminar una categoría y sus subcategorías.
     */
    public function delete(int $id): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        // Eliminar subcategorías primero
        $stmtSub = $this->db->prepare("DELETE FROM subcategorias WHERE id_categoria = ?");
        if ($stmtSub) {
            $stmtSub->bind_param("i", $id);
            $stmtSub->execute();
            $stmtSub->close();
        }

        // Eliminar categoría
        $stmt = $this->db->prepare("DELETE FROM categorias WHERE id_categoria = ?");
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }

        return false;
    }

    /**
     * Añadir subcategoría a una categoría.
     */
    public function addSubcategory(int $categoryId, string $name, ?string $image = null): int
    {
        if (!$this->db || $this->db->connect_error) {
            throw new Exception("Error de conexión a la base de datos", 500);
        }

        if ($image !== null) {
            $stmt = $this->db->prepare("INSERT INTO subcategorias (id_categoria, nombre_subcategoria, imagen_subcategoria) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $categoryId, $name, $image);
        } else {
            $stmt = $this->db->prepare("INSERT INTO subcategorias (id_categoria, nombre_subcategoria) VALUES (?, ?)");
            $stmt->bind_param("is", $categoryId, $name);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error al insertar subcategoría: " . $stmt->error, 500);
        }

        $id = (int)$stmt->insert_id;
        $stmt->close();
        return $id;
    }

    /**
     * Actualizar una subcategoría.
     */
    public function updateSubcategory(int $subcategoryId, string $name, ?int $categoryId = null): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        if ($categoryId !== null && $categoryId > 0) {
            $stmt = $this->db->prepare("UPDATE subcategorias SET nombre_subcategoria = ?, id_categoria = ? WHERE id_subcategoria = ?");
            $stmt->bind_param("sii", $name, $categoryId, $subcategoryId);
        } else {
            $stmt = $this->db->prepare("UPDATE subcategorias SET nombre_subcategoria = ? WHERE id_subcategoria = ?");
            $stmt->bind_param("si", $name, $subcategoryId);
        }

        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }

    /**
     * Eliminar una subcategoría.
     */
    public function deleteSubcategory(int $subcategoryId): bool
    {
        if (!$this->db || $this->db->connect_error) {
            return false;
        }

        $stmt = $this->db->prepare("DELETE FROM subcategorias WHERE id_subcategoria = ?");
        if ($stmt) {
            $stmt->bind_param("i", $subcategoryId);
            $success = $stmt->execute();
            $stmt->close();
            return $success;
        }

        return false;
    }
}
