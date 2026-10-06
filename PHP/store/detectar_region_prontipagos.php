<?php
header('Content-Type: application/json');

// ==========================
// CONFIG
// ==========================
$USERNAME = 'api.desarrollo';
$PASSWORD = '1hFdcv4G*';

// ==========================
// LOGIN
// ==========================
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL => 'https://prontipagos-api-dev.domainscm.com/prontipagos-external-api-ws/ws/v1/auth/login',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode([
        'username' => $USERNAME,
        'password' => $PASSWORD
    ]),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
]);

$response = curl_exec($curl);
curl_close($curl);

$auth = json_decode($response, true);
if (!isset($auth['payload']['accessToken'])) {
    echo json_encode(['error' => 'No se pudo autenticar']);
    exit;
}

$token = $auth['payload']['accessToken'];

// ==========================
// OBTENER PRODUCTOS
// ==========================
$all = [];
$page = 0;

do {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://prontipagos-api-dev.domainscm.com/prontipagos-external-api-ws/ws/protected/v1/product/list?page=$page&pageSize=100",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $res = curl_exec($curl);
    curl_close($curl);

    $json = json_decode($res, true);
    $data = $json['payload']['content'] ?? [];

    if (empty($data)) break;

    $all = array_merge($all, $data);
    $page++;
} while ($page < 10);

// ==========================
// DETECTAR REGIONES
// ==========================
$regiones = [];

foreach ($all as $s) {
    $sku = strtoupper($s['sku'] ?? '');
    if (preg_match('/-R([1-9])\b/', $sku, $m)) {
        $r = intval($m[1]);
        $regiones[$r] = ($regiones[$r] ?? 0) + 1;
    }
}

ksort($regiones);

// ==========================
// RESPUESTA
// ==========================
echo json_encode([
    'success' => true,
    'regiones_disponibles' => $regiones,
    'explicacion' => 'Estas son las regiones que Prontipagos reconoce SEGÚN SU CATÁLOGO'
], JSON_PRETTY_PRINT);
