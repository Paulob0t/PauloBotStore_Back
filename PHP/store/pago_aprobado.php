<?php
// Recuperar datos de la sesión
session_start();

// Verificar si es respuesta de Mercado Pago
$is_mercadopago = isset($_GET['collection_status']) || isset($_GET['payment_id']);

if ($is_mercadopago) {
    // Parámetros de Mercado Pago
    $payment_id = $_GET['payment_id'] ?? null;
    $status = $_GET['status'] ?? $_GET['collection_status'] ?? 'unknown';
    $external_reference = $_GET['external_reference'] ?? 'N/A';
    $merchant_order_id = $_GET['merchant_order_id'] ?? null;
    $preference_id = $_GET['preference_id'] ?? null;
    
    $folio = $external_reference;
    $metodo_pago = 'Mercado Pago (' . $status . ')';
    $total = '0.00'; // Puedes consultar el monto desde la API si lo necesitas
    $despachos = [];
    
    // Log para debug
    error_log("MP Success - Payment ID: $payment_id, Status: $status, Reference: $external_reference");
} else {
    // Flujo original
    $folio = isset($_GET['folio']) ? $_GET['folio'] : (isset($_SESSION['ultimo_folio']) ? $_SESSION['ultimo_folio'] : 'N/A');
    $total = isset($_GET['total']) ? $_GET['total'] : (isset($_SESSION['ultimo_total']) ? $_SESSION['ultimo_total'] : '0.00');
    $metodo_pago = isset($_GET['metodo']) ? $_GET['metodo'] : (isset($_SESSION['ultimo_metodo']) ? $_SESSION['ultimo_metodo'] : 'N/A');
    $despachos = isset($_SESSION['ultimos_despachos']) ? $_SESSION['ultimos_despachos'] : [];
}

// Limpiar sesión después de usar
unset($_SESSION['ultimo_folio']);
unset($_SESSION['ultimo_total']);
unset($_SESSION['ultimo_metodo']);
unset($_SESSION['ultimos_despachos']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Aprobado - VendingBox</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
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
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .success-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: #38ef7d;
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

        .success-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
        }

        h1 {
            color: #11998e;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
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

        .despachos-section {
            margin-top: 20px;
            background: #e8f5e9;
            border-radius: 10px;
            padding: 20px;
        }

        .despachos-section h3 {
            color: #2e7d32;
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .despacho-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            text-align: left;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .despacho-item:last-child {
            margin-bottom: 0;
        }

        .despacho-producto {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        .despacho-detalles {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            color: #666;
        }

        .ubicacion-badge {
            background: #11998e;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-family: monospace;
        }

        .countdown {
            margin-top: 30px;
            padding: 20px;
            background: #fff3cd;
            border-radius: 10px;
            color: #856404;
        }

        .countdown-text {
            font-size: 14px;
            margin-bottom: 10px;
        }

        .countdown-timer {
            font-size: 24px;
            font-weight: bold;
        }

        .btn-home {
            margin-top: 20px;
            padding: 15px 40px;
            background: #11998e;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-home:hover {
            background: #0d7a6f;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-print {
            margin-top: 15px;
            padding: 12px 30px;
            background: #FFD700;
            color: #000;
            border: 2px solid #FFC700;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-block;
        }

        .btn-print:hover {
            background: #FFC700;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 215, 0, 0.3);
        }

        .dispensando-alert {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            text-align: left;
        }

        .dispensando-alert strong {
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Icono de éxito -->
        <div class="success-icon">
            <svg viewBox="0 0 52 52">
                <path d="M14 27l7 7 17-17"/>
            </svg>
        </div>

        <!-- Título -->
        <h1>¡Pago Aprobado!</h1>
        <p class="subtitle">Tu compra se procesó exitosamente</p>

        <!-- Información de la compra -->
        <div class="info-card">
            <div class="info-row">
                <span class="info-label">Folio:</span>
                <span class="info-value"><?php echo htmlspecialchars($folio); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Total Pagado:</span>
                <span class="info-value">$<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Método de Pago:</span>
                <span class="info-value"><?php echo htmlspecialchars($metodo_pago); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span class="info-value"><?php echo date('d/m/Y H:i:s'); ?></span>
            </div>
        </div>

        <!-- Alerta de dispensación -->
        <div class="dispensando-alert">
            <strong>🤖 Dispensando productos...</strong><br>
            Por favor espera mientras la máquina despacha tus productos.
        </div>

        <?php if (!empty($despachos)): ?>
        <!-- Sección de despachos -->
        <div class="despachos-section">
            <h3>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="9" y1="9" x2="15" y2="15"></line>
                    <line x1="15" y1="9" x2="9" y2="15"></line>
                </svg>
                Productos a Recoger
            </h3>

            <?php foreach ($despachos as $despacho): ?>
            <div class="despacho-item">
                <div class="despacho-producto">
                    <?php echo htmlspecialchars($despacho['producto']); ?>
                </div>
                <div class="despacho-detalles">
                    <span>
                        <strong>Ubicación:</strong> 
                        <span class="ubicacion-badge"><?php echo htmlspecialchars($despacho['ubicacion']); ?></span>
                    </span>
                    <span>
                        <strong>Cantidad:</strong> <?php echo htmlspecialchars($despacho['cantidad']); ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Contador regresivo -->
        <div class="countdown">
            <div class="countdown-text">Redireccionando al inicio en:</div>
            <div class="countdown-timer" id="countdown">5</div>
        </div>

        <!-- Botón para ir al inicio -->
        <a href="index.php" class="btn-home">Ir al Inicio Ahora</a>
        
        <!-- 🖨️ Botón para reimprimir ticket -->
        <button onclick="reimprimirTicket()" class="btn-print" style="display:none;" id="btnPrint">
            🖨️ Reimprimir Ticket
        </button>
    </div>

    <!-- 🖨️ Scripts para impresión -->
    <script src="qz-tray.js"></script>
    <script src="js/modulos/print-ticket.js"></script>
    
    <script>
        // Contador regresivo
        let segundos = 5;
        const countdownElement = document.getElementById('countdown');

        const interval = setInterval(() => {
            segundos--;
            countdownElement.textContent = segundos;

            if (segundos <= 0) {
                clearInterval(interval);
                window.location.href = 'index.php';
            }
        }, 1000);

        // Limpiar localStorage del carrito
        localStorage.removeItem('cart');
        localStorage.removeItem('serviceCart');
        
        // 🖨️ IMPRIMIR TICKET AUTOMÁTICAMENTE
        (async function imprimirTicketAuto() {
            try {
                // Verificar si hay datos del ticket en sessionStorage
                const ticketDataStr = sessionStorage.getItem('ultimo_ticket_data');
                
                if (!ticketDataStr) {
                    console.warn('⚠️ No hay datos de ticket para imprimir');
                    return;
                }
                
                const ticketData = JSON.parse(ticketDataStr);
                
                // Verificar que QZ Tray y PrintTicket estén disponibles
                if (typeof qz === 'undefined') {
                    console.error('❌ QZ Tray no está disponible');
                    return;
                }
                
                if (typeof PrintTicket === 'undefined') {
                    console.error('❌ PrintTicket no está disponible');
                    return;
                }
                
                // Mostrar botón de reimprimir
                document.getElementById('btnPrint').style.display = 'inline-block';
                
                // Intentar imprimir con timeout
                const timeoutPromise = new Promise((_, reject) => 
                    setTimeout(() => reject(new Error('Timeout')), 5000)
                );
                
                await Promise.race([
                    PrintTicket.imprimirTicketVenta(ticketData),
                    timeoutPromise
                ]);
                
                // Limpiar datos del ticket después de imprimir
                sessionStorage.removeItem('ultimo_ticket_data');
                
            } catch (error) {
                // Error en impresión automática
            }
        })();
        
        // 🖨️ Función para reimprimir manualmente
        async function reimprimirTicket() {
            try {
                const ticketDataStr = sessionStorage.getItem('ultimo_ticket_data');
                
                if (!ticketDataStr) {
                    alert('No hay datos de ticket disponibles');
                    return;
                }
                
                const ticketData = JSON.parse(ticketDataStr);
                
                await PrintTicket.imprimirTicketVenta(ticketData);
                
            } catch (error) {
                console.error('❌ Error al reimprimir:', error);
                alert('Error al reimprimir el ticket: ' + error.message);
            }
        }
    </script>
</body>
</html>
