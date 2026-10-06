<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UserDto",
    title: "Información de Usuario",
    description: "Datos del usuario autenticado"
)]
class UserDto
{
    #[OA\Property(property: "id", type: "integer", example: 1)]
    public int $id;

    #[OA\Property(property: "nombre", type: "string", example: "Administrador")]
    public string $nombre;

    #[OA\Property(property: "correo", type: "string", format: "email", example: "admin@paulobot.com")]
    public string $correo;

    #[OA\Property(property: "tipo_usuario", type: "string", example: "1", description: "1 = Admin, 2 = Empleado/Cajero")]
    public string $tipo_usuario;

    #[OA\Property(property: "activo", type: "integer", example: 1)]
    public int $activo;
}
