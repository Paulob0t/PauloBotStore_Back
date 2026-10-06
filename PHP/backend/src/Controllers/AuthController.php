<?php

namespace App\Controllers;

use App\Core\Response;
use App\DTOs\ApiResponse;
use App\DTOs\LoginRequest;
use App\DTOs\LoginResponse;
use App\DTOs\RegisterRequest;
use App\DTOs\UserDto;
use App\Models\User;
use OpenApi\Attributes as OA;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    #[OA\Post(
        path: "/api/v1/auth/login",
        operationId: "login",
        summary: "Iniciar sesión de usuario",
        description: "Valida las credenciales (correo y contraseña), soporta hashing seguro y actualización automática de hashes legados.",
        tags: ["Autenticación"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Credenciales de acceso",
            content: new OA\JsonContent(ref: "#/components/schemas/LoginRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Autenticación exitosa",
                content: new OA\JsonContent(ref: "#/components/schemas/LoginResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos faltantes o formato de correo inválido",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            ),
            new OA\Response(
                response: 401,
                description: "Credenciales incorrectas o usuario no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            ),
            new OA\Response(
                response: 403,
                description: "Usuario inactivo o suspendido",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function login(): void
    {
        $input = $this->getJsonInput();
        $email = filter_var(trim($input['correo'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = trim($input['contrasena'] ?? '');

        if (empty($email) || empty($password)) {
            Response::error('Por favor, ingresa tu correo y contraseña.', null, 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('El formato del correo electrónico no es válido.', null, 400);
            return;
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            Response::error('El correo no se encuentra registrado en el sistema.', null, 401);
            return;
        }

        if (isset($user['activo']) && (int) $user['activo'] !== 1) {
            Response::error('Tu usuario está inactivo. Contacta al administrador.', null, 403);
            return;
        }

        if ($this->userModel->verifyAndUpgradePassword($user['id'], $password, $user['contrasena'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['uid'] = $user['id'];
            $_SESSION['login'] = true;
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
            $_SESSION['nombre_usuario'] = $user['nombre'];
            $_SESSION['correo_usuario'] = $user['correo'] ?? $email;

            // Generar un token de sesión de referencia para el SPA
            $token = bin2hex(random_bytes(32));
            $_SESSION['api_token'] = $token;

            $userDto = [
                'id' => (int) $user['id'],
                'nombre' => (string) $user['nombre'],
                'correo' => (string) ($user['correo'] ?? $email),
                'tipo_usuario' => (string) $user['tipo_usuario'],
                'activo' => (int) ($user['activo'] ?? 1)
            ];

            Response::json([
                'success' => true,
                'message' => '¡Bienvenido de nuevo, ' . htmlspecialchars($user['nombre']) . '!',
                'token' => $token,
                'user' => $userDto
            ], 200);
            return;
        }

        Response::error('Contraseña incorrecta. Inténtalo nuevamente.', null, 401);
    }

    #[OA\Get(
        path: "/api/v1/auth/me",
        operationId: "getCurrentUser",
        summary: "Obtener usuario autenticado actual",
        description: "Retorna el perfil del usuario autenticado en la sesión activa.",
        tags: ["Autenticación"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Usuario autenticado",
                content: new OA\JsonContent(ref: "#/components/schemas/UserDto")
            ),
            new OA\Response(
                response: 401,
                description: "No autenticado",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function me(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['login']) || empty($_SESSION['uid'])) {
            Response::error('No hay una sesión activa.', null, 401);
            return;
        }

        $userId = (int) $_SESSION['uid'];
        $user = $this->userModel->findById($userId);

        if (!$user) {
            Response::error('Usuario no encontrado.', null, 404);
            return;
        }

        $userDto = [
            'id' => (int) $user['id'],
            'nombre' => (string) $user['nombre'],
            'correo' => (string) $user['correo'],
            'tipo_usuario' => (string) $user['tipo_usuario'],
            'activo' => (int) ($user['activo'] ?? 1)
        ];

        Response::json([
            'success' => true,
            'user' => $userDto
        ], 200);
    }

    #[OA\Post(
        path: "/api/v1/auth/register",
        operationId: "register",
        summary: "Registrar un nuevo usuario",
        description: "Crea una nueva cuenta de usuario en el sistema.",
        tags: ["Autenticación"],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Datos para el registro",
            content: new OA\JsonContent(ref: "#/components/schemas/RegisterRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Usuario registrado con éxito",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos inválidos o contraseñas no coinciden",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            ),
            new OA\Response(
                response: 409,
                description: "El correo ya se encuentra registrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function register(): void
    {
        $input = $this->getJsonInput();
        $name = trim(strip_tags($input['nombre'] ?? ''));
        $email = filter_var(trim($input['correo'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = trim($input['contrasena'] ?? '');
        $confirmPassword = trim($input['confirmar_contrasena'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            Response::error('Todos los campos son obligatorios.', null, 400);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('El formato del correo electrónico no es válido.', null, 400);
            return;
        }

        if (strlen($password) < 6) {
            Response::error('La contraseña debe tener al menos 6 caracteres.', null, 400);
            return;
        }

        if ($password !== $confirmPassword) {
            Response::error('Las contraseñas no coinciden.', null, 400);
            return;
        }

        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser) {
            Response::error('El correo electrónico ya se encuentra registrado.', null, 409);
            return;
        }

        if ($this->userModel->create($name, $email, $password)) {
            Response::success('Usuario registrado exitosamente.', null, 201);
            return;
        }

        Response::error('Error al registrar el usuario en la base de datos.', null, 500);
    }

    #[OA\Post(
        path: "/api/v1/auth/logout",
        operationId: "logout",
        summary: "Cerrar sesión activa",
        description: "Destruye la sesión del usuario actual.",
        tags: ["Autenticación"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Sesión cerrada correctamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        Response::success('Sesión cerrada correctamente.');
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
