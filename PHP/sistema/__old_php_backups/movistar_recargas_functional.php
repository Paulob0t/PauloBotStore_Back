<?php
header('Content-Type: application/json');

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
    
    error_log("Iniciando obtención de servicios Movistar...");

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
            
            if (empty($pageServices)) {
                foreach ($pageData['payload'] as $key => $value) {
                    if (is_array($value) && !empty($value)) {
                        if (isset($value[0]) && is_array($value[0])) {
                            if (isset($value[0]['sku']) || isset($value[0]['name']) || isset($value[0]['id'])) {
                                $pageServices = $value;
                                break;
                            }
                        }
                    }
                }
            }
        }
        
        if (!empty($pageServices)) {
            $allServices = array_merge($allServices, $pageServices);
        } else {
            break;
        }
        
        $pageNum++;
        usleep(100000);
        
    } while ($pageNum < $maxPages && !empty($pageServices));
    
    error_log("Total servicios obtenidos: " . count($allServices));
    
    $movistarServices = [];
    
    foreach ($allServices as $service) {
        $name = strtolower($service['name'] ?? '');
        $sku = strtoupper($service['sku'] ?? '');
        $minAmount = floatval($service['minAmount'] ?? 0);
        $categoryId = intval($service['categoryId'] ?? 0);
        $description = strtolower($service['description'] ?? '');
        
        $isMovistar = false;
        
        if ($categoryId === 34) {
            if (strpos($sku, 'MOVIMXN') !== false) {
                $isMovistar = true;
            }
            elseif (stripos($name, 'movistar') !== false) {
                $isMovistar = true;
            }
        }
        
        if ($isMovistar && $minAmount > 0) {
            $movistarServices[] = [
                'sku' => $service['sku'] ?? '',
                'name' => $service['name'] ?? 'Recarga Movistar',
                'description' => $service['description'] ?? '',
                'minAmount' => $minAmount,
                'maxAmount' => floatval($service['maxAmount'] ?? 0),
                'categoryId' => $categoryId,
                'discount' => floatval($service['discount'] ?? 0),
                'formatted_name' => 'Recarga Movistar $' . number_format($minAmount, 0) . ' MXN',
                'price_display' => '$' . number_format($minAmount, 0)
            ];
        }
    }
    
    usort($movistarServices, function($a, $b) {
        return $a['minAmount'] - $b['minAmount'];
    });
    
    error_log("Total servicios Movistar encontrados: " . count($movistarServices));
    
    echo json_encode([
        'success' => true,
        'services' => $movistarServices,
        'total' => count($movistarServices),
        'message' => 'Servicios Movistar cargados exitosamente (' . count($movistarServices) . ' servicios)'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'services' => [],
        'error' => $e->getMessage(),
        'message' => 'Error al cargar servicios Movistar'
    ]);
}
?>

