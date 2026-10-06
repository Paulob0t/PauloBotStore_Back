<?php
/**
 * Configuración de Mercado Pago
 * VendingBox Payment Integration (Point)
 * - Producción por defecto (sin fallback a TEST para evitar 401)
 * - Soporta variables de entorno (recomendado)
 */

// =============================
// 1) MODO (prod por defecto)
// =============================
define('MP_MODE', getenv('MP_MODE') ?: 'production'); // 'production' | 'test'

// =============================
// 2) CREDENCIALES (usar ENV)
//    export MP_ACCESS_TOKEN="APP_USR-..."
//    export MP_PUBLIC_KEY="APP_USR-..." (opcional; para front)
//    export MP_ACCESS_TOKEN_TEST="TEST-..." (si usas modo test)
//    export MP_PUBLIC_KEY_TEST="TEST-..." (opcional; para front en test)
// =============================
define('MP_ACCESS_TOKEN_PROD', 'APP_USR-2922010686761411-110313-cb69c477c6a52cd92059cad5e1772df2-665547137');
define('MP_PUBLIC_KEY_PROD', 'APP_USR-e0c4e7bb-0b07-4d36-b476-7cae6733b5be');


define('MP_ACCESS_TOKEN_TEST', getenv('MP_ACCESS_TOKEN_TEST') ?: 'TEST-REEMPLAZA_AQUI');
define('MP_PUBLIC_KEY_TEST',   getenv('MP_PUBLIC_KEY_TEST')   ?: 'TEST-REEMPLAZA_AQUI');

// =============================
// 3) CLIENT (solo si usas OAuth)
// =============================
define('MP_CLIENT_ID',     getenv('MP_CLIENT_ID')     ?: '2922010686761411');
define('MP_CLIENT_SECRET', getenv('MP_CLIENT_SECRET') ?: 'REEMPLAZA_CLIENT_SECRET');

// =============================
// 4) URLs de retorno / webhook
// =============================
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http")
          . "://" . $_SERVER['HTTP_HOST']
          . rtrim(dirname($_SERVER['PHP_SELF']), '/\\');

define('MP_SUCCESS_URL',      getenv('MP_SUCCESS_URL')      ?: $base_url . '/pago_aprobado.php');
define('MP_FAILURE_URL',      getenv('MP_FAILURE_URL')      ?: $base_url . '/pago_rechazado.php');
define('MP_PENDING_URL',      getenv('MP_PENDING_URL')      ?: $base_url . '/pago_pendiente.php');
define('MP_NOTIFICATION_URL', getenv('MP_NOTIFICATION_URL') ?: $base_url . '/mercadopago_webhook.php');

// =============================
// 5) Helpers de credenciales
// =============================
function getMercadoPagoAccessToken(): string {
    if (MP_MODE === 'production') {
        if (!MP_ACCESS_TOKEN_PROD || MP_ACCESS_TOKEN_PROD === 'APP_USR-REEMPLAZA_AQUI') {
            http_response_code(500);
            die('Falta MP_ACCESS_TOKEN de PRODUCCIÓN (APP_USR-...)');
        }
        return MP_ACCESS_TOKEN_PROD;
    }
    // Modo test (solo si TÚ lo pones explícitamente en MP_MODE)
    if (!MP_ACCESS_TOKEN_TEST || MP_ACCESS_TOKEN_TEST === 'TEST-REEMPLAZA_AQUI') {
        http_response_code(500);
        die('Falta MP_ACCESS_TOKEN_TEST (TEST-...) en modo test');
    }
    return MP_ACCESS_TOKEN_TEST;
}

function getMercadoPagoPublicKey(): string {
    if (MP_MODE === 'production') {
        return MP_PUBLIC_KEY_PROD ?: '';
    }
    return MP_PUBLIC_KEY_TEST ?: '';
}

// =============================
// 6) (Opcional) Base URL API MP
// =============================
// Para Point Integration API:
define('MP_POINT_API_BASE', 'https://api.mercadopago.com/point/integration-api');

// =============================
// 7) Seguridad básica
// =============================
// - No expongas tokens en front.
// - Usa ENV en el server.
// - Si rotaste tus claves, actualízalas aquí/ENV.
