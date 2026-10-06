<?php

namespace App\Controllers;

use App\Core\Response;
use App\DTOs\ApiResponse;
use App\DTOs\CashRegisterActionResponse;
use App\DTOs\CashRegisterCutDto;
use App\DTOs\CashRegisterStatusDto;
use App\DTOs\CloseCashRegisterRequest;
use App\DTOs\CreateCashMovementRequest;
use App\DTOs\OpenCashRegisterRequest;
use App\DTOs\UpdateCashConfigRequest;
use App\Models\CashRegister;
use OpenApi\Attributes as OA;

class CashRegisterController
{
    private CashRegister $cashRegisterModel;

    public function __construct()
    {
        $this->cashRegisterModel = new CashRegister();
    }

    #[OA\Get(
        path: "/api/v1/cash-register/status",
        operationId: "getCashRegisterStatus",
        summary: "Obtener estado actual de la caja y corte en vivo",
        description: "Retorna si la caja está abierta o cerrada, configuración de cierre, corte activo con totales en tiempo real y lista de movimientos del turno.",
        tags: ["Cortes de Caja"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Estado actual de la caja",
                content: new OA\JsonContent(ref: "#/components/schemas/CashRegisterStatusDto")
            ),
            new OA\Response(
                response: 500,
                description: "Error al consultar estado de caja",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getStatus(): void
    {
        try {
            $config = $this->cashRegisterModel->getConfig();
            $isActive = $this->cashRegisterModel->isCashRegisterActive();
            $currentCut = $isActive ? $this->cashRegisterModel->getCurrentCut() : null;

            $totals = null;
            $movements = [];

            if ($isActive && $currentCut) {
                $totals = $this->cashRegisterModel->calculateTotals($currentCut['id'], $currentCut['monto_inicial']);
                $movements = $this->cashRegisterModel->getMovements($currentCut['id']);
            }

            Response::json([
                'caja_activa' => $isActive,
                'config' => $config,
                'corte_actual' => $currentCut,
                'totales' => $totals,
                'movimientos' => $movements
            ], 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener estado de caja: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Post(
        path: "/api/v1/cash-register/open",
        operationId: "openCashRegister",
        summary: "Iniciar nueva jornada / Apertura de caja",
        description: "Inicia una jornada con un fondo inicial declarado y habilita la recepción de movimientos.",
        tags: ["Cortes de Caja"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/OpenCashRegisterRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Caja abierta con éxito",
                content: new OA\JsonContent(ref: "#/components/schemas/CashRegisterActionResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Error de validación o caja ya abierta",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function open(): void
    {
        $input = $this->getJsonInput();
        $montoInicial = floatval($input['monto_inicial'] ?? 0);
        $notas = isset($input['notas']) ? trim((string)$input['notas']) : null;
        $idUsuario = 1; // Super Admin por defecto

        if ($montoInicial < 0) {
            Response::error('El monto inicial no puede ser negativo.', null, 400);
            return;
        }

        try {
            $res = $this->cashRegisterModel->openCashRegister($montoInicial, $idUsuario, $notas);
            Response::json([
                'success' => true,
                'message' => $res['message'],
                'id_corte' => $res['id_corte']
            ], 201);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), null, 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/cash-register/close",
        operationId: "closeCashRegister",
        summary: "Cerrar caja / Realizar corte de turno",
        description: "Cierra el corte activo con el monto final contado físicamente, calculando diferencias y total de ventas.",
        tags: ["Cortes de Caja"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CloseCashRegisterRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Caja cerrada y corte guardado",
                content: new OA\JsonContent(ref: "#/components/schemas/CashRegisterActionResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Error al cerrar caja",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function close(): void
    {
        $input = $this->getJsonInput();
        $montoFinal = floatval($input['monto_final'] ?? 0);
        $notas = isset($input['notas']) ? trim((string)$input['notas']) : null;
        $idUsuario = 1;

        if ($montoFinal < 0) {
            Response::error('El monto final declarado no puede ser negativo.', null, 400);
            return;
        }

        try {
            $res = $this->cashRegisterModel->closeCashRegister($idUsuario, $montoFinal, $notas);
            Response::json([
                'success' => true,
                'message' => $res['message'],
                'id_corte' => $res['id_corte'],
                'monto_esperado' => $res['monto_esperado'],
                'monto_declarado' => $res['monto_declarado'],
                'diferencia' => $res['diferencia']
            ], 200);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), null, 400);
        }
    }

    #[OA\Post(
        path: "/api/v1/cash-register/movements",
        operationId: "createCashMovement",
        summary: "Registrar movimiento manual de caja (ingreso / egreso)",
        description: "Registra una entrada o salida extraordinaria de efectivo vinculada al corte activo.",
        tags: ["Cortes de Caja"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateCashMovementRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Movimiento registrado",
                content: new OA\JsonContent(ref: "#/components/schemas/CashRegisterActionResponse")
            ),
            new OA\Response(
                response: 400,
                description: "Datos inválidos o caja cerrada",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function addMovement(): void
    {
        if (!$this->cashRegisterModel->isCashRegisterActive()) {
            Response::error('No hay una caja activa para registrar movimientos.', null, 400);
            return;
        }

        $input = $this->getJsonInput();
        $tipo = trim($input['tipo_movimiento'] ?? '');
        $concepto = trim($input['concepto'] ?? '');
        $monto = floatval($input['monto'] ?? 0);
        $metodoPago = !empty($input['metodo_pago']) ? trim($input['metodo_pago']) : 'Efectivo';
        $notas = !empty($input['notas']) ? trim($input['notas']) : null;

        if (!in_array($tipo, ['ingreso', 'egreso'])) {
            Response::error("El tipo de movimiento debe ser 'ingreso' o 'egreso'.", null, 400);
            return;
        }

        if (empty($concepto)) {
            Response::error('El concepto es obligatorio.', null, 400);
            return;
        }

        if ($monto <= 0) {
            Response::error('El monto debe ser mayor a 0.', null, 400);
            return;
        }

        try {
            $cfg = $this->cashRegisterModel->getConfig();
            $idCorte = $cfg['id_corte_actual'];
            $idMov = $this->cashRegisterModel->addMovement($idCorte, $tipo, $concepto, $monto, $metodoPago, 1, $notas);

            Response::json([
                'success' => true,
                'message' => 'Movimiento registrado correctamente.',
                'id_corte' => $idCorte
            ], 201);
        } catch (\Throwable $e) {
            Response::error('Error al registrar movimiento: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/cash-register/history",
        operationId: "getCashRegisterHistory",
        summary: "Obtener historial de cortes cerrados",
        description: "Lista de cortes de caja finalizados con fecha, montos inicial/final, ingresos, egresos y diferencias.",
        tags: ["Cortes de Caja"],
        parameters: [
            new OA\Parameter(
                name: "desde",
                in: "query",
                required: false,
                description: "Fecha desde (YYYY-MM-DD)",
                schema: new OA\Schema(type: "string", format: "date")
            ),
            new OA\Parameter(
                name: "hasta",
                in: "query",
                required: false,
                description: "Fecha hasta (YYYY-MM-DD)",
                schema: new OA\Schema(type: "string", format: "date")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Historial de cortes",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/CashRegisterCutDto")
                )
            )
        ]
    )]
    public function getHistory(): void
    {
        try {
            $desde = $_GET['desde'] ?? null;
            $hasta = $_GET['hasta'] ?? null;
            $history = $this->cashRegisterModel->getHistory($desde, $hasta);
            Response::json($history, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener historial de cortes: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Get(
        path: "/api/v1/cash-register/{id}",
        operationId: "getCashRegisterCutById",
        summary: "Obtener detalle completo de un corte específico",
        description: "Retorna toda la información de un corte histórico con sus movimientos individuales y balance.",
        tags: ["Cortes de Caja"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del corte",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalle del corte",
                content: new OA\JsonContent(
                    type: "object"
                )
            ),
            new OA\Response(
                response: 404,
                description: "Corte no encontrado",
                content: new OA\JsonContent(ref: "#/components/schemas/ApiResponse")
            )
        ]
    )]
    public function getById(int $id): void
    {
        try {
            $detail = $this->cashRegisterModel->getCutDetail($id);
            if (!$detail) {
                Response::error('Corte no encontrado.', null, 404);
                return;
            }
            Response::json($detail, 200);
        } catch (\Throwable $e) {
            Response::error('Error al obtener detalle del corte: ' . $e->getMessage(), null, 500);
        }
    }

    #[OA\Put(
        path: "/api/v1/cash-register/config",
        operationId: "updateCashRegisterConfig",
        summary: "Actualizar parámetros de configuración de corte automático",
        tags: ["Cortes de Caja"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/UpdateCashConfigRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Configuración actualizada con éxito",
                content: new OA\JsonContent(ref: "#/components/schemas/CashRegisterActionResponse")
            )
        ]
    )]
    public function updateConfig(): void
    {
        $input = $this->getJsonInput();
        $hab = !empty($input['corte_automatico_habilitado']);
        $hora = trim($input['hora_corte_automatico'] ?? '23:59:00');
        $monto = floatval($input['monto_inicial_default'] ?? 100.0);

        if ($this->cashRegisterModel->updateConfig($hab, $hora, $monto)) {
            Response::json([
                'success' => true,
                'message' => 'Configuración de caja actualizada.'
            ], 200);
            return;
        }

        Response::error('Error al actualizar configuración.', null, 500);
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
