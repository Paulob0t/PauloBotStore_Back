<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

function authenticateAPI() {
    error_log("Iniciando autenticación...");
    $url = 'https://prontipagos-api-dev.domainscm.com/prontipagos-external-api-ws/ws/v1/auth/login';
    $data = array(
        'username' => 'api.desarrollo',
        'password' => '1hFdcv4G*'
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Accept: */*',
        'User-Agent: PostmanRuntime/7.36.1',
        'Cache-Control: no-cache',
        'Postman-Token: ' . uniqid()
    ));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $debugInfo = [
        'url' => $url,
        'httpCode' => $httpCode,
        'curlError' => $error,
        'response' => $response,
        'requestInfo' => $info,
        'requestData' => $data
    ];
    error_log("Debug de autenticación: " . json_encode($debugInfo, JSON_PRETTY_PRINT));

    if ($error) {
        echo json_encode([
            'success' => false,
            'error' => 'Error de conexión',
            'debug' => $debugInfo
        ]);
        exit;
    }

    if ($httpCode !== 200) {
        echo json_encode([
            'success' => false,
            'error' => 'Error HTTP: ' . $httpCode,
            'debug' => $debugInfo
        ]);
        exit;
    }

    $authData = json_decode($response, true);
    if (!$authData || !isset($authData['payload']['accessToken'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Respuesta inválida',
            'debug' => $debugInfo
        ]);
        exit;
    }

    return $authData['payload']['accessToken'];
}

function fetchAllServices($token) {
    error_log("Iniciando búsqueda de servicios con token: " . substr($token, 0, 10) . "...");
    $allServices = [];
    
    for ($page = 0; $page <= 6; $page++) {
        error_log("Obteniendo página " . $page);
        $url = "https://prontipagos-api-dev.domainscm.com/prontipagos-external-api-ws/ws/protected/v1/product/list?page={$page}&pageSize=100";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: PostmanRuntime/7.36.1',
            'Cache-Control: no-cache',
            'Postman-Token: ' . uniqid()
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($ch);
        curl_close($ch);

        $debugInfo = [
            'url' => $url,
            'httpCode' => $httpCode,
            'curlError' => $error,
            'response' => $response,
            'requestInfo' => $info,
            'page' => $page
        ];
        error_log("Debug de página " . $page . ": " . json_encode($debugInfo, JSON_PRETTY_PRINT));

        if ($error || $httpCode !== 200) {
            continue;
        }

        $data = json_decode($response, true);
        if (!$data || !isset($data['payload']['content'])) {
            continue;
        }

        $pageServices = [];
        
        if ($data && isset($data['payload'])) {
            $possibleKeys = ['content', 'data', 'products', 'services', 'items', 'list', 'results', 'productList'];
            
            foreach ($possibleKeys as $key) {
                if (isset($data['payload'][$key]) && is_array($data['payload'][$key])) {
                    $pageServices = $data['payload'][$key];
                    break;
                }
            }
        }

        foreach ($pageServices as $service) {
            $name = strtoupper($service['name'] ?? '');
            $categoryId = intval($service['categoryId'] ?? 0);

            if (strpos($name, 'TELEFONIA TELMEX') !== false) {
                $allServices[] = [
                    'sku' => $service['sku'] ?? '',
                    'name' => $service['name'] ?? '',
                    'description' => $service['description'] ?? '',
                    'categoryId' => $categoryId,
                    'discount' => floatval($service['discount'] ?? 0),
                    'fee' => floatval($service['fee'] ?? 0),
                    'maxAmount' => floatval($service['maxAmount'] ?? 0),
                    'minAmount' => floatval($service['minAmount'] ?? 0)
                ];
                error_log("Servicio Telmex encontrado: " . json_encode($service));
            }
        }
    }
    
    return $allServices;
}

function filterTelmexServices($services) {
    error_log("Filtrando servicios Telmex de " . count($services) . " servicios totales");
    $telmexServices = [];
    
    foreach ($services as $service) {
        error_log("Evaluando servicio: " . json_encode($service));
        $name = strtoupper($service['name'] ?? '');
        
        $allowedNames = [
            'TELEFONIA TELMEX'
        ];
        
        if (in_array($service['name'], $allowedNames)) {
            $telmexServices[] = $service;
        }
    }
    
    usort($telmexServices, function($a, $b) use ($allowedNames) {
        return array_search($a['name'], $allowedNames) - array_search($b['name'], $allowedNames);
    });
    
    return $telmexServices;
}

try {
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
      CURLOPT_POSTFIELDS => '{"username":"api.desarrollo","password":"1hFdcv4G*"}',
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_HTTPHEADER => array(
        'Content-Type: application/json',
        'Accept: */*',
        'User-Agent: PostmanRuntime/7.36.1',
        'Cache-Control: no-cache',
        'Postman-Token: ' . uniqid()  
      ),
    ));

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
        throw new Exception('cURL error: ' . curl_error($curl));
    }

    curl_close($curl);

    $authResponse = json_decode($response, true);
    if (!isset($authResponse['payload']['accessToken'])) {
        throw new Exception('No se pudo obtener token de autenticación');
    }
    
    $token = $authResponse['payload']['accessToken'];
    
    $allServices = [];
    $pageNum = 0;
    $maxPages = 7;
    
    error_log("Iniciando obtención de servicios Telmex...");

    do {
        error_log("Obteniendo página $pageNum...");
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://prontipagos-api-dev.domainscm.com/prontipagos-external-api-ws/ws/protected/v1/product/list?page=$pageNum&pageSize=100",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'GET',
          CURLOPT_SSL_VERIFYPEER => false,
          CURLOPT_SSL_VERIFYHOST => false,
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Accept: */*',
            'User-Agent: PostmanRuntime/7.36.1',
            'Cache-Control: no-cache',
            'Postman-Token: ' . uniqid(),
            "Authorization: Bearer $token"
          ),
        ));
        
        $response = curl_exec($curl);
        if (curl_errno($curl)) {
            error_log("Error cURL en página $pageNum: " . curl_error($curl));
            curl_close($curl);
            break;
        }
        
        $pageData = json_decode($response, true);
        curl_close($curl);
        
        $pageServices = [];
        
        if ($pageData && isset($pageData['payload'])) {
            $possibleKeys = ['content', 'data', 'products', 'services', 'items', 'list', 'results', 'productList'];
            
            foreach ($possibleKeys as $key) {
                if (isset($pageData['payload'][$key]) && is_array($pageData['payload'][$key])) {
                    $pageServices = $pageData['payload'][$key];
                    break;
                }
            }
        }
        
        foreach ($pageServices as $service) {
            if (isset($service['name']) && in_array($service['name'], [
                'TELEFONIA TELMEX'
            ])) {
                $allServices[] = [
                    'sku' => $service['sku'] ?? '',
                    'name' => $service['name'] ?? '',
                    'description' => $service['description'] ?? '',
                    'categoryId' => intval($service['categoryId'] ?? 0),
                    'discount' => floatval($service['discount'] ?? 0),
                    'fee' => floatval($service['fee'] ?? 0),
                    'maxAmount' => floatval($service['maxAmount'] ?? 0),
                    'minAmount' => floatval($service['minAmount'] ?? 0)
                ];
                error_log("Servicio Telmex encontrado: " . json_encode($service));
            }
        }
        
        $pageNum++;
        usleep(100000);
        
    } while ($pageNum < $maxPages);

    usort($allServices, function($a, $b) {
        $order = [
            'TELEFONIA TELMEX' => 1 
        ];
        return ($order[$a['name']] ?? 999) - ($order[$b['name']] ?? 999);
    });
    
    echo json_encode([
        'success' => true,
        'services' => $allServices,
        'total' => count($allServices),
        'debug' => [
            'token_obtained' => true,
            'services_found' => count($allServices)
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug' => [
            'error_message' => $e->getMessage(),
            'error_trace' => $e->getTraceAsString()
        ]
    ]);
}
?>
