<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control - VendigBox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .control-panel {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border: none;
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        .action-btn {
            padding: 40px 20px;
            border-radius: 15px;
            border: 3px solid transparent;
            transition: all 0.3s;
            cursor: pointer;
            background: white;
            text-decoration: none;
            display: block;
        }
        .action-btn:hover {
            border-color: #667eea;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transform: scale(1.05);
        }
        .action-btn i {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-container img {
            max-width: 250px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="control-panel">
        <div class="logo-container">
            <img src="images/logo.png" alt="VendigBox" onerror="this.style.display='none'">
            <h1 class="text-white mt-3">Panel de Control</h1>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pc-display"></i> Estado del Sistema</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <div class="p-3">
                                    <i class="bi bi-hdd-network-fill fs-1 text-primary"></i>
                                    <h6 class="mt-2">Apache</h6>
                                    <span class="badge bg-success" id="apache-status">Verificando...</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3">
                                    <i class="bi bi-database-fill fs-1 text-info"></i>
                                    <h6 class="mt-2">MySQL</h6>
                                    <span class="badge bg-success" id="mysql-status">Verificando...</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3">
                                    <i class="bi bi-coin fs-1 text-warning"></i>
                                    <h6 class="mt-2">Monedero</h6>
                                    <span class="badge bg-warning" id="monedero-status">Verificando...</span>
                                </div>
                            </div>
                            <div class="col-md-3 mb-3">
                                <div class="p-3">
                                    <i class="bi bi-printer-fill fs-1 text-secondary"></i>
                                    <h6 class="mt-2">Impresora</h6>
                                    <span class="badge bg-success" id="printer-status">OK</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Tienda -->
            <div class="col-md-4">
                <a href="index.php" class="action-btn text-center text-decoration-none text-dark">
                    <i class="bi bi-shop text-primary"></i>
                    <h4 class="mb-2">🛒 Tienda</h4>
                    <p class="text-muted mb-0">Sistema de ventas</p>
                </a>
            </div>

            <!-- Test Dispensado -->
            <div class="col-md-4">
                <a href="test_dispensar_cambio.php" class="action-btn text-center text-decoration-none text-dark">
                    <i class="bi bi-cash-coin text-warning"></i>
                    <h4 class="mb-2">💰 Test Cambio</h4>
                    <p class="text-muted mb-0">Probar dispensado</p>
                </a>
            </div>

            <!-- Logs -->
            <div class="col-md-4">
                <a href="ver_logs.php" class="action-btn text-center text-decoration-none text-dark">
                    <i class="bi bi-file-text text-info"></i>
                    <h4 class="mb-2">📋 Logs</h4>
                    <p class="text-muted mb-0">Ver registros</p>
                </a>
            </div>

            <!-- Admin -->
            <div class="col-md-4">
                <a href="admin/" class="action-btn text-center text-decoration-none text-dark">
                    <i class="bi bi-gear-fill text-success"></i>
                    <h4 class="mb-2">⚙️ Admin</h4>
                    <p class="text-muted mb-0">Panel administrativo</p>
                </a>
            </div>

            <!-- Diagnóstico -->
            <div class="col-md-4">
                <a href="verificar_sistema.bat" class="action-btn text-center text-decoration-none text-dark">
                    <i class="bi bi-wrench text-secondary"></i>
                    <h4 class="mb-2">🔧 Diagnóstico</h4>
                    <p class="text-muted mb-0">Verificar sistema</p>
                </a>
            </div>

            <!-- Documentación -->
            <div class="col-md-4">
                <a href="GUIA_MEI_CF7000.md" target="_blank" class="action-btn text-center text-decoration-none text-dark">
                    <i class="bi bi-book text-danger"></i>
                    <h4 class="mb-2">📖 Docs</h4>
                    <p class="text-muted mb-0">Documentación</p>
                </a>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-terminal"></i> Acciones Rápidas</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <button class="btn btn-success w-100" onclick="iniciarListener()">
                                    <i class="bi bi-play-circle"></i> Iniciar Listener
                                </button>
                            </div>
                            <div class="col-md-4 mb-3">
                                <button class="btn btn-danger w-100" onclick="detenerListener()">
                                    <i class="bi bi-stop-circle"></i> Detener Listener
                                </button>
                            </div>
                            <div class="col-md-4 mb-3">
                                <button class="btn btn-warning w-100" onclick="resetHardware()">
                                    <i class="bi bi-arrow-clockwise"></i> Reset Hardware
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Verificar estado de los servicios
        async function checkStatus() {
            // Check Monedero
            try {
                const response = await fetch('monedero_api.php?action=get_saldo');
                const data = await response.json();
                document.getElementById('monedero-status').textContent = data.success !== undefined ? '✅ Activo' : '⚠️ Error';
                document.getElementById('monedero-status').className = data.success !== undefined ? 'badge bg-success' : 'badge bg-danger';
            } catch {
                document.getElementById('monedero-status').textContent = '❌ Inactivo';
                document.getElementById('monedero-status').className = 'badge bg-danger';
            }

            // Check Apache (si la página carga, Apache está OK)
            document.getElementById('apache-status').textContent = '✅ Activo';
            document.getElementById('apache-status').className = 'badge bg-success';

            // Check MySQL
            try {
                const response = await fetch('get_products.php');
                const data = await response.json();
                document.getElementById('mysql-status').textContent = Array.isArray(data) ? '✅ Activo' : '⚠️ Error';
                document.getElementById('mysql-status').className = Array.isArray(data) ? 'badge bg-success' : 'badge bg-warning';
            } catch {
                document.getElementById('mysql-status').textContent = '❌ Inactivo';
                document.getElementById('mysql-status').className = 'badge bg-danger';
            }
        }

        async function iniciarListener() {
            alert('Para iniciar el listener, ejecuta: iniciar_listener_invisible.vbs\nO ejecuta: iniciar_sistema_completo.bat');
        }

        async function detenerListener() {
            if (confirm('¿Detener el listener del monedero?')) {
                alert('Ejecuta: detener_listener.bat');
            }
        }

        async function resetHardware() {
            if (confirm('¿Enviar comando RESET al hardware?')) {
                try {
                    const response = await fetch('monedero_api.php?action=hardware_reset');
                    const data = await response.json();
                    alert(data.success ? '✅ Hardware reseteado' : '❌ Error: ' + data.mensaje);
                } catch (error) {
                    alert('❌ Error de conexión');
                }
            }
        }

        // Verificar estado al cargar
        checkStatus();
        setInterval(checkStatus, 5000); // Actualizar cada 5 segundos
    </script>
</body>
</html>
