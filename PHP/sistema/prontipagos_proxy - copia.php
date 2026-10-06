<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
// header('Content-Type: application/json');

function authenticateAPI() {
    $apiUrl = getenv('API_URL');
    $usr = getenv('API_USR');
    $pwd = getenv('API_PWD');
    
    $loginUrl = $apiUrl . '/v1/auth/login';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'username' => $usr,
        'password' => $pwd
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: */*',
        'User-Agent: PostmanRuntime/7.36.1',
        'Cache-Control: no-cache',
        'Postman-Token: ' . uniqid()
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        throw new Exception('Error de conexión en autenticación: ' . $error);
    }

    if ($httpCode !== 200) {
        throw new Exception('Error HTTP en autenticación: ' . $httpCode);
    }

    $authData = json_decode($response, true);
    if (!$authData || !isset($authData['payload']['accessToken'])) {
        throw new Exception('Respuesta de autenticación inválida');
    }

    return $authData['payload']['accessToken'];
}

function fetchAllServices($token) {
    $allServices = [];
    $allRawServices = [];
    $apiUrl = getenv('API_URL');
    $possibleKeys = ['content', 'data', 'products', 'services', 'items', 'list', 'results', 'productList'];

    $page = 0;
    do {
        $url = $apiUrl . "/protected/v1/product/list?page=$page&pageSize=100";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: PostmanRuntime/7.36.1',
            'Cache-Control: no-cache',
            'Postman-Token: ' . uniqid()
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            break;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['payload'])) {
            break;
        }

        $pageServices = [];

        if (count($data['payload']['data']) > 0) {
            $pageServices = $data['payload']['data'];
        } else
            break;
        // foreach ($possibleKeys as $key) {
        //     if (isset($data['payload'][$key]) && is_array($data['payload'][$key])) {
        //         $pageServices = $data['payload'][$key];
        //         break;
        //     }
        // }

        $allRawServices = array_merge($allRawServices, $pageServices);

        // foreach ($pageServices as $service) {
        //     $name = strtoupper($service['name'] ?? '');
        //     // Registrar todos los servicios encontrados
        //     $allRawServices[] = [
        //         'name'       => $service['name'] ?? '',
        //         'sku'        => $service['sku'] ?? '',
        //         'categoryId' => intval($service['categoryId'] ?? 0)
        //     ];

        //     if (strpos($name, 'TELEFONIA TELMEX') !== false) {
        //         $allServices[] = [
        //             'sku'         => $service['sku'] ?? '',
        //             'name'        => $service['name'] ?? '',
        //             'description' => $service['description'] ?? '',
        //             'categoryId'  => intval($service['categoryId'] ?? 0),
        //             'discount'    => floatval($service['discount'] ?? 0),
        //             'fee'         => floatval($service['fee'] ?? 0),
        //             'maxAmount'   => floatval($service['maxAmount'] ?? 0),
        //             'minAmount'   => floatval($service['minAmount'] ?? 0)
        //         ];
        //         break;
        //     }
        // }

        $page++;
    } while (!empty($pageServices));

    return ['all' => $allRawServices];
}

function filterServices($services,$svc,$saldo = false) {
    $allowedNames = array_map('trim', $svc);

    $filtered = array_filter($services, function($service) use ($allowedNames, $saldo) {
        foreach ($allowedNames as $name) {
            if (stripos($service['name'], $name) !== false) {
                if ($saldo && stripos($service['name'], 'SALDO') !== false)
                    return true;
                if (!$saldo && stripos($service['name'], 'SALDO') === false)
                    return true;
            }
        }
        return false;
    });

    usort($filtered, function($a, $b) use ($allowedNames) {
        return array_search($a['name'], $allowedNames) - array_search($b['name'], $allowedNames);
    });

    return array_values($filtered);
}

function processPayment($token, $data) {
    $apiUrl = getenv('API_URL');
    $transactionId = time() . rand(1000, 9999);
    
    $paymentData = [
        'sku' => $data['sku'],
        'amount' => floatval($data['amount']),
        'reference' => $data['reference'],
        'transacctionId' => $transactionId
    ];

    // Todos los servicios de Telcel necesitan regionalización (confirmado con pruebas)
    // Incluye: TELCEL_*, PA* (Paquetes Amigo), IA* (Internet Amigo)
    $sku = $data['sku'];
    if (strpos($sku, 'TELCEL') !== false || 
        strpos($sku, 'PA') === 0 || 
        strpos($sku, 'IA') === 0) {
        $paymentData['requiresRegionalization'] = true;
    }
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $apiUrl . '/protected/v1/sell/product',
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
    // echo $response;
    // 🔍 DEBUG: Log de la respuesta inicial
    error_log("🔵 [PAYMENT] Respuesta inicial del API: " . json_encode($apiResponse));
    
    // Verificar que la solicitud fue aceptada
    if ($httpCode !== 200 || !isset($apiResponse['code']) || $apiResponse['code'] !== 0) {
        error_log("❌ [PAYMENT] Solicitud rechazada - HTTP: $httpCode, Code: " . ($apiResponse['code'] ?? 'N/A'));
        return [
            'success' => false,
            'response' => $apiResponse,
            'transactionId' => $transactionId
        ];
    }
    
    // Obtener el ID de transacción del API
    $apiTransactionId = $apiResponse['payload']['transactionId'] ?? null;
    
    if (!$apiTransactionId) {
        error_log("⚠️ [PAYMENT] Sin transactionId en respuesta");
        return [
            'success' => false,
            'response' => $apiResponse,
            'transactionId' => $transactionId
        ];
    }
    
    // 🔄 VERIFICAR ESTADO REAL DE LA TRANSACCIÓN
    error_log("🔍 [PAYMENT] Verificando estado real de transacción $apiTransactionId...");
    $statusCheck = checkTransactionStatus($token, $apiTransactionId);
    
    if (!$statusCheck['verified']) {
        error_log("⏰ [PAYMENT] No se pudo verificar el estado - Retornando error");
        
        return [
            'success' => false,
            'response' => $statusCheck['response'],
            'transactionId' => $transactionId,
            'verified' => false
        ];
    }
    
    // Usar el resultado de la verificación como respuesta final
    $finalResponse = $statusCheck['response'];
    $isSuccess = $statusCheck['success'];
    
    error_log("✅ [PAYMENT] Estado verificado - Éxito: " . ($isSuccess ? 'SÍ' : 'NO'));

    return [
        'success' => $isSuccess,
        'response' => $finalResponse,
        'transactionId' => $transactionId,
        'verified' => true
    ];
}

function checkTransactionStatus($token, $apiTransactionId, $maxAttempts = 30, $delaySeconds =2) {
    $apiUrl = getenv('API_URL');
    
    error_log("🔄 [STATUS_CHECK] Iniciando verificación de transacción: $apiTransactionId");
    error_log("⏱️ [STATUS_CHECK] Configuración: $maxAttempts intentos, {$delaySeconds}s entre intentos");
    
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        error_log("⏳ [STATUS_CHECK] Intento $attempt/$maxAttempts (esperando {$delaySeconds}s...)");
        sleep($delaySeconds);
        
        $checkUrl = $apiUrl . '/protected/v1/check-status?transactionId=' . urlencode($apiTransactionId);
        error_log("🌐 [STATUS_CHECK] URL: $checkUrl");
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $checkUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
                'Accept: application/json',
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
        
        error_log("📡 [STATUS_CHECK] HTTP Code: $httpCode");
        
        if ($httpCode !== 200) {
            error_log("❌ [STATUS_CHECK] Error HTTP: $httpCode - Respuesta: $response");
            continue;
        }
        
        $statusData = json_decode($response, true);
        error_log("📊 [STATUS_CHECK] Respuesta completa: " . json_encode($statusData));
        
        if (!isset($statusData['payload'])) {
            error_log("⚠️ [STATUS_CHECK] Sin payload en respuesta");
            continue;
        }
        
        error_log("🎯 [STATUS_CHECK] Payload: " . json_encode($statusData['payload']));
        
        if (!isset($statusData['payload']['codeTransaction'])) {
            error_log("⚠️ [STATUS_CHECK] Sin codeTransaction en payload - Esperando que se procese...");
            continue;
        }
        
        $codeTransaction = $statusData['payload']['codeTransaction'];
        $statusTransactionId = $statusData['payload']['statusTransactionId'] ?? 0;
        
        // ⏳ Si codeTransaction es "N/A" o statusTransactionId es 1 (Recibida), seguir esperando
        if ($codeTransaction === 'N/A' || $statusTransactionId === 1) {
            error_log("⏳ [STATUS_CHECK] Transacción aún procesándose (code: $codeTransaction, status: $statusTransactionId) - Esperando...");
            continue;
        }
        
        // ✅ '00' = Éxito confirmado
        if ($codeTransaction === '00') {
            error_log("✅ [STATUS_CHECK] ÉXITO CONFIRMADO - Code: 00");
            return [
                'verified' => true,
                'success' => true,
                'response' => $statusData
            ];
        }
        
        // ❌ CUALQUIER OTRO CÓDIGO = ERROR (incluyendo '08' timeout)
        // NO reintentar - el carrier ya procesó y rechazó la transacción
        $errorDescription = $statusData['payload']['codeDescription'] ?? 'Error desconocido';
        error_log("❌ [STATUS_CHECK] TRANSACCIÓN RECHAZADA - Code: $codeTransaction - $errorDescription");
        
        // Construir respuesta de error clara
        $errorResponse = [
            'code' => 0,
            'message' => $errorDescription,
            'payload' => $statusData['payload']
        ];
        
        return [
            'verified' => true,
            'success' => false,
            'response' => $errorResponse
        ];
    }
    
    // Timeout después de todos los intentos
    $totalSeconds = $maxAttempts * $delaySeconds;
    error_log("⏰ [STATUS_CHECK] Timeout final después de $maxAttempts intentos ({$totalSeconds}s)");
    
    return [
        'verified' => false,
        'success' => false,
        'response' => [
            'code' => 0,
            'message' => "No se pudo confirmar el pago después de {$totalSeconds} segundos. Por favor, verifique el saldo de ProntiPagos.",
            'payload' => [
                'codeTransaction' => '99',
                'codeDescription' => 'Timeout de verificación'
            ]
        ]
    ];
}


$tk = authenticateAPI();
$servicesData = null;
if (isset($_GET['data'])){
    $servicesData = json_decode($_GET['data'], true);
} else {        
    $servicesData = fetchAllServices($tk);
}

//Comprar servicio ej uso: comprar=1&svc=telmex&ref=7386880464&amount=100
if (isset($_GET['comprar'])){
    $servicio = $_GET['svc'];
    $servicio = strtoupper($servicio);
    $referencia = $_GET['ref'];
    $amount = $_GET['amount'];
    // $tk = authenticateAPI();
    //  $servicesData = fetchAllServices($tk);
    // $servicesData['filtered'] = filterServices($servicesData['all'], explode(',', $servicio),true);
    
    // echo json_encode($servicesData, JSON_UNESCAPED_UNICODE);
    // $filtered = $servicesData['filtered'];
    $payment = [
            'sku' => $servicio,  
            'amount' => $amount,
            'reference' => $referencia,
            'transacctionId' => time() . rand(1000, 9999)
        ];
    $resp = processPayment($tk, $payment);
    $resp = $resp['response']['payload'];
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
}

//Consultar saldo ej uso: saldo=1&svc=telmex&ref=7386880464
if (isset($_GET['saldo'])){
    $servicio = $_GET['svc'];
    $servicio = strtoupper($servicio);
    $referencia = $_GET['ref'];
    // $tk = authenticateAPI();
    //  $servicesData = fetchAllServices($tk);
    $servicesData['filtered'] = filterServices($servicesData['all'], explode(',', $servicio),true);
    
    // echo json_encode($servicesData, JSON_UNESCAPED_UNICODE);
    // $filtered = $servicesData['filtered'];
    $payment = [
            'sku' => $servicesData['filtered'][0]['sku'],  
            'amount' => '0',
            'reference' => $referencia,
            'transacctionId' => time() . rand(1000, 9999)
        ];
    $resp = processPayment($tk, $payment);
    $resp = $resp['response']['payload'];
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
}

//Obtener todos los servicios ej uso: servicios=1
if (isset($_GET['servicios'])){
    // $tk = authenticateAPI();
    $servicesData = fetchAllServices($tk);
    echo json_encode($servicesData, JSON_UNESCAPED_UNICODE);
}

//Obtener servicios filtrados ej uso: servicio=telmex
if (isset($_GET['servicio'])){
    $servicio = $_GET['servicio'];
    $servicio = strtoupper($servicio);
    // $tk = authenticateAPI();
    $servicesData = fetchAllServices($tk);
    $servicesData['filtered'] = filterServices($servicesData['all'], explode(',', $servicio));
    
    echo json_encode($servicesData['filtered'], JSON_UNESCAPED_UNICODE);
}
