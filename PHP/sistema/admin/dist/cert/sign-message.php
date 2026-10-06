<?php
// cert/sign-message.php

// Leer JSON del fetch
$input = json_decode(file_get_contents('php://input'), true);
$data  = isset($input['request']) ? $input['request'] : '';

if ($data === '') {
    http_response_code(400);
    echo "Missing request";
    exit;
}

// Cargar la llave privada generada por QZ (private-key.pem)
$privateKeyPath = __DIR__ . '/private-key.pem';
$privateKeyPem  = file_get_contents($privateKeyPath);

if ($privateKeyPem === false) {
    http_response_code(500);
    echo "No se pudo leer private-key.pem";
    exit;
}

$privateKey = openssl_pkey_get_private($privateKeyPem);

if ($privateKey === false) {
    http_response_code(500);
    echo "Llave privada inválida";
    exit;
}

// Firmar con SHA1 (default de QZ Tray 2.x; se puede cambiar p.ej. a SHA256)
if (!openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA1)) {
    http_response_code(500);
    echo "No se pudo firmar";
    exit;
}

// Regresar la firma en base64
echo base64_encode($signature);
