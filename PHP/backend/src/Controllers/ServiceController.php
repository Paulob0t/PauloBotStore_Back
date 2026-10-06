<?php

namespace App\Controllers;

use App\Core\Response;
use App\Services\ProntiPagosService;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(name: "Servicios Digitales", description: "Endpoints para recargas telefónicas, pago de servicios (CFE) y pines digitales")]
class ServiceController
{
    #[OA\Get(
        path: "/api/v1/services",
        operationId: "getServiceProviders",
        summary: "Obtener lista de proveedores de servicios y recargas",
        description: "Retorna los proveedores disponibles (CFE, Telcel, Netflix, etc.) con sus metadatos y estado de disponibilidad.",
        tags: ["Servicios Digitales"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Listado de proveedores de servicios"
            )
        ]
    )]
    public function getProviders(): void
    {
        try {
            $providers = ProntiPagosService::getProviders();
            Response::json($providers, 200);
        } catch (Throwable $e) {
            Response::error('Error al obtener proveedores de servicios: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/services/cfe/check-balance",
        operationId: "checkCfeBalance",
        summary: "Consultar adeudo de recibo CFE",
        description: "Consulta en tiempo real con ProntiPagos el adeudo pendiente para un número de servicio CFE.",
        tags: ["Servicios Digitales"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "service_number", type: "string", example: "012345678901")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Adeudo consultado exitosamente"
            ),
            new OA\Response(
                response: 400,
                description: "Número de servicio inválido"
            )
        ]
    )]
    public function checkCfe(): void
    {
        $input = $this->getJsonInput();
        $serviceNumber = trim($input['service_number'] ?? '');

        if (empty($serviceNumber)) {
            Response::error('El número de servicio CFE es obligatorio.', null, 400);
            return;
        }

        try {
            $result = ProntiPagosService::checkCfeBalance($serviceNumber);
            Response::json($result, 200);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), null, 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/services/cfe/pay",
        operationId: "payCfe",
        summary: "Pagar recibo CFE",
        description: "Procesa el pago de luz CFE con ProntiPagos y emite folio y ticket.",
        tags: ["Servicios Digitales"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "service_number", type: "string", example: "012345678901"),
                    new OA\Property(property: "amount", type: "number", format: "float", example: 185.00),
                    new OA\Property(property: "payment_method", type: "string", example: "cash")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Pago de CFE procesado exitosamente"
            ),
            new OA\Response(
                response: 400,
                description: "Error al procesar pago"
            )
        ]
    )]
    public function payCfe(): void
    {
        $input = $this->getJsonInput();
        $serviceNumber = trim($input['service_number'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $method = trim($input['payment_method'] ?? 'cash');

        if (empty($serviceNumber)) {
            Response::error('El número de servicio CFE es obligatorio.', null, 400);
            return;
        }

        if ($amount <= 0) {
            Response::error('El monto debe ser superior a $0.00.', null, 400);
            return;
        }

        try {
            $result = ProntiPagosService::payCfe($serviceNumber, $amount, $method);
            Response::json($result, 200);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/services/movistar/packages",
        operationId: "getMovistarPackages",
        summary: "Obtener paquetes de recarga Movistar",
        tags: ["Servicios Digitales"],
        responses: [
            new OA\Response(response: 200, description: "Listado de paquetes Movistar")
        ]
    )]
    public function getMovistarPackages(): void
    {
        try {
            $packages = ProntiPagosService::getMovistarPackages();
            Response::json($packages, 200);
        } catch (Throwable $e) {
            Response::error('Error al obtener paquetes Movistar: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/services/movistar/recharge",
        operationId: "rechargeMovistar",
        summary: "Procesar recarga telefónica Movistar",
        tags: ["Servicios Digitales"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "phone_number", type: "string", example: "5512345678"),
                    new OA\Property(property: "sku", type: "string", example: "S3TAE50MOVIMXN"),
                    new OA\Property(property: "amount", type: "number", format: "float", example: 50.00),
                    new OA\Property(property: "payment_method", type: "string", example: "cash")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Recarga procesada exitosamente"),
            new OA\Response(response: 400, description: "Error en datos de recarga")
        ]
    )]
    public function rechargeMovistar(): void
    {
        $input = $this->getJsonInput();
        $phone = trim($input['phone_number'] ?? '');
        $sku = trim($input['sku'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $method = trim($input['payment_method'] ?? 'cash');

        if (empty($phone) || strlen($phone) !== 10) {
            Response::error('El número celular debe contener 10 dígitos.', null, 400);
            return;
        }

        if (empty($sku)) {
            Response::error('El paquete o SKU es obligatorio.', null, 400);
            return;
        }

        if ($amount <= 0) {
            Response::error('El monto debe ser superior a $0.00.', null, 400);
            return;
        }

        try {
            $result = ProntiPagosService::payMovistar($phone, $sku, $amount, $method);
            Response::json($result, 200);
        } catch (Throwable $e) {
            Response::error($e->getMessage(), null, 500);
        }
    }

    private function getJsonInput(): array
    {
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }
        return $_POST;
    }
}
