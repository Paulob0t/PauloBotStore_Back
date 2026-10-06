<?php

namespace App\Controllers;

use App\Core\Response;
use App\DTOs\ApiResponse;
use App\DTOs\CreateProductRequest;
use App\DTOs\ProductDto;
use App\DTOs\ProductResponse;
use App\Models\Category;
use App\Models\Product;
use OpenApi\Attributes as OA;

class ProductController
{
    private Product $productModel;
    private Category $categoryModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }

    #[OA\Get(
        path: "/api/v1/products",
        operationId: "getProducts",
        summary: "Listar productos del catálogo",
        description: "Retorna el listado completo de productos registrados con su información de stock, categoría y estado.",
        tags: ["Productos"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de productos",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/ProductDto")
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error al consultar productos",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getAll(): void
    {
        try {
            $products = $this->productModel->getAllProducts();
            Response::json($products, 200);
        } catch (\Throwable $e) {
            Response::error('Error al consultar productos: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/products/featured",
        operationId: "getFeaturedProducts",
        summary: "Listar productos destacados para la tienda",
        description: "Retorna los productos marcados como destacados ordenados por su orden de prioridad para carruseles de la tienda.",
        tags: ["Productos"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de productos destacados",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/ProductDto")
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error al consultar productos destacados",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getFeatured(): void
    {
        try {
            $products = $this->productModel->getFeaturedProducts();
            Response::json($products, 200);
        } catch (\Throwable $e) {
            Response::error('Error al consultar productos destacados: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/products/{id}",
        operationId: "getProductById",
        summary: "Obtener detalle de un producto",
        description: "Retorna el producto con sus imágenes y datos completos.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del producto",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalle del producto",
                content: new OA\JsonContent(ref: "#/components/schemas/ProductDto")
            ),
            new OA\Response(
                response: 404,
                description: "Producto no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getById(int $id): void
    {
        try {
            $product = $this->productModel->findById($id);
            if (!$product) {
                Response::error('Producto no encontrado.', null, 404);
                return;
            }
            Response::json($product, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener producto: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/products/{id}/image",
        operationId: "getProductImage",
        summary: "Obtener imagen optimizada del producto con cache",
        description: "Devuelve el binario de la imagen del producto con cabecera Cache-Control pública para máxima velocidad.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del producto",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "type",
                in: "query",
                required: false,
                description: "Tipo de imagen: main, sec1, sec2, sec3",
                schema: new OA\Schema(type: "string", default: "main")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Imagen del producto"),
            new OA\Response(response: 404, description: "Imagen no encontrada")
        ]
    )]
    public function getImage(int $id): void
    {
        $type = $_GET['type'] ?? 'main';
        $imageData = $this->productModel->getImageData($id, $type);

        Response::applyCorsHeaders();

        if (!empty($imageData)) {
            \App\Services\ImageOptimizer::serveOptimized($imageData, 420);
            return;
        }

        // SVG placeholder fallback
        header("Content-Type: image/svg+xml");
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>';
        exit;
    }

    #[OA\Get(
        path: "/api/v1/products/featured-order/{order}",
        operationId: "checkFeaturedOrder",
        summary: "Verificar disponibilidad de número de orden destacado",
        description: "Verifica si una posición de orden destacado ya se encuentra asignada a otro producto.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "order",
                in: "path",
                required: true,
                description: "Número de orden a validar",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Disponibilidad del orden",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "occupied", type: "boolean", example: false),
                        new OA\Property(property: "order", type: "integer", example: 1)
                    ]
                )
            )
        ]
    )]
    public function checkFeaturedOrder(int $order): void
    {
        $excludeId = isset($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;
        $occupied = $this->productModel->isFeaturedOrderOccupied($order, $excludeId);

        Response::json([
            'occupied' => $occupied,
            'order' => $order
        ], 200);
    }

    #[OA\Patch(
        path: "/api/v1/products/{id}/status",
        operationId: "toggleProductStatus",
        summary: "Alternar estado activo/inactivo del producto",
        description: "Habilita o deshabilita la disponibilidad del producto en el catálogo.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del producto",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Estado actualizado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ProductResponse")
            ),
            new OA\Response(
                response: 500,
                description: "Error al actualizar estado",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function toggleStatus(int $id): void
    {
        $newStatus = $this->productModel->toggleStatus($id);
        if ($newStatus === null) {
            Response::error('No se pudo actualizar el estado del producto.', null, 500);
            return;
        }

        Response::json([
            'success' => true,
            'message' => $newStatus === 1 ? 'Producto activado.' : 'Producto desactivado.',
            'id_producto' => $id
        ], 200);
    }

    #[OA\Delete(
        path: "/api/v1/products/{id}",
        operationId: "deleteProduct",
        summary: "Eliminar un producto",
        description: "Elimina permanentemente un producto del catálogo.",
        tags: ["Productos"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del producto a eliminar",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Producto eliminado correctamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ProductResponse")
            ),
            new OA\Response(
                response: 500,
                description: "Error al eliminar producto",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function delete(int $id): void
    {
        try {
            if ($this->productModel->delete($id)) {
                Response::json([
                    'success' => true,
                    'message' => 'Producto eliminado correctamente.',
                    'id_producto' => $id
                ], 200);
                return;
            }
            Response::error('No se pudo eliminar el producto de la base de datos.', null, 500);
        } catch (\Throwable $e) {
            Response::error('Error al eliminar producto: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/products",
        operationId: "createProduct",
        summary: "Registrar un nuevo producto en el catálogo",
        description: "Crea un producto nuevo con validaciones de negocio, ubicación, categoría y almacenamiento de imágenes.",
        tags: ["Productos"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Datos del nuevo producto",
            content: new OA\JsonContent(ref: "#/components/schemas/CreateProductRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Producto creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ProductResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos requeridos faltantes o formato inválido",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            ),
            new OA\Response(
                response: 500,
                description: "Error interno al guardar en base de datos",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function create(): void
    {
        $input = $this->getJsonInput();

        $nombre = trim($input['nombre_producto'] ?? '');
        $descripcion = trim($input['descripcion'] ?? '');
        $precio = isset($input['precio']) ? (float)$input['precio'] : -1;
        $stock = isset($input['stock']) ? (int)$input['stock'] : -1;
        $idCategoria = isset($input['id_categoria']) ? (int)$input['id_categoria'] : 0;
        $ubicacion = strtoupper(trim($input['ubicacion'] ?? ''));
        $imagenPrincipal = trim($input['imagen_principal'] ?? '');

        if (empty($nombre) || empty($descripcion) || $precio < 0 || $stock < 0 || $idCategoria <= 0 || empty($ubicacion)) {
            Response::error('Por favor completa todos los campos obligatorios.', null, 400);
            return;
        }

        if (!preg_match('/^[A-Z][0-9]$/', $ubicacion)) {
            Response::error('Formato de ubicación inválido. Usa una letra seguida de un número (Ejemplo: A1, B2, C3).', null, 400);
            return;
        }

        if (empty($imagenPrincipal)) {
            Response::error('La imagen principal del producto es obligatoria.', null, 400);
            return;
        }

        $destacado = !empty($input['destacado']) ? 1 : 0;
        $ordenDestacado = null;

        if ($destacado === 1) {
            if (empty($input['orden_destacado']) || (int)$input['orden_destacado'] < 1) {
                Response::error('El número de orden es requerido para productos destacados (mayor a 0).', null, 400);
                return;
            }
            $ordenDestacado = (int)$input['orden_destacado'];
            if ($this->productModel->isFeaturedOrderOccupied($ordenDestacado)) {
                Response::error("El orden de destacado #$ordenDestacado ya se encuentra ocupado por otro producto.", null, 400);
                return;
            }
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $userId = $_SESSION['uid'] ?? 1;

        $data = [
            'id_usuario' => $userId,
            'id_categoria' => $idCategoria,
            'id_subcategoria' => !empty($input['id_subcategoria']) ? (int)$input['id_subcategoria'] : null,
            'nombre_producto' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'descuento' => isset($input['descuento']) && $input['descuento'] !== '' ? (float)$input['descuento'] : null,
            'stock' => $stock,
            'sku' => !empty($input['sku']) ? trim($input['sku']) : null,
            'ubicacion' => $ubicacion,
            'imagen_principal' => $imagenPrincipal,
            'imagen_secundaria_1' => !empty($input['imagen_secundaria_1']) ? $input['imagen_secundaria_1'] : null,
            'imagen_secundaria_2' => !empty($input['imagen_secundaria_2']) ? $input['imagen_secundaria_2'] : null,
            'imagen_secundaria_3' => !empty($input['imagen_secundaria_3']) ? $input['imagen_secundaria_3'] : null,
            'destacado' => $destacado,
            'orden_destacado' => $ordenDestacado,
            'activo' => isset($input['activo']) ? ((bool)$input['activo'] ? 1 : 0) : 1
        ];

        try {
            $result = $this->productModel->save($data);
            Response::json([
                'success' => true,
                'message' => 'Producto registrado exitosamente.',
                'id_producto' => $result['id_producto']
            ], 201);
        } catch (\Throwable $e) {
            Response::error('Error al guardar el producto: ' . $e->getMessage(), null, 500);
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $_POST;
    }
}
