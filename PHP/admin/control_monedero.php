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
    <title>Control Monedero - Eshop Admin</title>
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
        .comando-btn {
            margin: 5px;
            min-width: 150px;
        }
        .console-output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            height: 300px;
            overflow-y: auto;
            margin-top: 20px;
        }
        .console-line {
            margin: 5px 0;
        }
        .console-line.sent {
            color: #4ec9b0;
        }
        .console-line.received {
            color: #dcdcaa;
        }
        .console-line.error {
            color: #f48771;
        }
        .console-line.success {
            color: #89d185;
        }
        .status-indicator {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 10px;
        }
        .status-disconnected {
            background: #dc3545;
        }
        .status-connected {
            background: #28a745;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
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
                    <h3 class="font-weight-bold">Control de Monedero</h3>
                    <h6 class="font-weight-normal mb-0">
                      Enviar comandos al dispositivo monedero por puerto serial
                      <a href="../../monedero_diagnostico.php" target="_blank" class="btn btn-sm btn-info ms-3">
                        🔍 Abrir Herramienta de Diagnóstico
                      </a>
                    </h6>
                  </div>
                </div>
              </div>
            </div>

            <!-- Configuración del Puerto -->
            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">
                      <span class="status-indicator status-disconnected" id="statusIndicator"></span>
                      Configuración de Puerto Serial
                    </h4>
                    <div class="row">
                      <div class="col-md-3">
                        <div class="form-group">
                          <label>Puerto COM</label>
                          <select class="form-control" id="puerto">
                            <option value="COM1">COM1</option>
                            <option value="COM2">COM2</option>
                            <option value="COM3">COM3</option>
                            <option value="COM4">COM4</option>
                            <option value="COM5" selected>COM5</option>
                            <option value="COM6">COM6</option>
                            <option value="COM7">COM7</option>
                            <option value="COM8">COM8</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Baud Rate</label>
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
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Data Bits</label>
                          <select class="form-control" id="databits">
                            <option value="7">7</option>
                            <option value="8" selected>8</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Parity</label>
                          <select class="form-control" id="parity">
                            <option value="none" selected>None</option>
                            <option value="even">Even</option>
                            <option value="odd">Odd</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>Terminador</label>
                          <select class="form-control" id="terminador">
                            <option value="none">Ninguno</option>
                            <option value="cr">CR (\r)</option>
                            <option value="lf">LF (\n)</option>
                            <option value="crlf" selected>CRLF (\r\n)</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>&nbsp;</label>
                          <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="formatoHex">
                            <label class="form-check-label" for="formatoHex">
                              Formato HEX
                            </label>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>&nbsp;</label>
                          <button class="btn btn-success btn-block" id="btnAbrirPuerto">
                            <i class="mdi mdi-lan-connect"></i> Abrir Puerto
                          </button>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>&nbsp;</label>
                          <button class="btn btn-danger btn-block" id="btnCerrarPuerto" disabled>
                            <i class="mdi mdi-lan-disconnect"></i> Cerrar Puerto
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Comandos Predefinidos -->
            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Comandos Predefinidos</h4>
                    <div class="row">
                      <div class="col-md-12">
                        <button class="btn btn-primary comando-btn" data-comando="INT0000001" disabled>
                          <i class="mdi mdi-restart"></i> Reiniciar (INT0000001)
                        </button>
                        <button class="btn btn-info comando-btn" data-comando="0D0A" disabled>
                          <i class="mdi mdi-information"></i> Estado (0D0A)
                        </button>
                        <button class="btn btn-warning comando-btn" data-comando="STATUS" disabled>
                          <i class="mdi mdi-pulse"></i> Status
                        </button>
                        <button class="btn btn-secondary comando-btn" data-comando="RESET" disabled>
                          <i class="mdi mdi-backup-restore"></i> Reset
                        </button>
                        <button class="btn btn-success comando-btn" data-comando="ENABLE" disabled>
                          <i class="mdi mdi-check-circle"></i> Habilitar
                        </button>
                        <button class="btn btn-danger comando-btn" data-comando="DISABLE" disabled>
                          <i class="mdi mdi-close-circle"></i> Deshabilitar
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Comando Personalizado -->
            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Comando Personalizado 
                      <button class="btn btn-sm btn-outline-info" data-bs-toggle="collapse" data-bs-target="#ayudaComandos">
                        <i class="mdi mdi-help-circle"></i> Ayuda
                      </button>
                    </h4>
                    <div class="collapse" id="ayudaComandos">
                      <div class="alert alert-info">
                        <strong>Ejemplos de comandos:</strong><br>
                        <strong>Texto ASCII:</strong> Desmarcar "Formato HEX"<br>
                        - <code>INT0000001</code> - Reiniciar<br>
                        - <code>STATUS</code> - Estado<br><br>
                        <strong>HEX:</strong> Marcar "Formato HEX" (sin espacios o con espacios)<br>
                        - <code>0D0A</code> - Carriage Return + Line Feed<br>
                        - <code>494E54</code> - "INT" en HEX<br>
                        - <code>49 4E 54</code> - "INT" en HEX con espacios<br><br>
                        <strong>Nota:</strong> El terminador CRLF se agrega automáticamente al final
                      </div>
                    </div>
                    <div class="row">
                      <div class="col-md-8">
                        <div class="form-group">
                          <label>Escribir comando personalizado</label>
                          <input type="text" class="form-control" id="comandoCustom" placeholder="Ejemplo: INT0000001">
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>&nbsp;</label>
                          <button class="btn btn-primary btn-block" id="btnEnviarCustom" disabled>
                            <i class="mdi mdi-send"></i> Enviar
                          </button>
                        </div>
                      </div>
                      <div class="col-md-2">
                        <div class="form-group">
                          <label>&nbsp;</label>
                          <button class="btn btn-danger btn-block" id="btnLimpiar">
                            <i class="mdi mdi-broom"></i> Limpiar
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Consola de Salida -->
            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">Consola de Comandos</h4>
                    <div class="console-output" id="console">
                      <div class="console-line">Sistema de control de monedero iniciado...</div>
                      <div class="console-line">Esperando comandos...</div>
                    </div>
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
    <script src="assets/vendors/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>
    <script>
      $(document).ready(function() {
        const consoleEl = $('#console');
        let puertoAbierto = false;
        
        function addToConsole(message, type = 'info') {
          const timestamp = new Date().toLocaleTimeString();
          const line = `<div class="console-line ${type}">[${timestamp}] ${message}</div>`;
          consoleEl.append(line);
          consoleEl.scrollTop(consoleEl[0].scrollHeight);
        }

        function habilitarControles(habilitar) {
          $('.comando-btn').prop('disabled', !habilitar);
          $('#btnEnviarCustom').prop('disabled', !habilitar);
          $('#btnAbrirPuerto').prop('disabled', habilitar);
          $('#btnCerrarPuerto').prop('disabled', !habilitar);
          $('#puerto, #baudrate, #databits, #parity, #terminador, #formatoHex').prop('disabled', habilitar);
        }

        // Abrir puerto
        $('#btnAbrirPuerto').click(function() {
          const puerto = $('#puerto').val();
          const baudrate = $('#baudrate').val();
          const databits = $('#databits').val();
          const parity = $('#parity').val();
          const terminador = $('#terminador').val();
          const formatoHex = $('#formatoHex').is(':checked');

          addToConsole('Abriendo puerto ' + puerto + ' @ ' + baudrate + ' baud...', 'info');
          addToConsole('Configuración: Data=' + databits + ', Parity=' + parity + ', Terminador=' + terminador + ', HEX=' + formatoHex, 'info');

          $.ajax({
            url: 'enviar_comando_monedero.php',
            method: 'POST',
            dataType: 'json',
            data: {
              accion: 'abrir',
              puerto: puerto,
              baudrate: baudrate,
              databits: databits,
              parity: parity,
              terminador: terminador,
              formato_hex: formatoHex ? '1' : '0'
            },
            success: function(response) {
              if (response.success) {
                addToConsole('✓ Puerto abierto exitosamente', 'success');
                $('#statusIndicator').removeClass('status-disconnected').addClass('status-connected');
                puertoAbierto = true;
                habilitarControles(true);
              } else {
                addToConsole('✗ Error al abrir puerto: ' + response.mensaje, 'error');
              }
            },
            error: function(xhr, status, error) {
              addToConsole('✗ Error de conexión: ' + error, 'error');
            }
          });
        });

        // Cerrar puerto
        $('#btnCerrarPuerto').click(function() {
          addToConsole('Cerrando puerto...', 'info');

          $.ajax({
            url: 'enviar_comando_monedero.php',
            method: 'POST',
            dataType: 'json',
            data: { accion: 'cerrar' },
            success: function(response) {
              if (response.success) {
                addToConsole('✓ Puerto cerrado', 'success');
                $('#statusIndicator').removeClass('status-connected').addClass('status-disconnected');
                puertoAbierto = false;
                habilitarControles(false);
              } else {
                addToConsole('✗ Error al cerrar puerto: ' + response.mensaje, 'error');
              }
            },
            error: function(xhr, status, error) {
              addToConsole('✗ Error: ' + error, 'error');
            }
          });
        });

        function enviarComando(comando) {
          if (!puertoAbierto) {
            addToConsole('✗ Error: Primero debes abrir el puerto', 'error');
            return;
          }

          const puerto = $('#puerto').val();
          const baudrate = $('#baudrate').val();
          const databits = $('#databits').val();
          const parity = $('#parity').val();
          const terminador = $('#terminador').val();
          const formatoHex = $('#formatoHex').is(':checked');

          let comandoDisplay = comando;
          if (formatoHex) {
            comandoDisplay += ' (HEX)';
          }
          if (terminador !== 'none') {
            comandoDisplay += ' + ' + terminador.toUpperCase();
          }
          addToConsole('→ Enviando: ' + comandoDisplay, 'sent');

          $.ajax({
            url: 'enviar_comando_monedero.php',
            method: 'POST',
            dataType: 'json',
            data: {
              accion: 'enviar',
              comando: comando,
              puerto: puerto,
              baudrate: baudrate,
              databits: databits,
              parity: parity,
              terminador: terminador,
              formato_hex: formatoHex ? '1' : '0'
            },
            success: function(response) {
              if (response.success) {
                addToConsole('✓ ' + response.mensaje, 'success');
                if (response.respuesta) {
                  addToConsole('← Respuesta: ' + response.respuesta, 'received');
                }
                if (response.debug) {
                  addToConsole('ℹ ' + response.debug, 'info');
                }
                $('#statusIndicator').removeClass('status-disconnected').addClass('status-connected');
              } else {
                addToConsole('✗ Error: ' + response.mensaje, 'error');
                $('#statusIndicator').removeClass('status-connected').addClass('status-disconnected');
              }
            },
            error: function(xhr, status, error) {
              addToConsole('✗ Error de conexión: ' + error, 'error');
              $('#statusIndicator').removeClass('status-connected').addClass('status-disconnected');
            }
          });
        }

        // Botones de comandos predefinidos
        $('.comando-btn').click(function() {
          const comando = $(this).data('comando');
          enviarComando(comando);
        });

        // Enviar comando personalizado
        $('#btnEnviarCustom').click(function() {
          const comando = $('#comandoCustom').val().trim();
          if (comando) {
            enviarComando(comando);
            $('#comandoCustom').val('');
          } else {
            addToConsole('✗ Error: Comando vacío', 'error');
          }
        });

        // Enter en input personalizado
        $('#comandoCustom').keypress(function(e) {
          if (e.which === 13) {
            $('#btnEnviarCustom').click();
          }
        });

        // Limpiar consola
        $('#btnLimpiar').click(function() {
          consoleEl.html('<div class="console-line">Consola limpiada...</div>');
        });
      });
    </script>
  </body>
</html>
