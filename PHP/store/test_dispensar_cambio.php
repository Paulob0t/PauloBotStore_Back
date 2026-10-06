<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Dispensar Cambio - MEI CF 7000</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            border-radius: 15px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .denomination-btn {
            font-size: 24px;
            font-weight: bold;
            padding: 20px;
            margin: 10px;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .denomination-btn:hover {
            transform: scale(1.05);
        }
        #log-area {
            background: #1e1e1e;
            color: #00ff00;
            font-family: 'Courier New', monospace;
            padding: 15px;
            border-radius: 8px;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .test-controls {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .info-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header text-center">
                <h2><i class="bi bi-cash-coin"></i> Test Dispensar Cambio</h2>
                <p class="mb-0">MEI CF 7000 - Puerto COM5</p>
            </div>
            <div class="card-body">
                
                <div class="info-box">
                    <h5><i class="bi bi-info-circle"></i> Información</h5>
                    <ul class="mb-0">
                        <li>Protocolo del hardware VendigBox</li>
                        <li>Puerto configurado: <strong>COM5</strong> @ 9600 baud</li>
                        <li>Comandos: <code>INT000[HEX]003\r\n</code></li>
                        <li>Ejemplos: <code>INT000A003</code> (10 pesos), <code>INT00014003</code> (20 pesos)</li>
                        <li>Espera 200ms entre comandos</li>
                    </ul>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <button class="btn btn-warning btn-lg w-100" onclick="hardwareReset()">
                            <i class="bi bi-arrow-clockwise"></i> RESET Hardware
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-success btn-lg w-100" onclick="hardwareHabilitar()">
                            <i class="bi bi-check-circle"></i> Habilitar
                        </button>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-danger btn-lg w-100" onclick="hardwareDeshabilitar()">
                            <i class="bi bi-x-circle"></i> Deshabilitar
                        </button>
                    </div>
                </div>

                <div class="test-controls">
                    <h5 class="mb-3"><i class="bi bi-calculator"></i> Monto a Dispensar</h5>
                    <div class="input-group mb-3">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control form-control-lg" id="monto-input" 
                               placeholder="Ingresa monto" min="1" max="1000" step="1" value="15">
                        <button class="btn btn-success btn-lg" onclick="dispensarCambio()">
                            <i class="bi bi-cash-stack"></i> Dispensar
                        </button>
                    </div>

                    <div class="row">
                        <div class="col">
                            <button class="btn btn-primary denomination-btn w-100" onclick="dispensarRapido(5)">
                                $5
                            </button>
                        </div>
                        <div class="col">
                            <button class="btn btn-info denomination-btn w-100" onclick="dispensarRapido(10)">
                                $10
                            </button>
                        </div>
                        <div class="col">
                            <button class="btn btn-warning denomination-btn w-100" onclick="dispensarRapido(20)">
                                $20
                            </button>
                        </div>
                        <div class="col">
                            <button class="btn btn-danger denomination-btn w-100" onclick="dispensarRapido(50)">
                                $50
                            </button>
                        </div>
                    </div>
                </div>

                <h5 class="mb-3"><i class="bi bi-terminal"></i> Log de Comandos</h5>
                <div id="log-area">Esperando comandos...</div>

                <div class="mt-3 text-center">
                    <button class="btn btn-secondary" onclick="limpiarLog()">
                        <i class="bi bi-trash"></i> Limpiar Log
                    </button>
                </div>

            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5><i class="bi bi-wrench"></i> Comandos de Prueba Manual</h5>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <input type="text" class="form-control" id="comando-manual" 
                           placeholder="Ejemplo: INT000A003" value="INT0000000">
                    <button class="btn btn-outline-primary" onclick="enviarComandoManual()">
                        <i class="bi bi-send"></i> Enviar Comando
                    </button>
                </div>
                <small class="text-muted">
                    Comandos: <code>INT0000000</code> (reset), 
                    <code>INT0000001</code> (habilitar), 
                    <code>INT0000002</code> (deshabilitar),
                    <code>INT000A003</code> (dispensar 10 pesos)
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const logArea = document.getElementById('log-area');
        let logCount = 0;

        function log(mensaje, tipo = 'info') {
            const timestamp = new Date().toLocaleTimeString('es-MX', { hour12: false });
            const iconos = {
                'info': 'ℹ️',
                'success': '✅',
                'error': '❌',
                'warning': '⚠️',
                'command': '📤',
                'response': '📥'
            };
            const icono = iconos[tipo] || '•';
            
            logCount++;
            logArea.innerHTML += `\n[${timestamp}] ${icono} ${mensaje}`;
            logArea.scrollTop = logArea.scrollHeight;
        }

        function limpiarLog() {
            logArea.innerHTML = 'Log limpiado.\n';
            logCount = 0;
        }

        async function dispensarCambio() {
            const monto = parseFloat(document.getElementById('monto-input').value);
            
            if (!monto || monto <= 0) {
                log('Monto inválido', 'error');
                return;
            }

            log(`Dispensando cambio de $${monto}...`, 'info');

            try {
                const formData = new FormData();
                formData.append('monto', monto);

                const response = await fetch('monedero_api.php?action=dispensar_cambio', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    log(`✅ ÉXITO: Cambio dispensado correctamente`, 'success');
                    log(`💰 Monto: $${data.monto}`, 'info');
                    log(`📊 Desglose:`, 'info');
                    
                    for (const [denom, cant] of Object.entries(data.desglose)) {
                        log(`   • $${denom} × ${cant} = $${denom * cant}`, 'info');
                    }
                    
                    if (data.comandos) {
                        log(`📤 Comandos enviados:`, 'command');
                        data.comandos.forEach(cmd => {
                            log(`   ${cmd.comando} (Total: $${cmd.total})`, 'command');
                        });
                    }
                } else {
                    log(`❌ ERROR: ${data.mensaje}`, 'error');
                    if (data.errores) {
                        data.errores.forEach(err => log(`   ${err}`, 'error'));
                    }
                }
            } catch (error) {
                log(`❌ Error de conexión: ${error.message}`, 'error');
            }
        }

        function dispensarRapido(monto) {
            document.getElementById('monto-input').value = monto;
            dispensarCambio();
        }

        async function enviarComandoManual() {
            const comando = document.getElementById('comando-manual').value.trim();
            
            if (!comando) {
                log('Ingresa un comando', 'warning');
                return;
            }

            log(`📤 Enviando comando manual: ${comando}`, 'command');

            try {
                // Agregar \r\n si no lo tiene
                let comandoCompleto = comando;
                if (!comando.includes('\\r\\n')) {
                    comandoCompleto = comando + '\\r\\n';
                }

                const response = await fetch('monedero_test_command.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `comando=${encodeURIComponent(comandoCompleto)}`
                });

                const data = await response.json();

                if (data.success) {
                    log(`✅ Comando enviado correctamente`, 'success');
                    if (data.output) {
                        log(`📥 Respuesta: ${data.output}`, 'response');
                    }
                } else {
                    log(`❌ Error: ${data.mensaje || 'Error desconocido'}`, 'error');
                }
            } catch (error) {
                log(`❌ Error de conexión: ${error.message}`, 'error');
            }
        }

        async function hardwareReset() {
            log('🔄 Enviando RESET al hardware...', 'command');
            try {
                const response = await fetch('monedero_api.php?action=hardware_reset');
                const data = await response.json();
                if (data.success) {
                    log('✅ Hardware reseteado correctamente', 'success');
                } else {
                    log(`❌ Error: ${data.mensaje}`, 'error');
                }
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        }

        async function hardwareHabilitar() {
            log('✅ Habilitando monedero/billetero...', 'command');
            try {
                const response = await fetch('monedero_api.php?action=hardware_habilitar');
                const data = await response.json();
                if (data.success) {
                    log('✅ Monedero/billetero habilitado', 'success');
                } else {
                    log(`❌ Error: ${data.mensaje}`, 'error');
                }
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        }

        async function hardwareDeshabilitar() {
            log('🚫 Deshabilitando monedero/billetero...', 'command');
            try {
                const response = await fetch('monedero_api.php?action=hardware_deshabilitar');
                const data = await response.json();
                if (data.success) {
                    log('✅ Monedero/billetero deshabilitado', 'success');
                } else {
                    log(`❌ Error: ${data.mensaje}`, 'error');
                }
            } catch (error) {
                log(`❌ Error: ${error.message}`, 'error');
            }
        }

        // Log inicial
        log('Sistema de prueba iniciado', 'success');
        log('Listo para dispensar cambio', 'info');
    </script>
</body>
</html>
