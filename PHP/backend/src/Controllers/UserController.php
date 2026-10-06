<?php

namespace App\Controllers;

use App\Core\Response;
use App\DTOs\ApiResponse;
use App\DTOs\CreateUserRequest;
use App\DTOs\UpdateUserStatusRequest;
use App\DTOs\UserItemDto;
use App\Models\User;
use OpenApi\Attributes as OA;

class UserController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    #[OA\Get(
        path: "/api/v1/users",
        operationId: "getUsers",
        summary: "Listar todos los usuarios del sistema",
        description: "Retorna el catálogo completo de usuarios registrados con sus roles y estados.",
        tags: ["Usuarios"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de usuarios",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/UserItemDto")
                )
            ),
            new OA\Response(
                response: 500,
                description: "Error al consultar usuarios",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getAll(): void
    {
        try {
            $users = $this->userModel->getAll();
            Response::json($users, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener usuarios: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/users",
        operationId: "createUser",
        summary: "Crear / Registrar un nuevo usuario",
        description: "Crea una nueva cuenta de usuario con rol y contraseña cifrada con BCrypt.",
        tags: ["Usuarios"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateUserRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Usuario creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos inválidos",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            ),
            new OA\Response(
                response: 409,
                description: "El correo ya está registrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function create(): void
    {
        $input = $this->getJsonInput();
        $name = trim(strip_tags((string)($input['nombre'] ?? '')));
        $email = filter_var(trim((string)($input['correo'] ?? '')), FILTER_SANITIZE_EMAIL);
        $password = trim((string)($input['contrasena'] ?? ''));
        $tipoUsuario = isset($input['tipo_usuario']) ? (int)$input['tipo_usuario'] : 1;
        $activo = isset($input['activo']) ? (int)$input['activo'] : 1;

        if (empty($name) || empty($email) || empty($password)) {
            Response::error('El nombre, correo y contraseña son obligatorios.', null, 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('El correo electrónico no tiene un formato válido.', null, 400);
            return;
        }

        if (strlen($password) < 6) {
            Response::error('La contraseña debe contener al menos 6 caracteres.', null, 400);
            return;
        }

        $existing = $this->userModel->findByEmail($email);
        if ($existing) {
            Response::error('El correo electrónico ya se encuentra registrado por otro usuario.', null, 409);
            return;
        }

        $userId = $this->userModel->create($name, $email, $password, $tipoUsuario, $activo);
        if ($userId > 0) {
            Response::success('Usuario creado exitosamente con ID #' . $userId, ['id' => $userId], 201);
            return;
        }

        Response::error('No se pudo crear el usuario en la base de datos.', null, 500);
    }

    #[OA\Put(
        path: "/api/v1/users/{id}/status",
        operationId: "updateUserStatus",
        summary: "Cambiar estado de un usuario (Activar / Suspender)",
        tags: ["Usuarios"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del usuario",
                schema: new OA\Schema(type: "integer")
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateUserStatusRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Estado actualizado",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function updateStatus(int $id): void
    {
        $input = $this->getJsonInput();
        $active = isset($input['activo']) ? (int)$input['activo'] : 1;

        if ($this->userModel->updateStatus($id, $active)) {
            Response::success('Estado de usuario actualizado correctamente.', ['id' => $id, 'activo' => $active]);
            return;
        }

        Response::error('No se pudo actualizar el estado del usuario.', null, 500);
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return $_POST;
    }
}
