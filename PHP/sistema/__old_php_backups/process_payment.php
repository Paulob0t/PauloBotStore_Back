<?php
header('Content-Type: application/json');

function authenticateAPI() {
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://prontipagos-api-dev.domainscm.com/prontipagos-external-api-ws/ws/v1/auth/login',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode([
            'username' => 'api.desarrollo',
            'password' => '1hFdcv4G*'
        ]),
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: PostmanRuntime/7.36.1',
            'Cache-Control: no-cache',
            'Postman-Token: ' . uniqid()
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($httpCode === 200) {
        $authData = json_decode($response, true);
        return $authData['payload']['accessToken'] ?? null;
    }

    return null;
}

function processPayment($token, $data) {
    $transactionId = time() . rand(1000, 9999);
    
    $paymentData = [
        'sku' => $data['sku'],
        'amount' => floatval($data['amount']),
        'reference' => $data['reference'],
        'transacctionId' => $transactionId
    ];

    if (strpos($data['sku'], 'TELCEL') !== false) {
        $paymentData['requiresRegionalization'] = true;
    }

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://prontipagos-api-dev.domainscm.com/prontipagos-external-api-ws/ws/protected/v1/sell/product',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => json_encode($paymentData),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: PostmanRuntime/7.36.1',
            'Cache-Control: no-cache',
            'Postman-Token: ' . uniqid()
        ),
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ));

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $apiResponse = json_decode($response, true);
    
    // 🔍 DEBUG: Log de la respuesta completa
    error_log("🔵 [PAYMENT] API Response: " . json_encode($apiResponse));
    
    // Verificar si realmente fue exitoso (no solo HTTP 200)
    $isSuccess = $httpCode === 200 && isset($apiResponse['code']) && $apiResponse['code'] === 0;
    
    // Verificar códigos de error en el payload
    if ($isSuccess && isset($apiResponse['payload'])) {
        $codeTransaction = $apiResponse['payload']['codeTransaction'] ?? '';
        $statusTransactionId = $apiResponse['payload']['statusTransactionId'] ?? 0;
        
        error_log("🔍 [PAYMENT] codeTransaction: " . $codeTransaction);
        error_log("🔍 [PAYMENT] statusTransactionId: " . $statusTransactionId);
        
        // Si codeTransaction es '01' o statusTransactionId indica error, marcar como fallido
        if ($codeTransaction === '01' || in_array($statusTransactionId, [5, 6, 7])) {
            $isSuccess = false;
            error_log("❌ [PAYMENT] Error detectado - Marcando como failed");
        }
    }
    
    error_log("✅ [PAYMENT] isSuccess final: " . ($isSuccess ? 'true' : 'false'));

    return [
        'success' => $isSuccess,
        'response' => $apiResponse,
        'transactionId' => $transactionId
    ];
}

try {
    $requestData = json_decode(file_get_contents('php://input'), true);

    if (!$requestData) {
        throw new Exception('Datos incompletos o JSON inválido');
    }

    // 🪙 MANEJO ESPECIAL PARA PAGOS CON MONEDAS
    if (isset($requestData['action']) && $requestData['action'] === 'pay_with_coins') {
        // Validar datos requeridos para pago con monedas
        if (!isset($requestData['sku']) || !isset($requestData['amount']) || !isset($requestData['reference'])) {
            throw new Exception('Datos incompletos para pago con monedas');
        }

        // Obtener token y procesar pago normalmente
        $token = authenticateAPI();
        if (!$token) {
            throw new Exception('Error de autenticacion');
        }

        $result = processPayment($token, $requestData);

        // Si el pago fue exitoso, NO resetear aquí el saldo
        // El frontend ya lo hace después de confirmar éxito
        $response = [
            'success' => $result['success'],
            'data' => $result['response'],
            'transactionId' => $result['transactionId'],
            'folio' => $result['transactionId'],
            'paid_with_coins' => true
        ];

        if (!$result['success']) {
            $errorMsg = 'Error al procesar el pago con monedas';
            
            if (isset($result['response']['payload']['codeDescription'])) {
                $errorMsg = $result['response']['payload']['codeDescription'];
            }
            
            $response['error'] = $errorMsg;
            $response['message'] = $errorMsg;
        }

        echo json_encode($response);
        exit;
    }

    // 💳 PAGO NORMAL (sin monedas)
    if (!isset($requestData['sku']) || !isset($requestData['amount']) || !isset($requestData['reference'])) {
        throw new Exception('Datos incompletos');
    }
    $token = authenticateAPI();
    if (!$token) {
        throw new Exception('Error de autenticacion');
    }
    $result = processPayment($token, $requestData);

    $response = [
        'success' => $result['success'],
        'data' => $result['response'],
        'transactionId' => $result['transactionId']
    ];

    if (!$result['success']) {
        $errorMsg = 'Error al procesar el pago';
        
        // Agregar codeDescription si existe
        if (isset($result['response']['payload']['codeDescription'])) {
            $errorMsg = $result['response']['payload']['codeDescription'];
        }
        
        $response['error'] = $errorMsg;
    }

    echo json_encode($response);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
