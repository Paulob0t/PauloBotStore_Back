<?php

namespace App\DTOs;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "UpdateCompanyConfigRequest",
    title: "Solicitud de Actualización de Empresa",
    required: ["nombre_empresa"]
)]
class UpdateCompanyConfigRequest
{
    #[OA\Property(property: "nombre_empresa", type: "string", example: "PauloBot Store")]
    public string $nombre_empresa;

    #[OA\Property(property: "direccion", type: "string", nullable: true, example: "Av. Tecnológico 123")]
    public ?string $direccion;

    #[OA\Property(property: "ciudad", type: "string", nullable: true, example: "León")]
    public ?string $ciudad;

    #[OA\Property(property: "estado", type: "string", nullable: true, example: "Guanajuato")]
    public ?string $estado;

    #[OA\Property(property: "telefono", type: "string", nullable: true, example: "477-123-4567")]
    public ?string $telefono;

    #[OA\Property(property: "rfc", type: "string", nullable: true, example: "XAXX010101000")]
    public ?string $rfc;

    #[OA\Property(property: "email", type: "string", nullable: true, example: "contacto@paulobot.com")]
    public ?string $email;

    #[OA\Property(property: "website", type: "string", nullable: true, example: "https://paulobot.store")]
    public ?string $website;

    #[OA\Property(property: "mensaje_ticket", type: "string", nullable: true, example: "¡GRACIAS POR SU COMPRA!")]
    public ?string $mensaje_ticket;
}
