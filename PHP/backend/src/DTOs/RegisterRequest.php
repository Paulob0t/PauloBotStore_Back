<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "RegisterRequest",
    title: "Solicitud de Registro de Usuario",
    description: "Datos requeridos para crear una cuenta nueva",
    required: ["nombre", "correo", "contrasena", "confirmar_contrasena"]
)]
class RegisterRequest
{
    #[OA\Property(
        property: "nombre",
        description: "Nombre completo del usuario",
        type: "string",
        example: "Paulo Armenta"
    )]
    public string $nombre;

    #[OA\Property(
        property: "correo",
        description: "Correo electrónico del usuario",
        type: "string",
        format: "email",
        example: "usuario@paulobot.com"
    )]
    public string $correo;

    #[OA\Property(
        property: "contrasena",
        description: "Contraseña segura (mínimo 6 caracteres)",
        type: "string",
        format: "password",
        example: "Secreto123!"
    )]
    public string $contrasena;

    #[OA\Property(
        property: "confirmar_contrasena",
        description: "Confirmación de la contraseña",
        type: "string",
        format: "password",
        example: "Secreto123!"
    )]
    public string $confirmar_contrasena;
}
