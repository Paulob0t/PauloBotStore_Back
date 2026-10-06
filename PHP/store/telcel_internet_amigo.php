<?php
header('Content-Type: application/json');

/**
 * 📱 TELCEL INTERNET AMIGO - TODOS los SKU sin filtro de región
 * Los SKU se mostrarán de TODAS las regiones
 */

try {
    $curl = curl_init();

    // Autenticar
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
    
    error_log("Iniciando obtención de Internet Amigo (TODAS las regiones)...");

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
    
    $internetAmigoServices = [];
    $montosUnicos = []; // Para evitar duplicados del mismo monto
    
    foreach ($allServices as $service) {
        $name = strtolower($service['name'] ?? '');
        $sku = strtoupper($service['sku'] ?? '');
        $minAmount = floatval($service['minAmount'] ?? 0);
        $categoryId = intval($service['categoryId'] ?? 0);
        $description = strtolower($service['description'] ?? '');
        
        $isInternetAmigo = false;
        
        // Filtrar SOLO Internet Amigo categoría 44 sin región
        // Buscar SKU que empiece con INT seguido de números, SIN -R
        if ($categoryId === 44) {
            // EXCLUIR si menciona "REGION" en el nombre
            if (preg_match('/region\s*\d+/i', $name)) {
                continue;
            }
            
            // EXCLUIR si tiene -R seguido de número en el SKU
            if (preg_match('/-R\d+/', $sku)) {
                continue;
            }
            
            // Solo si empieza con INT y no hemos agregado ese monto
            if (strpos($sku, 'INT') === 0 && !isset($montosUnicos[$minAmount])) {
                $isInternetAmigo = true;
            }
        }
        
        if ($isInternetAmigo && $minAmount > 0) {
            $montosUnicos[$minAmount] = true; // Marcar este monto como ya agregado
            $region = 'General';
            
            // Limpiar description quitando menciones de región
            $description = $service['description'] ?? '';
            $description = preg_replace('/\bregion\s*\d+\b/i', '', $description);
            $description = preg_replace('/\bR\d+\b/', '', $description);
            $description = trim(preg_replace('/\s+/', ' ', $description));
            
            $internetAmigoServices[] = [
                'sku' => $service['sku'] ?? '',
                'name' => $service['name'] ?? 'Internet Amigo',
                'description' => $description,
                'minAmount' => $minAmount,
                'maxAmount' => floatval($service['maxAmount'] ?? 0),
                'categoryId' => $categoryId,
                'discount' => floatval($service['discount'] ?? 0),
                'region' => $region,
                'formatted_name' => 'Internet Amigo $' . number_format($minAmount, 0) . ' MXN',
                'price_display' => '$' . number_format($minAmount, 0)
            ];
        }
    }
    
    // Ordenar por monto y luego por región
    usort($internetAmigoServices, function($a, $b) {
        if ($a['minAmount'] === $b['minAmount']) {
            return strcmp($a['region'], $b['region']);
        }
        return $a['minAmount'] - $b['minAmount'];
    });
    
    error_log("Total Internet Amigo encontrados (SIN región específica): " . count($internetAmigoServices));
    
    echo json_encode([
        'success' => true,
        'services' => $internetAmigoServices,
        'total' => count($internetAmigoServices),
        'message' => 'Internet Amigo (generales sin región) cargados exitosamente (' . count($internetAmigoServices) . ' servicios)',
        'note' => 'Solo se muestran Internet generales - se excluyen SKU con región específica (-R1, -R2, etc.)'
    ]);

} catch (Exception $e) {
    error_log("Error en telcel_internet_amigo.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'services' => [],
        'error' => $e->getMessage(),
        'message' => 'Error al cargar Internet Amigo'
    ]);
}
?>
