<?php

namespace App\Models;

use App\Core\Database;
use mysqli;

class Category
{
    private mysqli $db;

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

        $sql = "SELECT c.id_categoria, c.nombre_categoria, s.id_subcategoria, s.nombre_subcategoria
                FROM categorias c
                LEFT JOIN subcategorias s ON c.id_categoria = s.id_categoria
                ORDER BY c.id_categoria, s.id_subcategoria";

        if ($res = $this->db->query($sql)) {
            while ($row = $res->fetch_assoc()) {
                $cat_id = (int)$row['id_categoria'];
                if (!isset($categorias[$cat_id])) {
                    $categorias[$cat_id] = [
                        'id' => $cat_id,
                        'nombre' => $row['nombre_categoria'],
                        'subcategorias' => []
                    ];
                }
                if ($row['id_subcategoria']) {
                    $categorias[$cat_id]['subcategorias'][] = [
                        'id' => (int)$row['id_subcategoria'],
                        'nombre' => $row['nombre_subcategoria']
                    ];
                }
            }
        }

        return array_values($categorias);
    }
}
