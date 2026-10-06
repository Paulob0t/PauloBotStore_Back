<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔧 Panel de Debugging - Sistema de Cambio</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 30px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card p {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            text-align: center;
            border: none;
            cursor: pointer;
            width: 100%;
        }
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success { background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); }
        .btn-warning { background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); }
        .btn-danger { background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%); }
        .btn-info { background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%); }
        .icon {
            font-size: 1.5em;
        }
        .status {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .status-item {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            padding: 8px;
            background: white;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Panel de Debugging - Sistema de Cambio Automático</h1>
        
        <div class="grid">
            <!-- Test Básico -->
            <div class="card">
                <h2><span class="icon">🧪</span> Test Básico</h2>
                <p>Verifica que PHP está funcionando correctamente y que el archivo de inventario existe.</p>
                <a href="test_minimo.php?action=dispensar_cambio&monto=5" class="btn btn-success" target="_blank">
                    Ejecutar Test Mínimo
                </a>
            </div>

            <!-- Test de Routing -->
            <div class="card">
                <h2><span class="icon">🔀</span> Test de Routing</h2>
                <p>Verifica que las peticiones GET/POST llegan correctamente a monedero_api.php.</p>
                <a href="test_simple_routing.php" class="btn btn-info" target="_blank">
                    Ver Test de Routing
                </a>
            </div>

            <!-- Test de Dispensado -->
            <div class="card">
                <h2><span class="icon">💰</span> Test de Dispensado</h2>
                <p>Prueba completa de la API de dispensado con análisis de respuesta JSON.</p>
                <a href="test_dispensar_debug.php?monto=5" class="btn btn-warning" target="_blank">
                    Test con $5
                </a>
                <a href="test_dispensar_debug.php?monto=14" class="btn btn-warning" target="_blank" style="margin-top: 10px;">
                    Test con $14
                </a>
            </div>

            <!-- Logs en Tiempo Real -->
            <div class="card">
                <h2><span class="icon">📜</span> Logs en Tiempo Real</h2>
                <p>Ver los logs de Apache en tiempo real con filtros para monedero/dispensar.</p>
                <a href="ver_logs_live.php" class="btn" target="_blank">
                    Ver Logs Live (Auto-refresh)
                </a>
            </div>

            <!-- Test Manual Completo -->
            <div class="card">
                <h2><span class="icon">🎮</span> Test Manual Completo</h2>
                <p>Interfaz completa con botones para probar dispensado con diferentes montos.</p>
                <a href="test_dispensar_cambio.php" class="btn btn-info" target="_blank">
                    Abrir Panel Completo
                </a>
            </div>

            <!-- Volver al Carrito -->
            <div class="card">
                <h2><span class="icon">🛒</span> Carrito de Compras</h2>
                <p>Volver a la interfaz principal del carrito para probar el flujo completo.</p>
                <a href="cart.php" class="btn btn-success">
                    Ir al Carrito
                </a>
            </div>
        </div>

        <div class="status">
            <h2 style="color: #667eea; margin-bottom: 15px;">📊 Estado del Sistema</h2>
            <div class="status-item">
                <strong>Archivo de API:</strong>
                <span><?php echo file_exists('monedero_api.php') ? '✅ Existe' : '❌ No encontrado'; ?></span>
            </div>
            <div class="status-item">
                <strong>Directorio de Logs:</strong>
                <span><?php echo is_dir('admin/dist/logs') ? '✅ Existe' : '❌ No existe'; ?></span>
            </div>
            <div class="status-item">
                <strong>Inventario de Monedas:</strong>
                <span><?php echo file_exists('admin/dist/logs/coin_inventory.log') ? '✅ Existe' : '⚠️ Se creará automáticamente'; ?></span>
            </div>
            <div class="status-item">
                <strong>PHP Version:</strong>
                <span><?php echo phpversion(); ?></span>
            </div>
            <div class="status-item">
                <strong>Extensión JSON:</strong>
                <span><?php echo extension_loaded('json') ? '✅ Activa' : '❌ No disponible'; ?></span>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; color: white;">
            <p><strong>💡 Consejo:</strong> Abre "Logs en Tiempo Real" en una ventana aparte y luego ejecuta los tests.</p>
            <p style="margin-top: 10px; opacity: 0.8;">Los logs se actualizan automáticamente cada 3 segundos.</p>
        </div>
    </div>
</body>
</html>
