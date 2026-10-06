<?php
header('Content-Type: application/json');

// Función para detectar la región según el número telefónico
function detectarRegion($numeroTelefono) {
    // Limpiar el número (quitar espacios, guiones, paréntesis)
    $numero = preg_replace('/[^0-9]/', '', $numeroTelefono);
    
    // Si tiene 10 dígitos y empieza con dígitos, extraer LADA (primeros 3 dígitos)
    if (strlen($numero) == 10) {
        $lada = substr($numero, 0, 3);
        
        // Mapeo de LADAs a regiones Telcel
        $regionesMap = [
            // Región 1: Noroeste
            '644' => 1, '662' => 1, '631' => 1, '637' => 1, '638' => 1, '641' => 1, '642' => 1, '643' => 1, '645' => 1, '647' => 1, '651' => 1, '653' => 1, '658' => 1, '659' => 1, '668' => 1, '687' => 1, '694' => 1, '698' => 1, '669' => 1,
            // Región 2: Norte
            '614' => 2, '656' => 2, '659' => 2, '625' => 2, '626' => 2, '627' => 2, '628' => 2, '629' => 2, '635' => 2, '639' => 2, '648' => 2, '649' => 2, '871' => 2, '872' => 2, '873' => 2, '877' => 2, '878' => 2,
            // Región 3: Noreste
            '818' => 3, '81' => 3, '867' => 3, '868' => 3, '869' => 3, '821' => 3, '823' => 3, '824' => 3, '826' => 3, '828' => 3, '831' => 3, '832' => 3, '833' => 3, '834' => 3, '835' => 3, '836' => 3, '841' => 3, '844' => 3, '861' => 3, '862' => 3, '866' => 3, '878' => 3, '879' => 3, '892' => 3, '894' => 3, '897' => 3, '899' => 3,
            // Región 4: Occidente
            '33' => 4, '312' => 4, '313' => 4, '314' => 4, '315' => 4, '316' => 4, '317' => 4, '321' => 4, '322' => 4, '341' => 4, '342' => 4, '343' => 4, '345' => 4, '346' => 4, '347' => 4, '348' => 4, '349' => 4, '351' => 4, '352' => 4, '353' => 4, '354' => 4, '355' => 4, '356' => 4, '357' => 4, '358' => 4, '359' => 4, '371' => 4, '372' => 4, '373' => 4, '374' => 4, '375' => 4, '376' => 4, '377' => 4, '378' => 4, '381' => 4, '382' => 4, '383' => 4, '384' => 4, '385' => 4, '386' => 4, '387' => 4, '388' => 4, '389' => 4, '391' => 4, '392' => 4, '393' => 4, '394' => 4, '395' => 4, '411' => 4, '412' => 4, '413' => 4, '414' => 4, '415' => 4, '417' => 4, '418' => 4, '419' => 4, '421' => 4, '423' => 4, '424' => 4, '425' => 4, '426' => 4, '427' => 4, '428' => 4, '429' => 4, '431' => 4, '432' => 4, '433' => 4, '434' => 4, '435' => 4, '436' => 4, '437' => 4, '438' => 4, '443' => 4, '447' => 4, '448' => 4, '449' => 4, '452' => 4, '453' => 4, '454' => 4, '455' => 4, '456' => 4, '457' => 4, '458' => 4, '459' => 4, '461' => 4, '462' => 4, '463' => 4, '464' => 4, '465' => 4, '466' => 4, '467' => 4, '468' => 4, '469' => 4, '471' => 4, '472' => 4, '473' => 4, '474' => 4, '475' => 4, '476' => 4, '477' => 4, '478' => 4, '479' => 4, '481' => 4, '482' => 4, '483' => 4, '485' => 4, '486' => 4, '487' => 4, '488' => 4, '489' => 4, '492' => 4, '493' => 4, '494' => 4, '495' => 4, '496' => 4, '497' => 4, '498' => 4, '499' => 4,
            // Región 5: Centro
            '55' => 5, '56' => 5, '722' => 5, '771' => 5, '777' => 5, '595' => 5, '591' => 5, '594' => 5, '597' => 5, '599' => 5, '711' => 5, '712' => 5, '713' => 5, '714' => 5, '715' => 5, '716' => 5, '717' => 5, '718' => 5, '719' => 5, '721' => 5, '723' => 5, '724' => 5, '725' => 5, '726' => 5, '727' => 5, '728' => 5, '732' => 5, '734' => 5, '735' => 5, '736' => 5, '737' => 5, '738' => 5, '739' => 5, '741' => 5, '742' => 5, '743' => 5, '744' => 5, '745' => 5, '746' => 5, '747' => 5, '748' => 5, '751' => 5, '753' => 5, '754' => 5, '755' => 5, '756' => 5, '757' => 5, '758' => 5, '759' => 5, '761' => 5, '762' => 5, '763' => 5, '764' => 5, '765' => 5, '766' => 5, '767' => 5, '768' => 5, '769' => 5, '771' => 5, '772' => 5, '773' => 5, '774' => 5, '775' => 5, '776' => 5, '778' => 5, '779' => 5, '781' => 5, '782' => 5, '783' => 5, '784' => 5, '785' => 5, '786' => 5, '789' => 5, '791' => 5, '797' => 5,
            // Región 6: Sur
            '222' => 6, '223' => 6, '224' => 6, '225' => 6, '227' => 6, '228' => 6, '229' => 6, '231' => 6, '232' => 6, '233' => 6, '235' => 6, '236' => 6, '237' => 6, '238' => 6, '241' => 6, '243' => 6, '244' => 6, '245' => 6, '246' => 6, '247' => 6, '248' => 6, '249' => 6, '271' => 6, '272' => 6, '273' => 6, '274' => 6, '275' => 6, '276' => 6, '278' => 6, '279' => 6, '281' => 6, '282' => 6, '283' => 6, '284' => 6, '285' => 6, '287' => 6, '288' => 6, '292' => 6, '294' => 6, '295' => 6, '296' => 6, '297' => 6,
            // Región 7: Golfo-Centro
            '229' => 7, '278' => 7, '921' => 7, '923' => 7, '924' => 7, '925' => 7, '951' => 7, '953' => 7, '954' => 7, '958' => 7, '971' => 7, '972' => 7, '981' => 7, '982' => 7, '983' => 7, '984' => 7, '985' => 7, '986' => 7, '987' => 7, '991' => 7, '992' => 7, '993' => 7, '994' => 7, '995' => 7, '996' => 7, '997' => 7,
            // Región 8
            '861' => 8, '867' => 8, '891' => 8,
            // Región 9
            '686' => 9, '664' => 9, '665' => 9, '646' => 9
        ];
        
        // Buscar región
        if (isset($regionesMap[$lada])) {
            return $regionesMap[$lada];
        }
        
        // Intentar con primeros 2 dígitos si no se encontró con 3
        $ladaCorta = substr($numero, 0, 2);
        if (isset($regionesMap[$ladaCorta])) {
            return $regionesMap[$ladaCorta];
        }
    }
    
    // Por defecto retornar región 6 si no se detecta
    return 6;
}

try {
    // Obtener número telefónico del request
    $numeroTelefono = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $requestData = json_decode(file_get_contents('php://input'), true);
        $numeroTelefono = $requestData['phone'] ?? $requestData['numero'] ?? $requestData['telefono'] ?? '';
    } else if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $numeroTelefono = $_GET['phone'] ?? $_GET['numero'] ?? $_GET['telefono'] ?? '';
    }
    
    // Detectar región
    $regionDetectada = detectarRegion($numeroTelefono);
    error_log("Número: $numeroTelefono - Región detectada: $regionDetectada");
    
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
    
    error_log("Iniciando obtención de Internet Amigo Región $regionDetectada...");

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
    
    $internetAmigoR6Services = [];
    
    foreach ($allServices as $service) {
        $name = strtolower($service['name'] ?? '');
        $sku = strtoupper($service['sku'] ?? '');
        $minAmount = floatval($service['minAmount'] ?? 0);
        $categoryId = intval($service['categoryId'] ?? 0);
        $description = strtolower($service['description'] ?? '');
        
        $isInternetAmigo = false;
        $regionSufijo = '-R' . $regionDetectada;
        
        if ($categoryId === 44) {
            if (strpos($sku, 'INT') === 0 && strpos($sku, $regionSufijo) !== false) {
                $isInternetAmigo = true;
            }
            elseif (stripos($name, 'internet amigo') !== false && 
                   (stripos($name, 'region ' . $regionDetectada) !== false || stripos($description, 'region ' . $regionDetectada) !== false)) {
                $isInternetAmigo = true;
            }
        }
        
        if ($isInternetAmigo && $minAmount > 0) {
            $internetAmigoR6Services[] = [
                'sku' => $service['sku'] ?? '',
                'name' => $service['name'] ?? 'Internet Amigo',
                'description' => $service['description'] ?? '',
                'minAmount' => $minAmount,
                'maxAmount' => floatval($service['maxAmount'] ?? 0),
                'categoryId' => $categoryId,
                'discount' => floatval($service['discount'] ?? 0),
                'formatted_name' => 'Internet Amigo $' . number_format($minAmount, 0) . ' MXN R' . $regionDetectada,
                'price_display' => '$' . number_format($minAmount, 0)
            ];
        }
    }
    
    usort($internetAmigoR6Services, function($a, $b) {
        return $a['minAmount'] - $b['minAmount'];
    });
    
    error_log("Total Internet Amigo Región $regionDetectada encontrados: " . count($internetAmigoR6Services));
    
    echo json_encode([
        'success' => true,
        'services' => $internetAmigoR6Services,
        'total' => count($internetAmigoR6Services),
        'region' => $regionDetectada,
        'phone' => $numeroTelefono,
        'message' => 'Internet Amigo Región ' . $regionDetectada . ' cargados exitosamente (' . count($internetAmigoR6Services) . ' servicios)'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'services' => [],
        'error' => $e->getMessage(),
        'message' => 'Error al cargar Internet Amigo'
    ]);
}
?>
