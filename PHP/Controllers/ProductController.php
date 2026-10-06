<?php

namespace App\Controllers;

use App\Models\Category;
use App\Models\Product;
use Exception;

class ProductController
{
    private Category $categoryModel;
    private Product $productModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
        $this->productModel = new Product();
    }

    /**
     * Datos necesarios para renderizar el formulario de creación de producto.
     */
    public function getCreateViewData(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
            header('Location: login.php');
            exit();
        }

        $categories = $this->categoryModel->getCategoriesWithSubcategories();

        return [
            'categories' => $categories,
            'userId' => $_SESSION['uid'] ?? 1
        ];
    }

    /**
     * Datos necesarios para renderizar la tabla administrativa de productos.
     */
    public function getProductsTableViewData(): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
            header('Location: login.php');
            exit();
        }

        $productos = $this->productModel->getAllProductsTable();
        $categories = $this->categoryModel->getCategoriesWithSubcategories();

        return [
            'productos' => $productos,
            'categories' => $categories,
            'totalProductos' => count($productos),
            'totalStock' => array_sum(array_column($productos, 'stock')),
            'productosActivos' => count(array_filter($productos, function ($p) { return (int)$p['activo'] === 1; })),
            'totalCategorias' => count($categories)
        ];
    }

    /**
     * Eliminar un producto.
     */
    public function deleteProduct(int $productId): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
            throw new Exception("No autorizado", 401);
        }

        if ($this->productModel->delete($productId)) {
            return [
                'success' => true,
                'message' => 'Producto eliminado correctamente.'
            ];
        }

        throw new Exception("No se pudo eliminar el producto de la base de datos", 500);
    }

    /**
     * Procesar la solicitud POST para guardar un producto.
     */
    public function saveProduct(array $postData): array
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $required = ['nombre_producto', 'descripcion', 'precio', 'stock', 'id_categoria', 'ubicacion'];
        foreach ($required as $field) {
            if (empty($postData[$field])) {
                throw new Exception("El campo $field es obligatorio", 400);
            }
        }

        $ubicacion = strtoupper(trim($postData['ubicacion']));
        if (!preg_match('/^[A-Z][0-9]$/', $ubicacion)) {
            throw new Exception("Formato de ubicación inválido. Use letra + número (Ej: A1, B2)", 400);
        }

        $imagen_principal = $postData['imagen_principal'] ?? null;
        if (empty($imagen_principal)) {
            throw new Exception("La imagen principal del producto es obligatoria", 400);
        }

        $imagen_secundaria_1 = $postData['imagen_secundaria_1'] ?? $postData['img_secundaria_1_base64'] ?? null;
        $imagen_secundaria_2 = $postData['imagen_secundaria_2'] ?? $postData['img_secundaria_2_base64'] ?? null;
        $imagen_secundaria_3 = $postData['imagen_secundaria_3'] ?? $postData['img_secundaria_3_base64'] ?? null;

        $destacado = isset($postData['destacado']) && ($postData['destacado'] == '1' || $postData['destacado'] == 'on') ? 1 : 0;
        $orden_destacado = null;

        if ($destacado === 1) {
            if (empty($postData['orden_destacado'])) {
                throw new Exception("El número de orden es requerido para productos destacados", 400);
            }
            $orden_destacado = (int)$postData['orden_destacado'];
            if ($orden_destacado < 1) {
                throw new Exception("El orden debe ser un número mayor a 0", 400);
            }

            $productId = !empty($postData['id_producto']) ? (int)$postData['id_producto'] : null;
            if ($this->productModel->isFeaturedOrderOccupied($orden_destacado, $productId)) {
                throw new Exception("El orden $orden_destacado ya está ocupado por otro producto. Elige otro número.", 400);
            }
        }

        $data = [
            'id_producto' => !empty($postData['id_producto']) ? (int)$postData['id_producto'] : null,
            'id_usuario' => $_SESSION['uid'] ?? 1,
            'id_categoria' => (int)$postData['id_categoria'],
            'id_subcategoria' => !empty($postData['id_subcategoria']) ? (int)$postData['id_subcategoria'] : null,
            'nombre_producto' => trim($postData['nombre_producto']),
            'descripcion' => trim($postData['descripcion']),
            'precio' => (float)$postData['precio'],
            'descuento' => isset($postData['descuento']) && $postData['descuento'] !== '' ? (float)$postData['descuento'] : null,
            'stock' => (int)$postData['stock'],
            'sku' => !empty($postData['sku']) ? trim($postData['sku']) : null,
            'ubicacion' => $ubicacion,
            'imagen_principal' => $imagen_principal,
            'imagen_secundaria_1' => $imagen_secundaria_1,
            'imagen_secundaria_2' => $imagen_secundaria_2,
            'imagen_secundaria_3' => $imagen_secundaria_3,
            'destacado' => $destacado,
            'orden_destacado' => $orden_destacado,
            'activo' => isset($postData['activo']) && ($postData['activo'] == '1' || $postData['activo'] == 'on') ? 1 : 0
        ];

        $result = $this->productModel->save($data);

        return [
            'success' => true,
            'message' => 'Producto ' . $result['accion'] . ' exitosamente.',
            'id_producto' => $result['id_producto']
        ];
    }
}
