<?php
/**
 * Mercado Pago Point - API de Integración
 * Permite conectar con terminales Point físicas
 */

header('Content-Type: application/json');
require_once 'mercadopago_config.php';

// Habilitar errores para debug
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    // Obtener datos del request
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('No se recibieron datos');
    }

    $action = $data['action'] ?? null;

    switch ($action) {
        case 'list_devices':
            // Listar dispositivos Point disponibles
            $devices = listPointDevices();
            echo json_encode([
                'success' => true,
                'devices' => $devices
            ]);
            break;

        case 'create_payment_intent':
            // Crear intención de pago en el Point
            if (!isset($data['amount'])) {
                throw new Exception('Falta el monto (amount)');
            }

            $amount = floatval($data['amount']);
            
            // ⚠️ Point requiere monto mínimo de $5.00 MXN (500 centavos)
            if ($amount < 5) {
                throw new Exception('El monto mínimo para Point es $5.00 MXN (recibido: $' . number_format($amount, 2) . ')');
            }

            $result = createPaymentIntent(
                $amount,
                $data['description'] ?? 'Pago VendingBox',
                $data['device_id'] ?? null,
                $data['external_reference'] ?? null
            );

            echo json_encode([
                'success' => true,
                'payment_intent' => $result
            ]);
            break;

        case 'get_payment_status':
            // Consultar estado de un pago
            if (!isset($data['payment_intent_id'])) {
                throw new Exception('Falta payment_intent_id');
            }

            $status = getPaymentIntentStatus($data['payment_intent_id']);
            echo json_encode([
                'success' => true,
                'status' => $status
            ]);
            break;

        case 'cancel_payment':
            // Cancelar intención de pago
            if (!isset($data['payment_intent_id'])) {
                throw new Exception('Falta payment_intent_id');
            }
            
            // 🔥 device_id es opcional pero NECESARIO para cancelación correcta
            $device_id = $data['device_id'] ?? null;
            
            error_log("🎯 CANCEL_PAYMENT recibido - payment_intent_id: " . $data['payment_intent_id'] . ", device_id: " . ($device_id ?? 'NULL'));

            $result = cancelPaymentIntent($data['payment_intent_id'], $device_id);
            
            error_log("📤 Enviando respuesta - success: true, cancelled: " . ($result['success'] ? 'true' : 'false') . ", http_code: " . $result['http_code']);
            
            echo json_encode([
                'success' => true,
                'cancelled' => $result['success'],
                'http_code' => $result['http_code'],
                'message' => $result['message'] ?? null
            ]);
            break;
            
        case 'force_device_reset':
            // 🔥 NUEVO: Intentar resetear el dispositivo creando un nuevo payment intent de $0.01 y cancelándolo
            if (!isset($data['device_id'])) {
                throw new Exception('Falta device_id');
            }
            
            $device_id = $data['device_id'];
            error_log("🔄 Intentando RESET FORZADO del dispositivo: $device_id");
            
            try {
                // Crear un payment intent mínimo ($5.00 es el mínimo)
                $dummyIntent = createPaymentIntent(5.00, 'RESET', $device_id, 'DUMMY-RESET-' . time());
                $dummyId = $dummyIntent['id'];
                
                // Cancelarlo inmediatamente (debe estar en OPEN todavía)
                sleep(1); // Esperar 1 segundo
                $cancelResult = cancelPaymentIntent($dummyId, $device_id);
                
                error_log("🔄 Reset result: " . json_encode($cancelResult));
                
                echo json_encode([
                    'success' => true,
                    'reset_attempted' => true,
                    'cancel_result' => $cancelResult
                ]);
            } catch (Exception $e) {
                error_log("❌ Error en reset forzado: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;

        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Listar dispositivos Point asociados a la cuenta
 */
function listPointDevices() {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/point/integration-api/devices',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . getMercadoPagoAccessToken()
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Error al listar dispositivos: HTTP $httpCode");
    }

    $result = json_decode($response, true);
    return $result['devices'] ?? [];
}

/**
 * Crear intención de pago en el Point
 */
function createPaymentIntent($amount, $description, $device_id = null, $external_reference = null) {
    $external_reference = $external_reference ?? 'ORDER-' . time() . '-' . rand(1000, 9999);
    
    // ⚠️ Point API requiere el monto en CENTAVOS
    // Ejemplo: $500.00 pesos = 50000 centavos
    $amountInCents = intval($amount * 100);
    
    // 🔥 Point API - Configuración básica
    $payment_data = [
        'amount' => $amountInCents,
        // 🔥 IMPORTANTE: Configurar comportamiento de la terminal
        'additional_info' => [
            'external_reference' => $external_reference,
            'print_on_terminal' => true
        ]
    ];

    // Si se especifica un device_id, se usa
    $url = 'https://api.mercadopago.com/point/integration-api/devices';
    if ($device_id) {
        $url .= '/' . $device_id . '/payment-intents';
    } else {
        // Si no se especifica, se usa el primer dispositivo disponible
        $devices = listPointDevices();
        if (empty($devices)) {
            throw new Exception('No hay dispositivos Point disponibles. Asegúrate de que tu Point esté vinculado a tu cuenta.');
        }
        $device_id = $devices[0]['id'];
        $url .= '/' . $device_id . '/payment-intents';
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payment_data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . getMercadoPagoAccessToken()
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception('Error de conexión: ' . $error);
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        $errorData = json_decode($response, true);
        $errorMsg = $errorData['message'] ?? $response;
        throw new Exception("Error al crear payment intent (HTTP $httpCode): $errorMsg");
    }

    $result = json_decode($response, true);
    
    // 🔥 IMPORTANTE: Agregar device_id al resultado para poder cancelar después
    $result['device_id'] = $device_id;
    
    // Log para debug
    error_log('Point Payment Intent Created: ' . json_encode($result, JSON_PRETTY_PRINT));
    
    return $result;
}

/**
 * Consultar estado de una intención de pago
 */
function getPaymentIntentStatus($payment_intent_id) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api.mercadopago.com/point/integration-api/payment-intents/' . $payment_intent_id,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . getMercadoPagoAccessToken()
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Error al consultar estado: HTTP $httpCode");
    }

    $result = json_decode($response, true);
    return $result;
}

/**
 * Cancelar una intención de pago
 * 🔥 CORRECTO: Usa device_id + payment_intent_id
 */
function cancelPaymentIntent($payment_intent_id, $device_id = null) {
    error_log("🔥 Cancelando Payment Intent: $payment_intent_id");
    
    // 🔥 Si no tenemos device_id, intentar obtenerlo del primer dispositivo
    if (!$device_id) {
        error_log("⚠️ No se proporcionó device_id, buscando primer dispositivo...");
        try {
            $devices = listPointDevices();
            if (!empty($devices)) {
                $device_id = $devices[0]['id'];
                error_log("✅ Usando device_id: $device_id");
            } else {
                error_log("❌ No se encontraron dispositivos");
                return false;
            }
        } catch (Exception $e) {
            error_log("❌ Error al obtener dispositivos: " . $e->getMessage());
            return false;
        }
    }
    
    // 🔥 DELETE CORRECTO: /devices/{device_id}/payment-intents/{payment_intent_id}
    $url = "https://api.mercadopago.com/point/integration-api/devices/$device_id/payment-intents/$payment_intent_id";
    error_log("📤 DELETE URL: $url");
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . getMercadoPagoAccessToken()
        ],
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    error_log("📥 DELETE Response - HTTP Code: $httpCode");
    if ($error) {
        error_log("❌ CURL Error: $error");
    }
    if ($response) {
        error_log("📄 Response Body: $response");
    }

    $success = $httpCode === 200 || $httpCode === 204;
    $responseData = json_decode($response, true);
    
    error_log($success ? "✅ Payment Intent CANCELADO exitosamente con device_id" : "❌ No se pudo cancelar Payment Intent");
    
    // 🔥 Retornar información detallada para manejo inteligente en frontend
    return [
        'success' => $success,
        'http_code' => $httpCode,
        'message' => $responseData['message'] ?? null,
        'error_code' => $responseData['error'] ?? null
    ];
}
