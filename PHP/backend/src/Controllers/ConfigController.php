<?php

namespace App\Controllers;

use App\Core\Response;
use App\DTOs\ApiResponse;
use App\DTOs\CompanyConfigDto;
use App\DTOs\UpdateCompanyConfigRequest;
use App\Models\CompanyConfig;
use OpenApi\Attributes as OA;

class ConfigController
{
    private CompanyConfig $configModel;

    public function __construct()
    {
        $this->configModel = new CompanyConfig();
    }

    #[OA\Get(
        path: "/api/v1/config/company",
        operationId: "getCompanyConfig",
        summary: "Obtener datos de la empresa y configuración de tickets",
        description: "Retorna el nombre comercial, RFC, domicilio, datos de contacto y leyenda para tickets impresos.",
        tags: ["Configuración"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Configuración de la empresa",
                content: new OA\JsonContent(ref: "#/components/schemas/CompanyConfigDto")
            ),
            new OA\Response(
                response: 500,
                description: "Error al consultar configuración",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getCompany(): void
    {
        try {
            $data = $this->configModel->get();
            Response::json($data, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener configuración de empresa: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Put(
        path: "/api/v1/config/company",
        operationId: "updateCompanyConfig",
        summary: "Actualizar datos de la empresa y tickets",
        description: "Modifica la información corporativa, teléfonos, dirección fiscal y pie de ticket de venta.",
        tags: ["Configuración"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateCompanyConfigRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Configuración guardada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos inválidos",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function updateCompany(): void
    {
        $input = $this->getJsonInput();
        $name = trim($input['nombre_empresa'] ?? '');

        if (empty($name)) {
            Response::error('El nombre de la empresa es obligatorio.', null, 400);
            return;
        }

        try {
            $this->configModel->update($input);
            Response::json([
                'success' => true,
                'message' => 'Configuración de empresa guardada correctamente.'
            ], 200);
        } catch (\Throwable $e) {
            Response::error('Error al guardar configuración: ' . $e->getMessage(), null, 500);
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) return $decoded;
        }
        return $_POST;
    }
}
