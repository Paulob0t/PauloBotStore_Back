<?php

namespace App\Controllers;

use App\Models\User;

class AuthController
{
    private User $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    /**
     * Procesar la solicitud de inicio de sesión.
     */
    public function login(string $email, string $password): array
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        $password = trim($password);

        if (empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Por favor, ingresa tu correo y contraseña.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El formato del correo electrónico no es válido.'
            ];
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return [
                'success' => false,
                'message' => 'El correo no se encuentra registrado en el sistema.'
            ];
        }

        if (isset($user['activo']) && (int) $user['activo'] !== 1) {
            return [
                'success' => false,
                'message' => 'Tu usuario está inactivo. Contacta al administrador.'
            ];
        }

        if ($this->userModel->verifyAndUpgradePassword($user['id'], $password, $user['contrasena'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['uid'] = $user['id'];
            $_SESSION['login'] = true;
            $_SESSION['tipo_usuario'] = $user['tipo_usuario'];
            $_SESSION['nombre_usuario'] = $user['nombre'];

            return [
                'success' => true,
                'message' => '¡Bienvenido de nuevo, ' . htmlspecialchars($user['nombre']) . '!'
            ];
        }

        return [
            'success' => false,
            'message' => 'Contraseña incorrecta. Inténtalo nuevamente.'
        ];
    }

    /**
     * Registrar un nuevo usuario en el sistema.
     */
    public function register(string $name, string $email, string $password, string $confirmPassword): array
    {
        $name = trim(strip_tags($name));
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        $password = trim($password);
        $confirmPassword = trim($confirmPassword);

        if (empty($name) || empty($email) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Todos los campos son obligatorios.'
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'El formato del correo electrónico no es válido.'
            ];
        }

        if (strlen($password) < 6) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 6 caracteres.'
            ];
        }

        if ($password !== $confirmPassword) {
            return [
                'success' => false,
                'message' => 'Las contraseñas no coinciden.'
            ];
        }

        $existingUser = $this->userModel->findByEmail($email);
        if ($existingUser) {
            return [
                'success' => false,
                'message' => 'El correo electrónico ya se encuentra registrado.'
            ];
        }

        if ($this->userModel->create($name, $email, $password)) {
            return [
                'success' => true,
                'message' => 'Usuario registrado exitosamente.'
            ];
        }

        return [
            'success' => false,
            'message' => 'Error al registrar el usuario en la base de datos.'
        ];
    }

    /**
     * Cerrar la sesión activa del usuario.
     */
    public function logout(): array
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

        return [
            'success' => true,
            'message' => 'Sesión cerrada correctamente.'
        ];
    }
}
