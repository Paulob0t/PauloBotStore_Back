<?php

namespace App\Controllers;

use App\Core\Response;
use App\DTOs\ApiResponse;
use App\DTOs\CategoryDto;
use App\DTOs\CategoryResponse;
use App\DTOs\CreateCategoryRequest;
use App\DTOs\CreateSubcategoryRequest;
use App\DTOs\SubcategoryDetailDto;
use App\DTOs\UpdateCategoryRequest;
use App\DTOs\UpdateSubcategoryRequest;
use App\Models\Category;
use OpenApi\Attributes as OA;

class CategoryController
{
    private Category $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
    }

    #[OA\Get(
        path: "/api/v1/categories",
        operationId: "getCategories",
        summary: "Obtener listado de categorías con sus subcategorías",
        description: "Retorna todas las categorías registradas con sus subcategorías anidadas para selectors y filtros.",
        tags: ["Categorías"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de categorías",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CategoryDto")
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error al consultar categorías",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getCategories(): void
    {
        try {
            $categories = $this->categoryModel->getCategoriesWithSubcategories();
            Response::json($categories, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener categorías: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/categories/{id}/image",
        operationId: "getCategoryImage",
        summary: "Obtener imagen optimizada de categoría con caché",
        description: "Devuelve el binario de la imagen de la categoría con cabecera Cache-Control pública para rendimiento instantáneo.",
        tags: ["Categorías"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la categoría",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(response: 200, description: "Imagen de la categoría"),
            new OA\Response(response: 404, description: "Imagen no encontrada")
        ]
    )]
    public function getImage(int $id): void
    {
        $imageData = $this->categoryModel->getCategoryImage($id);
        Response::applyCorsHeaders();

        if (!empty($imageData)) {
            \App\Services\ImageOptimizer::serveOptimized($imageData, 420);
            return;
        }

        // SVG placeholder fallback
        header("Content-Type: image/svg+xml");
        header("Cache-Control: public, max-age=86400");
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 24 24" fill="none" stroke="#6366f1" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 17l9.2-9.2M17 17V7.8H7.8"/></svg>';
        exit;
    }

    #[OA\Get(
        path: "/api/v1/subcategories",
        operationId: "getSubcategories",
        summary: "Obtener listado plano de todas las subcategorías",
        description: "Retorna todas las subcategorías con los datos de su categoría padre.",
        tags: ["Categorías"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de subcategorías",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/SubcategoryDetailDto")
                )
            )
        ]
    )]
    public function getSubcategories(): void
    {
        try {
            $subcats = $this->categoryModel->getAllSubcategories();
            Response::json($subcats, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener subcategorías: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/categories",
        operationId: "createCategory",
        summary: "Crear una nueva categoría",
        description: "Registra una categoría nueva y opcionalmente sus subcategorías asociadas.",
        tags: ["Categorías"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateCategoryRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Categoría creada con éxito",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoryResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Nombre de categoría inválido",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function create(): void
    {
        $input = $this->getJsonInput();
        $name = trim($input['nombre_categoria'] ?? '');
        $image = !empty($input['imagen_categoria']) ? $input['imagen_categoria'] : null;
        $subcategories = is_array($input['subcategorias'] ?? null) ? $input['subcategorias'] : [];

        if (empty($name)) {
            Response::error('El nombre de la categoría es obligatorio.', null, 400);
            return;
        }

        try {
            $res = $this->categoryModel->create($name, $image, $subcategories);
            Response::json([
                'success' => true,
                'message' => 'Categoría creada exitosamente.',
                'id_categoria' => $res['id']
            ], 201);
        } catch (\Throwable $e) {
            Response::error('Error al crear la categoría: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Put(
        path: "/api/v1/categories/{id}",
        operationId: "updateCategory",
        summary: "Actualizar una categoría",
        description: "Actualiza el nombre y/o imagen de una categoría existente.",
        tags: ["Categorías"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la categoría a actualizar",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateCategoryRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Categoría actualizada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoryResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos inválidos",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function update(int $id): void
    {
        $input = $this->getJsonInput();
        $name = trim($input['nombre_categoria'] ?? '');
        $image = isset($input['imagen_categoria']) ? $input['imagen_categoria'] : null;

        if (empty($name)) {
            Response::error('El nombre de la categoría es obligatorio.', null, 400);
            return;
        }

        if ($this->categoryModel->update($id, $name, $image)) {
            Response::json([
                'success' => true,
                'message' => 'Categoría actualizada correctamente.',
                'id_categoria' => $id
            ], 200);
            return;
        }

        Response::error('No se pudo actualizar la categoría.', null, 500);
    }

    #[OA\Delete(
        path: "/api/v1/categories/{id}",
        operationId: "deleteCategory",
        summary: "Eliminar una categoría",
        description: "Elimina una categoría y todas sus subcategorías vinculadas.",
        tags: ["Categorías"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la categoría a eliminar",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Categoría eliminada",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoryResponse")
            ),
            new OA\Response(
                response: 500,
                description: "Error al eliminar categoría",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function delete(int $id): void
    {
        if ($this->categoryModel->delete($id)) {
            Response::json([
                'success' => true,
                'message' => 'Categoría eliminada correctamente.',
                'id_categoria' => $id
            ], 200);
            return;
        }

        Response::error('No se pudo eliminar la categoría.', null, 500);
    }

    #[OA\Post(
        path: "/api/v1/categories/{id}/subcategories",
        operationId: "createSubcategory",
        summary: "Agregar subcategoría a una categoría",
        description: "Crea una subcategoría vinculada a la categoría indicada.",
        tags: ["Categorías"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la categoría padre",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateSubcategoryRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Subcategoría creada",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoryResponse")
            )
        ]
    )]
    public function addSubcategory(int $id): void
    {
        $input = $this->getJsonInput();
        $name = trim($input['nombre_subcategoria'] ?? '');
        $image = !empty($input['imagen_subcategoria']) ? $input['imagen_subcategoria'] : null;

        if (empty($name)) {
            Response::error('El nombre de la subcategoría es obligatorio.', null, 400);
            return;
        }

        try {
            $subId = $this->categoryModel->addSubcategory($id, $name, $image);
            Response::json([
                'success' => true,
                'message' => 'Subcategoría agregada exitosamente.',
                'id_categoria' => $id,
                'id_subcategoria' => $subId
            ], 201);
        } catch (\Throwable $e) {
            Response::error('Error al agregar subcategoría: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Put(
        path: "/api/v1/subcategories/{id}",
        operationId: "updateSubcategory",
        summary: "Actualizar una subcategoría",
        tags: ["Categorías"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateSubcategoryRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Subcategoría actualizada",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoryResponse")
            )
        ]
    )]
    public function updateSubcategory(int $id): void
    {
        $input = $this->getJsonInput();
        $name = trim($input['nombre_subcategoria'] ?? '');
        $categoryId = isset($input['id_categoria']) ? (int)$input['id_categoria'] : null;

        if (empty($name)) {
            Response::error('El nombre de la subcategoría es obligatorio.', null, 400);
            return;
        }

        if ($this->categoryModel->updateSubcategory($id, $name, $categoryId)) {
            Response::json([
                'success' => true,
                'message' => 'Subcategoría actualizada correctamente.',
                'id_subcategoria' => $id
            ], 200);
            return;
        }

        Response::error('No se pudo actualizar la subcategoría.', null, 500);
    }

    #[OA\Delete(
        path: "/api/v1/subcategories/{id}",
        operationId: "deleteSubcategory",
        summary: "Eliminar una subcategoría",
        tags: ["Categorías"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Subcategoría eliminada",
                content: new OA\JsonContent(ref: "#/components/schemas/CategoryResponse")
            )
        ]
    )]
    public function deleteSubcategory(int $id): void
    {
        if ($this->categoryModel->deleteSubcategory($id)) {
            Response::json([
                'success' => true,
                'message' => 'Subcategoría eliminada correctamente.',
                'id_subcategoria' => $id
            ], 200);
            return;
        }

        Response::error('No se pudo eliminar la subcategoría.', null, 500);
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
