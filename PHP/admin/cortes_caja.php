<?php
session_start();

// Verificar si el usuario está autenticado (ajustado al sistema existente)
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/db_config_dual.php';

$corteCajaClassFile = __DIR__ . '/CorteCaja.class.php';
if (!file_exists($corteCajaClassFile)) {
    $corteCajaClassFile = __DIR__ . '/cortecaja.class.php';
}
require_once $corteCajaClassFile;

$corteCaja = new CorteCaja($conn);

// Obtener información de la caja actual
$config = $corteCaja->getConfig();
$cajaActiva = $corteCaja->hayCajaActiva();
$corteActual = $cajaActiva ? $corteCaja->getCorteActual() : null;

// Si hay caja activa, calcular totales actuales
$totalesActuales = null;
if ($cajaActiva && $corteActual) {
    $totalesActuales = $corteCaja->calcularTotalesCorte($corteActual['id']);
    $movimientosActuales = $corteCaja->getMovimientosCorte($corteActual['id']);
}

// Obtener historial de cortes
$historialCortes = $corteCaja->getHistorialCortes(null, null, 20);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cortes de Caja - Vendigbox</title>
    
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="styles_css/tabla_productos.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    
    <style>
        .status-badge {
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            border: 1px solid transparent;
        }

        .status-activo {
            background: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
            border-color: rgba(46, 204, 113, 0.35);
        }

        .status-cerrado {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border-color: rgba(231, 76, 60, 0.35);
        }

        .card-header-custom {
            background: #0a0a0a;
            color: var(--vb-yellow);
            padding: 20px;
            border-bottom: 2px solid rgba(242, 220, 0, 0.35);
        }

        .metric-card {
            border-color: rgba(242, 220, 0, 0.25);
        }

        .big-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--vb-yellow);
        }

        .diferencia-positiva {
            color: #2ecc71;
            font-weight: 700;
        }

        .diferencia-negativa {
            color: #e74c3c;
            font-weight: 700;
        }

        .btn-action {
            min-width: 150px;
        }

        #tablaHistorialCortes.table > :not(caption) > * > * {
            background-color: #1a1a1a !important;
            color: #f8f8f8 !important;
            border-color: rgba(242, 220, 0, 0.1) !important;
        }

        #tablaHistorialCortes.table-striped > tbody > tr:nth-of-type(odd) > * {
            background-color: #171717 !important;
        }

        #tablaHistorialCortes.table-striped > tbody > tr:nth-of-type(even) > * {
            background-color: #1d1d1d !important;
        }

        .vb-modal-lg {
            max-width: 95vw;
            margin: 1.5rem auto;
        }

        .vb-modal-xl {
            max-width: 90vw;
            margin: 1.5rem auto;
        }

        .vb-modal-header {
            background: var(--vb-yellow) !important;
            border: none;
        }

        .vb-modal-header .modal-title,
        .vb-modal-header .modal-title i,
        .vb-modal-header .close {
            color: #0a0a0a !important;
            text-shadow: none;
        }

        .vb-modal-body {
            padding: 30px;
            max-height: 85vh;
            overflow-y: auto;
        }

        #modalDetalleMovimiento .table-sm td,
        #modalDetalleMovimiento .table-sm th {
            padding: 0.5rem;
            vertical-align: middle;
        }

        #modalDetalleMovimiento .table img {
            border-radius: 4px;
            object-fit: cover;
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
                
                <!-- Encabezado -->
                <div class="row">
                    <div class="col-md-12 grid-margin">
                        <div class="row">
                            <div class="col-12 col-xl-8 mb-4 mb-xl-0">
                                <h3 class="font-weight-bold">Sistema de Cortes de Caja</h3>
                                <h6 class="font-weight-normal mb-0">
                                    Estado: 
                                    <?php if ($cajaActiva): ?>
                                        <span class="status-badge status-activo">
                                            <i class="mdi mdi-checkbox-marked-circle"></i> Caja Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-cerrado">
                                            <i class="mdi mdi-close-circle"></i> Caja Cerrada
                                        </span>
                                    <?php endif; ?>
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alertas y Mensajes -->
                <div class="row">
                    <div class="col-12">
                        <div id="alertContainer"></div>
                    </div>
                </div>

                <!-- Panel de Control de Caja Actual -->
                <div class="row">
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-header-custom">
                                <h4 class="mb-0">
                                    <i class="mdi mdi-cash-register"></i> Control de Caja
                                </h4>
                            </div>
                            <div class="card-body">
                                <?php if ($cajaActiva && $corteActual): ?>
                                    <!-- Caja Activa -->
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="card metric-card">
                                                <div class="card-body text-center">
                                                    <p class="text-muted mb-2">Monto Inicial</p>
                                                    <h3 class="text-primary">
                                                        $<?php echo number_format($corteActual['monto_inicial'], 2); ?>
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card metric-card">
                                                <div class="card-body text-center">
                                                    <p class="text-muted mb-2">Total Ingresos</p>
                                                    <h3 class="text-success">
                                                        +$<?php echo number_format($totalesActuales['total_ingresos'] ?? 0, 2); ?>
                                                    </h3>
                                                    <small class="text-muted">
                                                        <?php echo $totalesActuales['num_ingresos'] ?? 0; ?> movimientos
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card metric-card">
                                                <div class="card-body text-center">
                                                    <p class="text-muted mb-2">Total Egresos</p>
                                                    <h3 class="text-danger">
                                                        -$<?php echo number_format($totalesActuales['total_egresos'] ?? 0, 2); ?>
                                                    </h3>
                                                    <small class="text-muted">
                                                        <?php echo $totalesActuales['num_egresos'] ?? 0; ?> movimientos
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="card metric-card">
                                                <div class="card-body text-center">
                                                    <p class="text-muted mb-2">Saldo Actual</p>
                                                    <h3 class="text-info">
                                                        $<?php 
                                                            $saldo_actual = $corteActual['monto_inicial'] + 
                                                                          ($totalesActuales['total_ingresos'] ?? 0) - 
                                                                          ($totalesActuales['total_egresos'] ?? 0);
                                                            echo number_format($saldo_actual, 2);
                                                        ?>
                                                    </h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <div class="alert alert-info">
                                                <i class="mdi mdi-information"></i>
                                                <strong>Caja iniciada:</strong> 
                                                <?php echo date('d/m/Y', strtotime($corteActual['fecha'])); ?> 
                                                a las <?php echo date('H:i', strtotime($corteActual['hora'])); ?>
                                                <?php if ($corteActual['notas']): ?>
                                                    <br><small><?php echo htmlspecialchars($corteActual['notas']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-3">
                                        <div class="col-md-12 text-center">
                                            <button type="button" class="btn btn-danger btn-lg btn-action" 
                                                    onclick="mostrarModalCerrarCaja()">
                                                <i class="mdi mdi-close-circle"></i> Cerrar Caja
                                            </button>
                                            <button type="button" class="btn btn-info btn-lg btn-action" 
                                                    onclick="verMovimientosActuales()">
                                                <i class="mdi mdi-eye"></i> Ver Movimientos
                                            </button>
                                        </div>
                                    </div>
                                    
                                <?php else: ?>
                                    <!-- Caja Cerrada -->
                                    <div class="text-center py-5">
                                        <i class="mdi mdi-cash-register text-muted" style="font-size: 80px;"></i>
                                        <h4 class="mt-3">No hay caja activa</h4>
                                        <p class="text-muted">Inicia una nueva jornada para comenzar a registrar movimientos</p>
                                        <button type="button" class="btn btn-success btn-lg btn-action mt-3" 
                                                onclick="mostrarModalAbrirCaja()">
                                            <i class="mdi mdi-cash-plus"></i> Iniciar Caja
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuración de Corte Automático -->
                <div class="row">
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">
                                    <i class="mdi mdi-clock-outline"></i> Configuración de Corte Automático
                                </h4>
                                <form id="formConfigAutomatico">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>
                                                    <input type="checkbox" 
                                                           id="corte_automatico_habilitado"
                                                           <?php echo $config['corte_automatico_habilitado'] ? 'checked' : ''; ?>>
                                                    Habilitar corte automático
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Hora del corte automático</label>
                                                <input type="time" class="form-control" 
                                                       id="hora_corte_automatico"
                                                       value="<?php echo $config['hora_corte_automatico']; ?>">
                                                <small class="form-text text-muted">Solo si usas servicios externos</small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Horas para cierre automático</label>
                                                <input type="number" class="form-control" 
                                                       id="horas_para_cierre"
                                                       min="1"
                                                       max="168"
                                                       value="<?php echo $config['horas_para_cierre'] ?? 24; ?>">
                                                <small class="form-text text-muted">Horas después de abrir (ej. 24 = 1 día)</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label>Monto inicial por defecto</label>
                                                <input type="number" class="form-control" 
                                                       id="monto_inicial_default"
                                                       step="0.01"
                                                       value="<?php echo $config['monto_inicial_default']; ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="mdi mdi-content-save"></i> Guardar Configuración
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historial de Cortes -->
                <div class="row">
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">
                                    <i class="mdi mdi-history"></i> Historial de Cortes
                                </h4>
                                <div class="table-responsive">
                                    <table id="tablaHistorialCortes" class="table table-striped table-borderless">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Tipo</th>
                                                <th>Monto Inicial</th>
                                                <th>Ingresos</th>
                                                <th>Egresos</th>
                                                <th>Monto Final</th>
                                                <th>Diferencia</th>
                                                <th>Usuario</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($historialCortes as $corte): ?>
                                            <tr>
                                                <td><?php echo $corte['id']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($corte['fecha'])); ?></td>
                                                <td><?php echo date('H:i', strtotime($corte['hora'])); ?></td>
                                                <td>
                                                    <?php if ($corte['tipo_movimiento'] == 'inicio'): ?>
                                                        <span class="badge badge-warning">
                                                            <i class="mdi mdi-clock-outline"></i> Abierto
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">
                                                            <i class="mdi mdi-check-circle"></i> Cerrado
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>$<?php echo number_format($corte['monto_inicial'], 2); ?></td>
                                                <td class="text-success">
                                                    +$<?php echo number_format($corte['total_ingresos'], 2); ?>
                                                </td>
                                                <td class="text-danger">
                                                    -$<?php echo number_format($corte['total_egresos'], 2); ?>
                                                </td>
                                                <td>$<?php echo number_format($corte['monto_final'], 2); ?></td>
                                                <td class="<?php echo $corte['diferencia'] >= 0 ? 'diferencia-positiva' : 'diferencia-negativa'; ?>">
                                                    $<?php echo number_format($corte['diferencia'], 2); ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($corte['nombre_usuario'] ?? 'Sistema'); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" 
                                                            onclick="verDetalleCorte(<?php echo $corte['id']; ?>)">
                                                        <i class="mdi mdi-eye"></i> Ver
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" 
                                                            onclick="imprimirCorte(<?php echo $corte['id']; ?>)">
                                                        <i class="mdi mdi-printer"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
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

<!-- Modal Abrir Caja -->
<div class="modal fade" id="modalAbrirCaja" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header vb-modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-cash-plus"></i> Iniciar Caja
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formAbrirCaja">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Monto Inicial *</label>
                        <input type="number" class="form-control" id="monto_inicial" 
                               step="0.01" required value="<?php echo $config['monto_inicial_default']; ?>">
                        <small class="form-text text-muted">
                            Ingrese el efectivo con el que inicia la caja
                        </small>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" id="notas_apertura" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="mdi mdi-check"></i> Iniciar Caja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cerrar Caja -->
<div class="modal fade" id="modalCerrarCaja" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header vb-modal-header">
                <h5 class="modal-title">
                    <i class="mdi mdi-close-circle"></i> Cerrar Caja
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="formCerrarCaja">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Saldo Esperado:</strong> 
                        <span class="float-right big-number" id="saldo_esperado">
                            $<?php echo isset($saldo_actual) ? number_format($saldo_actual, 2) : '0.00'; ?>
                        </span>
                    </div>
                    <div class="form-group">
                        <label>Monto Final Declarado *</label>
                        <input type="number" class="form-control" id="monto_final" 
                               step="0.01" required>
                        <small class="form-text text-muted">
                            Ingrese el efectivo real que hay en la caja
                        </small>
                    </div>
                    <div id="alertDiferencia" class="alert" style="display: none;"></div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" id="notas_cierre" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="mdi mdi-check"></i> Cerrar Caja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Ver Movimientos - ULTRA GRANDE -->
<div class="modal fade" id="modalMovimientos" tabindex="-1">
    <div class="modal-dialog vb-modal-lg">
        <div class="modal-content">
            <div class="modal-header vb-modal-header">
                <h5 class="modal-title d-flex align-items-center" style="gap: 12px;">
                    <i class="mdi mdi-format-list-bulleted"></i>
                    <span>Movimientos del Corte</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body vb-modal-body" id="contenidoMovimientos">
                <!-- Se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Modal Detalle de Movimiento Individual - ULTRA PRO -->
<div class="modal fade" id="modalDetalleMovimiento" tabindex="-1">
    <div class="modal-dialog vb-modal-xl">
        <div class="modal-content">
            <div class="modal-header vb-modal-header">
                <h5 class="modal-title d-flex align-items-center" style="gap: 12px;">
                    <i class="mdi mdi-information"></i>
                    <span>Detalle del Movimiento</span>
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body vb-modal-body" id="contenidoDetalleMovimiento">
                <!-- Se cargará dinámicamente -->
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/template.js"></script>
<script src="assets/js/settings.js"></script>
<script src="assets/js/todolist.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Fix para cerrar modales con la X
    $('.modal .close').on('click', function() {
        $(this).closest('.modal').modal('hide');
    });
    
    // Inicializar DataTable
    $('#tablaHistorialCortes').DataTable({
        paging: false,
        lengthChange: false,
        info: false,
        searching: true,
        responsive: true,
        dom: '<"row"<"col-sm-12"f>>rt',
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[0, 'desc']],
        columnDefs: [
            { targets: [10], orderable: false }
        ]
    });
    
    // Calcular diferencia al escribir monto final
    $('#monto_final').on('input', function() {
        const montoFinal = parseFloat($(this).val()) || 0;
        const saldoEsperado = parseFloat($('#saldo_esperado').text().replace('$', '').replace(',', '')) || 0;
        const diferencia = montoFinal - saldoEsperado;
        
        const alertDiv = $('#alertDiferencia');
        if (diferencia !== 0) {
            alertDiv.show();
            if (diferencia > 0) {
                alertDiv.removeClass('alert-danger').addClass('alert-success');
                alertDiv.html('<strong>Sobrante:</strong> $' + Math.abs(diferencia).toFixed(2));
            } else {
                alertDiv.removeClass('alert-success').addClass('alert-danger');
                alertDiv.html('<strong>Faltante:</strong> $' + Math.abs(diferencia).toFixed(2));
            }
        } else {
            alertDiv.hide();
        }
    });
});

function mostrarModalAbrirCaja() {
    $('#modalAbrirCaja').modal('show');
}

function mostrarModalCerrarCaja() {
    $('#modalCerrarCaja').modal('show');
}

function verMovimientosActuales() {
    verDetalleCorte(<?php echo $corteActual['id'] ?? 0; ?>);
}

// Formulario abrir caja
$('#formAbrirCaja').on('submit', function(e) {
    e.preventDefault();
    
    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Procesando...');
    
    const data = {
        action: 'iniciar_caja',
        monto_inicial: $('#monto_inicial').val(),
        notas: $('#notas_apertura').val()
    };
    
    $.ajax({
        url: 'cortes_caja_ajax.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success) {
                mostrarAlerta('success', response.mensaje);
                $('#modalAbrirCaja').modal('hide');
                setTimeout(() => location.reload(), 1000);
            } else {
                mostrarAlerta('danger', response.mensaje);
                btn.prop('disabled', false).html('<i class="mdi mdi-check"></i> Iniciar Caja');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText);
            let mensaje = 'Error al procesar la solicitud';
            if (xhr.responseText) {
                try {
                    const resp = JSON.parse(xhr.responseText);
                    mensaje = resp.mensaje || mensaje;
                } catch(e) {
                    mensaje = 'Error del servidor: ' + xhr.status;
                }
            }
            mostrarAlerta('danger', mensaje);
            btn.prop('disabled', false).html('<i class="mdi mdi-check"></i> Iniciar Caja');
        }
    });
});

// Formulario cerrar caja
$('#formCerrarCaja').on('submit', function(e) {
    e.preventDefault();
    
    const btn = $(this).find('button[type="submit"]');
    btn.prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Cerrando...');
    
    const data = {
        action: 'cerrar_caja',
        monto_final: $('#monto_final').val(),
        notas: $('#notas_cierre').val()
    };
    
    $.ajax({
        url: 'cortes_caja_ajax.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        timeout: 10000,
        success: function(response) {
            if (response.success) {
                $('#modalCerrarCaja').modal('hide');
                mostrarAlerta('success', response.mensaje);
                setTimeout(() => location.reload(), 1000);
            } else {
                mostrarAlerta('danger', response.mensaje);
                btn.prop('disabled', false).html('<i class="mdi mdi-lock"></i> Cerrar Caja');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error AJAX:', xhr.responseText);
            let mensaje = 'Error al cerrar la caja';
            if (xhr.responseText) {
                try {
                    const resp = JSON.parse(xhr.responseText);
                    mensaje = resp.mensaje || mensaje;
                } catch(e) {
                    mensaje = 'Error del servidor: ' + xhr.status;
                }
            }
            mostrarAlerta('danger', mensaje);
            btn.prop('disabled', false).html('<i class="mdi mdi-lock"></i> Cerrar Caja');
        }
    });
});

// Formulario configuración
$('#formConfigAutomatico').on('submit', function(e) {
    e.preventDefault();
    
    const data = {
        action: 'actualizar_config',
        corte_automatico_habilitado: $('#corte_automatico_habilitado').is(':checked') ? 1 : 0,
        hora_corte_automatico: $('#hora_corte_automatico').val(),
        monto_inicial_default: $('#monto_inicial_default').val(),
        horas_para_cierre: $('#horas_para_cierre').val()
    };
    
    $.ajax({
        url: 'cortes_caja_ajax.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                mostrarAlerta('success', 'Configuración actualizada correctamente');
            } else {
                mostrarAlerta('danger', response.mensaje);
            }
        },
        error: function() {
            mostrarAlerta('danger', 'Error al procesar la solicitud');
        }
    });
});

function verDetalleCorte(idCorte) {
    $.ajax({
        url: 'cortes_caja_ajax.php',
        method: 'POST',
        data: { action: 'ver_detalle_corte', id_corte: idCorte },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#contenidoMovimientos').html(response.html);
                $('#modalMovimientos').modal('show');
            } else {
                mostrarAlerta('danger', 'Error al cargar los movimientos');
            }
        }
    });
}

function verDetalleMovimiento(idMovimiento) {
    $.ajax({
        url: 'cortes_caja_ajax.php',
        method: 'POST',
        data: { action: 'ver_detalle_movimiento', id_movimiento: idMovimiento },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                $('#contenidoDetalleMovimiento').html(response.html);
                $('#modalDetalleMovimiento').modal('show');
            } else {
                mostrarAlerta('danger', response.mensaje || 'Error al cargar el detalle');
            }
        },
        error: function(xhr) {
            console.error('Error:', xhr.responseText);
            mostrarAlerta('danger', 'Error al cargar el detalle del movimiento');
        }
    });
}

function imprimirCorte(idCorte) {
    window.open('imprimir_corte.php?id=' + idCorte, '_blank');
}

function mostrarAlerta(tipo, mensaje) {
    const alerta = `
        <div class="alert alert-${tipo} alert-dismissible fade show" role="alert">
            ${mensaje}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    $('#alertContainer').html(alerta);
    
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}
</script>
</body>
</html>
