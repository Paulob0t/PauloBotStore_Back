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
    <title>Configurar Empresa</title>
    
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- inject:css -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="styles_css/tabla_productos.css">
    
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    
    <style>
        .card-description {
            color: rgba(248, 248, 248, 0.75) !important;
        }

        .ticket-preview {
            background: #0f0f0f;
            border: 2px dashed rgba(242, 220, 0, 0.35);
            border-radius: 12px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            max-width: 400px;
            color: #f8f8f8;
            box-shadow: inset 0 0 0 1px rgba(242, 220, 0, 0.08);
        }

        .ticket-preview .header {
            text-align: center;
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--vb-yellow);
        }

        .ticket-preview .field {
            text-align: center;
            font-size: 12px;
            margin: 2px 0;
            color: #f8f8f8;
        }

        .ticket-preview .separator {
            border-top: 1px dashed rgba(242, 220, 0, 0.45);
            margin: 15px 0;
        }

        .ticket-items {
            text-align: left;
            font-size: 11px;
            color: #f8f8f8;
        }
    </style>
</head>
<body>
    <div class="container-scroller">
        <?php include "navbar.php"; ?>
        <div class="container-fluid page-body-wrapper">
            <?php include "menu.php"; ?>
            <div class="main-panel">
                <div class="content-wrapper">
                    <div class="row">
                        <div class="col-md-12 grid-margin stretch-card">
                            <div class="card">
                                <div class="card-body">
                                    <h4 class="card-title">
                                        <i class="fa-solid fa-building"></i> Configuración de Empresa
                                    </h4>
                                    <p class="card-description">
                                        Esta información aparecerá en el encabezado de todos los tickets de venta
                                    </p>
                                    
                                    <form id="formEmpresa" class="forms-sample">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label for="nombre_empresa">Nombre de la Empresa <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" id="nombre_empresa" name="nombre_empresa" 
                                                           placeholder="VENDING BOX" required>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="direccion">Dirección</label>
                                                    <input type="text" class="form-control" id="direccion" name="direccion" 
                                                           placeholder="Calle Principal #123">
                                                </div>
                                                
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="ciudad">Ciudad</label>
                                                            <input type="text" class="form-control" id="ciudad" name="ciudad" 
                                                                   placeholder="Ciudad de México">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label for="estado">Estado</label>
                                                            <input type="text" class="form-control" id="estado" name="estado" 
                                                                   placeholder="CDMX">
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="telefono">Teléfono</label>
                                                    <input type="text" class="form-control" id="telefono" name="telefono" 
                                                           placeholder="55-1234-5678">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="rfc">RFC</label>
                                                    <input type="text" class="form-control text-uppercase" id="rfc" name="rfc" 
                                                           placeholder="XAXX010101000" maxlength="13">
                                                </div>
                                                
                                                <div class="form-group">
                                                    <label for="website">Sitio Web</label>
                                                    <input type="text" class="form-control" id="website" name="website" 
                                                           placeholder="www.vendigbox.com">
                                                </div>
                                                
                                                <button type="submit" class="btn btn-primary me-2">
                                                    <i class="fa fa-save"></i> Guardar Configuración
                                                </button>
                                                <button type="button" class="btn btn-outline-primary" onclick="cargarDatos()">
                                                    <i class="fa fa-refresh"></i> Recargar
                                                </button>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Vista Previa del Ticket</label>
                                                    <div class="ticket-preview" id="ticketPreview">
                                                        <div class="header" id="preview_nombre">VENDING BOX</div>
                                                        <div class="field" id="preview_direccion"></div>
                                                        <div class="field" id="preview_ciudad_estado"></div>
                                                        <div class="field" id="preview_telefono"></div>
                                                        <div class="field" id="preview_rfc"></div>
                                                        <div class="separator"></div>
                                                        <div class="field">TICKET DE VENTA</div>
                                                        <div class="field">Fecha: 26/11/2025 10:30</div>
                                                        <div class="field">Folio: #12345</div>
                                                        <div class="separator"></div>
                                                        <div class="ticket-items">
                                                            1x Coca Cola 500ml<br>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$15.00<br>
                                                            1x Sabritas Originales<br>
                                                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;$12.00
                                                        </div>
                                                        <div class="separator"></div>
                                                        <div class="field" style="font-weight: bold;">TOTAL: $27.00</div>
                                                        <div class="separator"></div>
                                                        <div class="field" id="preview_website">www.vendigbox.com</div>
                                                        <div class="field">¡GRACIAS POR SU COMPRA!</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
                <?php include 'footer.php'; ?>
            </div>
        </div>
    </div>
    
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- inject:js -->
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/template.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/todolist.js"></script>
    
    <script>
        const EMPRESA_GET_URL = 'get_empresa_config.php';
        const EMPRESA_SAVE_URL = 'save_empresa_config.php';

        // Cargar datos al iniciar
        document.addEventListener('DOMContentLoaded', cargarDatos);
        
        // Actualizar preview en tiempo real
        ['nombre_empresa', 'direccion', 'ciudad', 'estado', 'telefono', 'rfc', 'website'].forEach(field => {
            document.getElementById(field).addEventListener('input', actualizarPreview);
        });
        
        // Convertir RFC a mayúsculas automáticamente
        document.getElementById('rfc').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        function actualizarPreview() {
            const nombre = document.getElementById('nombre_empresa').value || 'VENDING BOX';
            const direccion = document.getElementById('direccion').value;
            const ciudad = document.getElementById('ciudad').value;
            const estado = document.getElementById('estado').value;
            const telefono = document.getElementById('telefono').value;
            const rfc = document.getElementById('rfc').value;
            const website = document.getElementById('website').value || 'www.vendigbox.com';
            
            document.getElementById('preview_nombre').textContent = nombre;
            document.getElementById('preview_direccion').textContent = direccion;
            
            let ciudadEstado = '';
            if (ciudad && estado) {
                ciudadEstado = `${ciudad}, ${estado}`;
            } else if (ciudad) {
                ciudadEstado = ciudad;
            } else if (estado) {
                ciudadEstado = estado;
            }
            document.getElementById('preview_ciudad_estado').textContent = ciudadEstado;
            
            document.getElementById('preview_telefono').textContent = telefono ? `Tel: ${telefono}` : '';
            document.getElementById('preview_rfc').textContent = rfc ? `RFC: ${rfc}` : '';
            document.getElementById('preview_website').textContent = website;
        }
        
        async function cargarDatos() {
            try {
                const response = await fetch(EMPRESA_GET_URL, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error(`No se pudo cargar la configuración (HTTP ${response.status})`);
                }

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const preview = (await response.text()).slice(0, 120);
                    throw new Error('Respuesta inválida del servidor: ' + preview);
                }

                const result = await response.json();
                
                if (result.success && result.data) {
                    document.getElementById('nombre_empresa').value = result.data.nombre_empresa || '';
                    document.getElementById('direccion').value = result.data.direccion || '';
                    document.getElementById('ciudad').value = result.data.ciudad || '';
                    document.getElementById('estado').value = result.data.estado || '';
                    document.getElementById('telefono').value = result.data.telefono || '';
                    document.getElementById('rfc').value = result.data.rfc || '';
                    document.getElementById('website').value = result.data.website || '';
                    
                    actualizarPreview();
                }
            } catch (error) {
                console.error('Error al cargar datos:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error al cargar',
                    text: error.message || 'No se pudo cargar la configuración de empresa'
                });
            }
        }
        
        document.getElementById('formEmpresa').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const datos = {
                nombre_empresa: document.getElementById('nombre_empresa').value,
                direccion: document.getElementById('direccion').value,
                ciudad: document.getElementById('ciudad').value,
                estado: document.getElementById('estado').value,
                telefono: document.getElementById('telefono').value,
                rfc: document.getElementById('rfc').value,
                website: document.getElementById('website').value
            };
            
            try {
                const response = await fetch(EMPRESA_SAVE_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(datos)
                });

                if (!response.ok) {
                    throw new Error(`No se pudo guardar la configuración (HTTP ${response.status})`);
                }

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    const preview = (await response.text()).slice(0, 120);
                    throw new Error('Respuesta inválida del servidor: ' + preview);
                }
                
                const result = await response.json();
                
                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Guardado!',
                        text: 'La configuración se guardó correctamente',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    throw new Error(result.error || 'Error al guardar');
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error.message || 'No se pudo guardar la configuración'
                });
            }
        });
    </script>
</body>
</html>
