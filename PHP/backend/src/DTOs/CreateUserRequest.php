<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "CreateUserRequest",
    title: "Solicitud para Crear Usuario",
    required: ["nombre", "correo", "contrasena"]
)]
class CreateUserRequest
{
    #[OA\Property(property: "nombre", type: "string", example: "Juan Pérez")]
    public string $nombre;

    #[OA\Property(property: "correo", type: "string", format: "email", example: "juan@paulobot.com")]
    public string $correo;

    #[OA\Property(property: "contrasena", type: "string", format: "password", example: "SuperSecret123!")]
    public string $contrasena;

    #[OA\Property(property: "tipo_usuario", type: "integer", nullable: true, example: 1)]
    public ?int $tipo_usuario;

    #[OA\Property(property: "activo", type: "integer", nullable: true, example: 1)]
    public ?int $activo;
}
