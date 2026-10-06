<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateUserStatusRequest",
    title: "Actualizar Estado de Usuario",
    required: ["activo"]
)]
class UpdateUserStatusRequest
{
    #[OA\Property(property: "activo", type: "integer", enum: [0, 1], example: 1)]
    public int $activo;
}
