<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciando Sistema VendigBox...</title>
    <meta http-equiv="refresh" content="2;url=index.php">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .startup-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
        }
        .logo-container {
            margin-bottom: 30px;
        }
        .logo-container img {
            max-width: 200px;
            height: auto;
        }
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3em;
        }
        .status-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        .status-item:last-child {
            border-bottom: none;
        }
        .status-ok {
            color: #28a745;
        }
        .status-checking {
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="startup-card">
        <div class="logo-container">
            <img src="images/logo.png" alt="VendigBox" onerror="this.style.display='none'">
        </div>
        
        <div class="mb-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
        
        <h3 class="mb-4">🚀 Iniciando Sistema</h3>
        
        <div class="status-list text-start mb-4">
            <div class="status-item">
                <span><i class="bi bi-hdd-fill"></i> Sistema de archivos</span>
                <span class="status-ok"><i class="bi bi-check-circle-fill"></i></span>
            </div>
            <div class="status-item">
                <span><i class="bi bi-coin"></i> Validador de monedas</span>
                <span class="status-checking" id="monedero-status">
                    <span class="spinner-border spinner-border-sm"></span>
                </span>
            </div>
            <div class="status-item">
                <span><i class="bi bi-printer-fill"></i> Sistema de impresión</span>
                <span class="status-ok"><i class="bi bi-check-circle-fill"></i></span>
            </div>
            <div class="status-item">
                <span><i class="bi bi-cart-fill"></i> Sistema de ventas</span>
                <span class="status-ok"><i class="bi bi-check-circle-fill"></i></span>
            </div>
        </div>
        
        <p class="text-muted">Redirigiendo a la tienda...</p>
        
        <div class="progress mt-3" style="height: 5px;">
            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                 role="progressbar" style="width: 100%"></div>
        </div>
    </div>
    
    <script>
        // Verificar estado del listener del monedero
        setTimeout(() => {
            fetch('monedero_api.php?action=get_saldo')
                .then(response => response.json())
                .then(data => {
                    const statusEl = document.getElementById('monedero-status');
                    if (data.success !== undefined) {
                        statusEl.innerHTML = '<i class="bi bi-check-circle-fill text-success"></i>';
                        statusEl.className = 'status-ok';
                    } else {
                        statusEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill text-warning"></i>';
                        statusEl.className = 'status-checking';
                    }
                })
                .catch(() => {
                    const statusEl = document.getElementById('monedero-status');
                    statusEl.innerHTML = '<i class="bi bi-dash-circle-fill text-secondary"></i>';
                    statusEl.className = 'text-secondary';
                });
        }, 500);
        
        // Intentar iniciar el listener (solo en Windows)
        if (navigator.platform.indexOf('Win') > -1) {
            // Crear elemento para ejecutar el VBS
            const link = document.createElement('a');
            link.href = 'file:///' + window.location.pathname.replace('/startup.php', '/iniciar_listener_invisible.vbs');
            link.style.display = 'none';
            document.body.appendChild(link);
            
            // Nota: Por seguridad del navegador, no se puede ejecutar archivos .vbs directamente
            // El usuario debe ejecutar manualmente iniciar_listener_invisible.vbs
            console.log('💡 Para activar el monedero automáticamente, ejecuta: iniciar_listener_invisible.vbs');
        }
    </script>
</body>
</html>
