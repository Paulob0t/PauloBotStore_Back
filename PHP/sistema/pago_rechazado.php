<?php
// Recuperar datos de la sesión
session_start();

// Verificar si es respuesta de Mercado Pago
$is_mercadopago = isset($_GET['collection_status']) || isset($_GET['payment_id']);

if ($is_mercadopago) {
    // Parámetros de Mercado Pago
    $payment_id = $_GET['payment_id'] ?? null;
    $status = $_GET['status'] ?? $_GET['collection_status'] ?? 'rejected';
    $status_detail = $_GET['status_detail'] ?? 'unknown';
    $external_reference = $_GET['external_reference'] ?? null;
    
    $metodo_pago = 'Mercado Pago';
    
    // Mensajes personalizados según el error
    $error_messages = [
        'cc_rejected_insufficient_amount' => 'Fondos insuficientes',
        'cc_rejected_bad_filled_card_number' => 'Número de tarjeta incorrecto',
        'cc_rejected_bad_filled_date' => 'Fecha de vencimiento incorrecta',
        'cc_rejected_bad_filled_security_code' => 'Código de seguridad incorrecto',
        'cc_rejected_call_for_authorize' => 'Debes autorizar el pago con tu banco',
        'cc_rejected_card_disabled' => 'Tarjeta deshabilitada',
        'cc_rejected_duplicated_payment' => 'Ya realizaste un pago similar',
        'cc_rejected_high_risk' => 'Pago rechazado por seguridad',
        'cc_rejected_max_attempts' => 'Superaste el número de intentos permitidos',
    ];
    
    $error_mensaje = $error_messages[$status_detail] ?? "Pago rechazado: $status_detail";
    
    error_log("MP Failure - Payment ID: $payment_id, Status: $status, Detail: $status_detail");
} else {
    // Flujo original
    $error_mensaje = isset($_GET['error']) ? $_GET['error'] : (isset($_SESSION['error_pago']) ? $_SESSION['error_pago'] : 'Error desconocido al procesar el pago');
    $metodo_pago = isset($_GET['metodo']) ? $_GET['metodo'] : (isset($_SESSION['metodo_pago_fallido']) ? $_SESSION['metodo_pago_fallido'] : 'N/A');
}

// Limpiar sesión después de usar
unset($_SESSION['error_pago']);
unset($_SESSION['metodo_pago_fallido']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Rechazado - VendingBox</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            text-align: center;
            animation: shake 0.5s ease-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }

        .error-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #e74c3c;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: scaleIn 0.5s ease-out 0.2s both;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }
            to {
                transform: scale(1);
            }
        }

        .error-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        h1 {
            color: #e74c3c;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .error-card {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }

        .error-card h3 {
            color: #856404;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .error-message {
            color: #333;
            line-height: 1.6;
        }

        .info-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #dee2e6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
            font-weight: 600;
        }

        .suggestions {
            background: #e8f4f8;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }

        .suggestions h3 {
            color: #1976d2;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .suggestions ul {
            margin: 0;
            padding-left: 20px;
        }

        .suggestions li {
            color: #333;
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .countdown {
            margin-top: 30px;
            padding: 20px;
            background: #f8d7da;
            border-radius: 10px;
            color: #721c24;
        }

        .countdown-text {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .countdown-timer {
            font-size: 24px;
            font-weight: bold;
        }

        .buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
        }

        .btn-primary:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .btn-secondary {
            background: #95a5a6;
            color: white;
        }

        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .support-info {
            margin-top: 30px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            font-size: 14px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Icono de error -->
        <div class="error-icon">
            <svg viewBox="0 0 52 52">
                <line x1="16" y1="16" x2="36" y2="36"/>
                <line x1="36" y1="16" x2="16" y2="36"/>
            </svg>
        </div>

        <!-- Título -->
        <h1>Pago Rechazado</h1>
        <p class="subtitle">No se pudo completar tu transacción</p>

        <!-- Mensaje de error -->
        <div class="error-card">
            <h3>⚠️ Motivo del rechazo:</h3>
            <div class="error-message">
                <?php echo htmlspecialchars($error_mensaje); ?>
            </div>
        </div>

        <!-- Información del intento -->
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Método de Pago:</span>
                <span class="info-value"><?php echo htmlspecialchars($metodo_pago); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha y Hora:</span>
                <span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Estado:</span>
                <span class="info-value" style="color: #e74c3c;">❌ Rechazado</span>
            </div>
        </div>

        <!-- Sugerencias -->
        <div class="suggestions">
            <h3>💡 ¿Qué puedes hacer?</h3>
            <ul>
                <li>Verifica que tu tarjeta tenga fondos suficientes</li>
                <li>Asegúrate de que los datos de la tarjeta sean correctos</li>
                <li>Intenta con otro método de pago</li>
                <li>Verifica tu conexión a internet</li>
                <li>Contacta con tu banco si el problema persiste</li>
            </ul>
        </div>

        <!-- Contador regresivo -->
        <div class="countdown">
            <div class="countdown-text">Regresando al carrito en:</div>
            <div class="countdown-timer" id="countdown">7</div>
        </div>

        <!-- Botones -->
        <div class="buttons">
            <a href="cart.php" class="btn btn-primary">Volver al Carrito</a>
            <a href="index.php" class="btn btn-secondary">Ir al Inicio</a>
        </div>

        <!-- Información de soporte -->
        <div class="support-info">
            <strong>¿Necesitas ayuda?</strong><br>
            Contacta a nuestro equipo de soporte para resolver cualquier problema.
        </div>
    </div>

    <script>
        // Contador regresivo
        let segundos = 7;
        const countdownElement = document.getElementById('countdown');

        const interval = setInterval(() => {
            segundos--;
            countdownElement.textContent = segundos;

            if (segundos <= 0) {
                clearInterval(interval);
                window.location.href = 'cart.php';
            }
        }, 1000);
    </script>
</body>
</html>
