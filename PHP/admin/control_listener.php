<?php 
session_start(); 

if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
    header('Location: login.php');
    exit(); 
}
?>

<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Control Listener Monedero - Eshop Admin</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <!-- inject:css -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    <style>
        .control-panel {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            border-radius: 15px;
            color: white;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .status-indicator {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            transition: all 0.3s ease;
        }
        
        .status-detenido {
            background: rgba(220, 53, 69, 0.3);
            border: 4px solid #dc3545;
        }
        
        .status-corriendo {
            background: rgba(40, 167, 69, 0.3);
            border: 4px solid #28a745;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 20px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
        
        .btn-control {
            padding: 15px 40px;
            font-size: 18px;
            border-radius: 10px;
            margin: 10px;
            min-width: 200px;
            font-weight: bold;
        }
        
        .console-log {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        
        .log-line {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #555;
            padding-left: 10px;
        }
        
        .log-info { border-color: #17a2b8; color: #17a2b8; }
        .log-success { border-color: #28a745; color: #28a745; }
        .log-error { border-color: #dc3545; color: #dc3545; }
        .log-warning { border-color: #ffc107; color: #ffc107; }
        
        .info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
    </style>
  </head>
  <body>
    <div class="container-scroller">
      <?php include 'navbar.php'; ?>
      
      <div class="container-fluid page-body-wrapper">
        <?php include 'menu.php'; ?>
        
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="row">
              <div class="col-md-12 grid-margin">
                <div class="row">
                  <div class="col-12">
                    <h3 class="font-weight-bold">Control de Listener de Monedero</h3>
                    <h6 class="font-weight-normal mb-0">
                      Administra el servicio de escucha del dispositivo monedero
                    </h6>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="control-panel">
                  <h2>Estado del Listener</h2>
                  
                  <div id="statusIndicator" class="status-indicator status-detenido">
                    <i class="mdi mdi-help"></i>
                  </div>
                  
                  <h3 id="statusText">Verificando...</h3>
                  <p id="statusDetails"></p>
                  
                  <div class="mt-4">
                    <button id="btnIniciar" class="btn btn-success btn-control" onclick="iniciarListener()">
                      <i class="mdi mdi-play"></i> Iniciar Listener
                    </button>
                    
                    <button id="btnDetener" class="btn btn-danger btn-control" onclick="detenerListener()">
                      <i class="mdi mdi-stop"></i> Detener Listener
                    </button>
                    
                    <button id="btnRefrescar" class="btn btn-info btn-control" onclick="verificarEstado()">
                      <i class="mdi mdi-refresh"></i> Actualizar Estado
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="info-card">
                  <h4><i class="mdi mdi-information"></i> ¿Qué hace el Listener?</h4>
                  <ul>
                    <li>Escucha el puerto serial del monedero</li>
                    <li>Detecta cuando se insertan monedas</li>
                    <li>Actualiza el saldo en tiempo real en cart.php</li>
                    <li>Se ejecuta en segundo plano (invisible)</li>
                  </ul>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="info-card">
                  <h4><i class="mdi mdi-lightbulb"></i> Consejos</h4>
                  <ul>
                    <li>Inicia el listener antes de usar el monedero</li>
                    <li>Solo debe haber una instancia corriendo</li>
                    <li>Si hay problemas, detén y reinicia</li>
                    <li>Verifica el log para diagnóstico</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <i class="mdi mdi-console"></i> Log de Actividad
                      <button class="btn btn-sm btn-outline-secondary float-end" onclick="limpiarLog()">
                        <i class="mdi mdi-delete"></i> Limpiar
                      </button>
                    </h4>
                    <div id="consoleLog" class="console-log">
                      <div class="log-line log-info">Sistema iniciado - esperando comandos...</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-body">
                    <h5>Enlaces Útiles</h5>
                    <a href="verificar_com.php" class="btn btn-sm btn-warning">
                      <i class="mdi mdi-wrench"></i> Verificar Configuración COM
                    </a>
                    <a href="test_listener.php" class="btn btn-sm btn-secondary">
                      <i class="mdi mdi-test-tube"></i> Test Diagnóstico
                    </a>
                    <a href="control_monedero.php" class="btn btn-sm btn-primary">
                      <i class="mdi mdi-remote"></i> Control de Comandos
                    </a>
                    <a href="../../monedero_diagnostico.php" target="_blank" class="btn btn-sm btn-info">
                      <i class="mdi mdi-bug"></i> Diagnóstico del Monedero
                    </a>
                    <a href="../../cart.php" target="_blank" class="btn btn-sm btn-success">
                      <i class="mdi mdi-cart"></i> Carrito de Compras
                    </a>
                  </div>
                </div>
              </div>
            </div>

          </div>
          <?php include 'footer.php'; ?>
        </div>
      </div>
    </div>

    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/template.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/todolist.js"></script>
    
    <script>
        let estadoActual = 'desconocido';
        
        // Verificar estado al cargar
        document.addEventListener('DOMContentLoaded', function() {
            verificarEstado();
            // Actualizar cada 5 segundos
            setInterval(verificarEstado, 5000);
        });
        
        function agregarLog(mensaje, tipo = 'info') {
            const consoleLog = document.getElementById('consoleLog');
            const timestamp = new Date().toLocaleTimeString();
            const logLine = document.createElement('div');
            logLine.className = `log-line log-${tipo}`;
            logLine.textContent = `[${timestamp}] ${mensaje}`;
            consoleLog.appendChild(logLine);
            consoleLog.scrollTop = consoleLog.scrollHeight;
        }
        
        function limpiarLog() {
            document.getElementById('consoleLog').innerHTML = 
                '<div class="log-line log-info">Log limpiado - esperando comandos...</div>';
        }
        
        function actualizarUI(estado, mensaje, detalles = '') {
            const indicator = document.getElementById('statusIndicator');
            const statusText = document.getElementById('statusText');
            const statusDetails = document.getElementById('statusDetails');
            const btnIniciar = document.getElementById('btnIniciar');
            const btnDetener = document.getElementById('btnDetener');
            
            estadoActual = estado;
            
            if (estado === 'corriendo') {
                indicator.className = 'status-indicator status-corriendo';
                indicator.innerHTML = '<i class="mdi mdi-check"></i>';
                statusText.textContent = 'Listener Activo';
                statusDetails.textContent = detalles;
                btnIniciar.disabled = true;
                btnDetener.disabled = false;
            } else if (estado === 'detenido') {
                indicator.className = 'status-indicator status-detenido';
                indicator.innerHTML = '<i class="mdi mdi-close"></i>';
                statusText.textContent = 'Listener Detenido';
                statusDetails.textContent = detalles;
                btnIniciar.disabled = false;
                btnDetener.disabled = true;
            } else {
                indicator.className = 'status-indicator status-detenido';
                indicator.innerHTML = '<i class="mdi mdi-help"></i>';
                statusText.textContent = 'Estado Desconocido';
                statusDetails.textContent = detalles;
                btnIniciar.disabled = false;
                btnDetener.disabled = false;
            }
        }
        
        function verificarEstado() {
            fetch('controlar_listener.php?accion=estado')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const estado = data.corriendo ? 'corriendo' : 'detenido';
                        const detalles = data.corriendo ? 
                            `${data.procesos} proceso(s) activo(s)` : 
                            'Ningún proceso detectado';
                        actualizarUI(estado, '', detalles);
                    } else {
                        agregarLog('Error al verificar estado: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    agregarLog('Error de conexión: ' + error, 'error');
                });
        }
        
        function iniciarListener() {
            agregarLog('Iniciando listener...', 'info');
            document.getElementById('btnIniciar').disabled = true;
            
            fetch('controlar_listener.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=iniciar'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    agregarLog('✓ ' + data.message, 'success');
                    actualizarUI('corriendo', data.message);
                } else {
                    agregarLog('✗ ' + data.message, 'error');
                    actualizarUI('detenido', data.message);
                }
                document.getElementById('btnIniciar').disabled = false;
            })
            .catch(error => {
                agregarLog('Error al iniciar: ' + error, 'error');
                document.getElementById('btnIniciar').disabled = false;
            });
        }
        
        function detenerListener() {
            agregarLog('Deteniendo listener...', 'info');
            document.getElementById('btnDetener').disabled = true;
            
            fetch('controlar_listener.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'accion=detener'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    agregarLog('✓ ' + data.message, 'success');
                    actualizarUI('detenido', data.message);
                } else {
                    agregarLog('✗ ' + data.message, 'warning');
                    actualizarUI('detenido', data.message);
                }
                document.getElementById('btnDetener').disabled = false;
            })
            .catch(error => {
                agregarLog('Error al detener: ' + error, 'error');
                document.getElementById('btnDetener').disabled = false;
            });
        }
    </script>
  </body>
</html>
