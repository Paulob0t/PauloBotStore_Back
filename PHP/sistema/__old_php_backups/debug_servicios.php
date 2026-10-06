<?php

function valueContainsBait($value)
{
  if (is_string($value)) {
    return stripos($value, 'telcel') !== false;
  }

  if (is_array($value)) {
    foreach ($value as $item) {
      if (valueContainsBait($item)) {
        return true;
      }
    }
  }

  return false;
}

function collectBaitItems($node, &$results, &$seen)
{
  if (!is_array($node)) {
    return;
  }

  $isAssoc = array_keys($node) !== range(0, count($node) - 1);

  if ($isAssoc && valueContainsBait($node)) {
    $hash = md5(json_encode($node));
    if (!isset($seen[$hash])) {
      $seen[$hash] = true;
      $results[] = $node;
    }
  }

  foreach ($node as $child) {
    if (is_array($child)) {
      collectBaitItems($child, $results, $seen);
    }
  }
}

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
  echo 'cURL error en login: ' . curl_error($curl) . "\n";
} else {
  echo "HTTP code login: $http_code\n";
}

curl_close($curl);

$loginData = json_decode($response, true);

if (!is_array($loginData) || empty($loginData['payload']['accessToken'])) {
    echo "No se pudo obtener el token de acceso.\n";
    echo "Respuesta login: $response\n";
    exit(1);
}

$token = $loginData['payload']['accessToken'];
$baitMatches = array();
$seenMatches = array();
$maxPages = 20;

for ($i = 0; $i < $maxPages; $i++) {
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
      CURLOPT_URL => "https://prontipagos-api-dev.domainscm.com/prontipagos-external-api-ws/ws/protected/v1/product/list?page=$i&pageSize=100",
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'GET',
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
    $pageHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

    if (curl_errno($curl)) {
      echo "cURL error en pagina $i: " . curl_error($curl) . "\n";
      curl_close($curl);
      continue;
    }
    
    curl_close($curl);

    if ($pageHttpCode !== 200) {
      echo "HTTP code pagina $i: $pageHttpCode\n";
      continue;
    }

    $pageData = json_decode($response, true);
    if (!is_array($pageData)) {
      echo "Respuesta JSON invalida en pagina $i\n";
      continue;
    }

    $beforeCount = count($baitMatches);
    collectBaitItems($pageData, $baitMatches, $seenMatches);
    $afterCount = count($baitMatches);

    if ($afterCount > $beforeCount) {
      echo "Pagina $i: se encontraron " . ($afterCount - $beforeCount) . " coincidencias BAIT.\n";
    }

    if (empty($pageData['payload'])) {
      break;
    }
}

  echo "\n===== RESULTADOS BAIT =====\n";
  if (empty($baitMatches)) {
    echo "No se encontraron servicios/productos con BAIT.\n";
  } else {
    echo "Total coincidencias BAIT: " . count($baitMatches) . "\n";
    echo json_encode($baitMatches, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
  }
