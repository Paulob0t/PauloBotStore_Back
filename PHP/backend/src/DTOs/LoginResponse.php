<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "LoginResponse",
    title: "Respuesta de Inicio de Sesión",
    description: "Respuesta retornada al autenticar credenciales satisfactoriamente"
)]
class LoginResponse
{
    #[OA\Property(property: "success", type: "boolean", example: true)]
    public bool $success;

    #[OA\Property(property: "message", type: "string", example: "¡Bienvenido de nuevo, Administrador!")]
    public string $message;

    #[OA\Property(property: "token", type: "string", nullable: true, example: "pb_tok_9b2e8a10f...")]
    public ?string $token;

    #[OA\Property(property: "user", ref: "#/components/schemas/UserDto")]
    public UserDto $user;
}
