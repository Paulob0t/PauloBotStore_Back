<?php
/**
 * DIAGNÓSTICO DEL MONEDERO
 * Este script muestra EXACTAMENTE lo que el monedero está enviando
 * Úsalo para ver qué formato tiene la salida
 * 
 * Fecha: 21-01-2026
 */

// Configuración
$puerto = $_GET['puerto'] ?? 'COM5';
$baudrate = $_GET['baudrate'] ?? 9600;
$databits = $_GET['databits'] ?? 8;
$parity = $_GET['parity'] ?? 'None';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Monedero en Tiempo Real</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #1e1e1e; color: #d4d4d4; padding: 20px; }
        .console { 
            background: #000; 
            color: #0f0; 
            padding: 20px; 
            border-radius: 10px; 
            font-family: 'Courier New', monospace;
            height: 500px;
            overflow-y: auto;
            border: 2px solid #0f0;
        }
        .line { margin: 5px 0; }
        .hex { color: #ff0; }
        .ascii { color: #0ff; }
        .timestamp { color: #888; }
        .status { 
            padding: 10px; 
            border-radius: 5px; 
            margin-bottom: 20px; 
        }
        .status.connected { background: #2d5016; border: 2px solid #4caf50; }
        .status.disconnected { background: #5d1616; border: 2px solid #f44336; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1>🔍 Diagnóstico de Monedero - Tiempo Real</h1>
        <p>Este script muestra TODO lo que el monedero envía por el puerto serial</p>

        <div class="row mb-3">
            <div class="col-md-3">
                <label>Puerto:</label>
                <select class="form-control" id="puerto">
                    <?php for($i=1; $i<=8; $i++): ?>
                        <option value="COM<?=$i?>" <?=$puerto=="COM$i"?'selected':''?>>COM<?=$i?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Baud Rate:</label>
                <select class="form-control" id="baudrate">
                    <option value="2400">2400</option>
                    <option value="4800">4800</option>
                    <option value="9600" selected>9600</option>
                    <option value="19200">19200</option>
                    <option value="38400">38400</option>
                    <option value="57600">57600</option>
                    <option value="115200">115200</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button class="btn btn-success btn-block w-100" id="btnIniciar">🚀 Iniciar Diagnóstico</button>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button class="btn btn-danger btn-block w-100" id="btnDetener" disabled>⏹️ Detener</button>
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button class="btn btn-warning btn-block w-100" id="btnLimpiar">🧹 Limpiar Consola</button>
            </div>
        </div>

        <div class="status disconnected" id="status">
            <h5>❌ Desconectado</h5>
            <p>Haz click en "Iniciar Diagnóstico" para empezar</p>
        </div>

        <div class="card bg-dark">
            <div class="card-header bg-success text-white">
                <h5>📟 Consola de Datos (Inserta monedas y mira qué aparece)</h5>
            </div>
            <div class="card-body">
                <div class="console" id="console">
                    <div class="line">🔍 Esperando conexión...</div>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-3">
            <strong>💡 Instrucciones:</strong><br>
            1. Click en "Iniciar Diagnóstico"<br>
            2. Inserta una moneda en el monedero<br>
            3. Observa qué texto/números aparecen en la consola<br>
            4. Copia el formato exacto para configurar el sistema<br>
            <br>
            <strong>📝 Ejemplos de lo que podrías ver:</strong><br>
            • <code>COIN:10</code> - Formato con etiqueta<br>
            • <code>10</code> - Solo el número<br>
            • <code>0A 00 10</code> - Formato hexadecimal<br>
            • <code>$10.00</code> - Con símbolo de moneda
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let diagnosticoActivo = false;
        let pollInterval = null;

        function addToConsole(mensaje, tipo = 'normal') {
            const timestamp = new Date().toLocaleTimeString();
            const console = $('#console');
            
            let cssClass = '';
            if (tipo === 'hex') cssClass = 'hex';
            else if (tipo === 'ascii') cssClass = 'ascii';
            
            const line = `<div class="line ${cssClass}"><span class="timestamp">[${timestamp}]</span> ${mensaje}</div>`;
            console.append(line);
            console.scrollTop(console[0].scrollHeight);
        }

        function iniciarDiagnostico() {
            const puerto = $('#puerto').val();
            const baudrate = $('#baudrate').val();

            diagnosticoActivo = true;
            $('#btnIniciar').prop('disabled', true);
            $('#btnDetener').prop('disabled', false);
            $('#status').removeClass('disconnected').addClass('connected').html(`
                <h5>✅ Conectado a ${puerto} @ ${baudrate} baud</h5>
                <p>Escuchando datos... Inserta una moneda ahora</p>
            `);

            addToConsole('🚀 Iniciando diagnóstico en ' + puerto + ' @ ' + baudrate + ' baud');
            addToConsole('💰 Inserta una moneda y observa lo que aparece...');

            // Iniciar polling continuo
            pollInterval = setInterval(() => {
                if (!diagnosticoActivo) return;

                $.ajax({
                    url: 'monedero_diagnostico_backend.php',
                    method: 'POST',
                    data: {
                        accion: 'leer',
                        puerto: puerto,
                        baudrate: baudrate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.datos && response.datos.length > 0) {
                            response.datos.forEach(dato => {
                                // Mostrar ASCII
                                addToConsole('📥 ASCII: <strong style="color:#0f0; font-size:18px;">' + dato.ascii + '</strong>', 'ascii');
                                
                                // Mostrar HEX
                                addToConsole('📦 HEX: ' + dato.hex, 'hex');
                                
                                // Mostrar bytes individuales
                                if (dato.bytes) {
                                    addToConsole('🔢 BYTES: ' + dato.bytes);
                                }
                                
                                addToConsole('---');
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        addToConsole('❌ Error: ' + error);
                    }
                });
            }, 300); // Check cada 300ms
        }

        function detenerDiagnostico() {
            diagnosticoActivo = false;
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
            }

            $('#btnIniciar').prop('disabled', false);
            $('#btnDetener').prop('disabled', true);
            $('#status').removeClass('connected').addClass('disconnected').html(`
                <h5>⏹️ Detenido</h5>
                <p>Diagnóstico finalizado</p>
            `);

            addToConsole('⏹️ Diagnóstico detenido');

            // Cerrar puerto en el backend
            $.post('monedero_diagnostico_backend.php', {
                accion: 'cerrar'
            });
        }

        $('#btnIniciar').click(iniciarDiagnostico);
        $('#btnDetener').click(detenerDiagnostico);
        $('#btnLimpiar').click(function() {
            $('#console').html('<div class="line">🧹 Consola limpiada</div>');
        });

        // Detener al cerrar la ventana
        $(window).on('beforeunload', function() {
            if (diagnosticoActivo) {
                detenerDiagnostico();
            }
        });
    </script>
</body>
</html>
