<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LoginRequest",
    title: "Solicitud de Inicio de Sesión",
    description: "Credenciales requeridas para autenticar al usuario",
    required: ["correo", "contrasena"]
)]
class LoginRequest
{
    #[OA\Property(
        property: "correo",
        description: "Correo electrónico registrado",
        type: "string",
        format: "email",
        example: "admin@paulobot.com"
    )]
    public string $correo;

    #[OA\Property(
        property: "contrasena",
        description: "Contraseña de acceso del usuario",
        type: "string",
        format: "password",
        example: "123456"
    )]
    public string $contrasena;
}
