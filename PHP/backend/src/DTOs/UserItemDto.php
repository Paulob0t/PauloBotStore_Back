<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UserItemDto",
    title: "Usuario del Sistema",
    required: ["id", "nombre", "correo", "tipo_usuario", "activo"]
)]
class UserItemDto
{
    #[OA\Property(property: "id", type: "integer", example: 1)]
    public int $id;

    #[OA\Property(property: "nombre", type: "string", example: "Juan Pérez")]
    public string $nombre;

    #[OA\Property(property: "correo", type: "string", format: "email", example: "juan@paulobot.com")]
    public string $correo;

    #[OA\Property(property: "tipo_usuario", type: "integer", example: 1)]
    public int $tipo_usuario;

    #[OA\Property(property: "tipo_usuario_label", type: "string", example: "Administrador")]
    public string $tipo_usuario_label;

    #[OA\Property(property: "activo", type: "integer", example: 1)]
    public int $activo;

    #[OA\Property(property: "fecha_creacion", type: "string", nullable: true, example: "2026-09-01 12:00:00")]
    public ?string $fecha_creacion;
}
