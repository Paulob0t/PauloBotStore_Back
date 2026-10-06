<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_config_dual.php';
require_once 'CorteCaja.class.php';

$corteCaja = new CorteCaja($conn);

// Parámetros de filtrado
$fecha_desde = $_GET['fecha_desde'] ?? date('Y-m-d', strtotime('-30 days'));
$fecha_hasta = $_GET['fecha_hasta'] ?? date('Y-m-d');
$id_usuario_filtro = $_GET['usuario'] ?? '';
$tipo_movimiento = $_GET['tipo'] ?? '';

// Construir consulta
$sql = "SELECT 
    m.*,
    u.nombre as nombre_usuario,
    c.fecha as fecha_corte,
    c.tipo_movimiento as tipo_corte
FROM movimientos_caja m
LEFT JOIN usuarios u ON m.id_usuario = u.id
LEFT JOIN cortes c ON m.id_corte = c.id
WHERE DATE(m.fecha_hora) BETWEEN ? AND ?";

$params = [$fecha_desde, $fecha_hasta];
$types = "ss";

if ($id_usuario_filtro) {
    $sql .= " AND m.id_usuario = ?";
    $params[] = $id_usuario_filtro;
    $types .= "i";
}

if ($tipo_movimiento) {
    $sql .= " AND m.tipo_movimiento = ?";
    $params[] = $tipo_movimiento;
    $types .= "s";
}

$sql .= " ORDER BY m.fecha_hora DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$movimientos = [];
$total_ingresos = 0;
$total_egresos = 0;

while ($row = $result->fetch_assoc()) {
    $movimientos[] = $row;
    if ($row['tipo_movimiento'] == 'ingreso') {
        $total_ingresos += $row['monto'];
    } elseif ($row['tipo_movimiento'] == 'egreso') {
        $total_egresos += $row['monto'];
    }
}

// Obtener lista de usuarios para filtro
$sql_usuarios = "SELECT DISTINCT u.id, u.nombre 
                FROM usuarios u
                INNER JOIN movimientos_caja m ON u.id = m.id_usuario
                ORDER BY u.nombre";
$usuarios = $conn->query($sql_usuarios);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Historial de Movimientos - Vendigbox</title>
    
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    
    <style>
        .filter-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .metric-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .metric-value {
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .badge-tipo {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
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
                                <h3 class="font-weight-bold">
                                    <i class="mdi mdi-history"></i> Historial de Movimientos
                                </h3>
                                <h6 class="font-weight-normal mb-0">
                                    Registro detallado de todos los movimientos de caja
                                </h6>
                            </div>
                            <div class="col-12 col-xl-4">
                                <div class="justify-content-end d-flex">
                                    <a href="cortes_caja.php" class="btn btn-primary">
                                        <i class="mdi mdi-arrow-left"></i> Volver a Cortes
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="filter-card">
                            <form method="GET" id="formFiltros">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Fecha Desde</label>
                                            <input type="date" class="form-control" name="fecha_desde" 
                                                   value="<?php echo $fecha_desde; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Fecha Hasta</label>
                                            <input type="date" class="form-control" name="fecha_hasta" 
                                                   value="<?php echo $fecha_hasta; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Tipo</label>
                                            <select class="form-control" name="tipo">
                                                <option value="">Todos</option>
                                                <option value="ingreso" <?php echo $tipo_movimiento == 'ingreso' ? 'selected' : ''; ?>>
                                                    Ingresos
                                                </option>
                                                <option value="egreso" <?php echo $tipo_movimiento == 'egreso' ? 'selected' : ''; ?>>
                                                    Egresos
                                                </option>
                                                <option value="apertura" <?php echo $tipo_movimiento == 'apertura' ? 'selected' : ''; ?>>
                                                    Apertura
                                                </option>
                                                <option value="cierre" <?php echo $tipo_movimiento == 'cierre' ? 'selected' : ''; ?>>
                                                    Cierre
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>Usuario</label>
                                            <select class="form-control" name="usuario">
                                                <option value="">Todos</option>
                                                <?php while ($user = $usuarios->fetch_assoc()): ?>
                                                <option value="<?php echo $user['id']; ?>"
                                                    <?php echo $id_usuario_filtro == $user['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($user['nombre']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <label>&nbsp;</label>
                                            <button type="submit" class="btn btn-primary btn-block">
                                                <i class="mdi mdi-filter"></i> Filtrar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Resumen -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="metric-box">
                            <p class="text-muted mb-2">Total Movimientos</p>
                            <div class="metric-value text-primary">
                                <?php echo count($movimientos); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <p class="text-muted mb-2">Total Ingresos</p>
                            <div class="metric-value text-success">
                                $<?php echo number_format($total_ingresos, 2); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <p class="text-muted mb-2">Total Egresos</p>
                            <div class="metric-value text-danger">
                                $<?php echo number_format($total_egresos, 2); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="metric-box">
                            <p class="text-muted mb-2">Balance Neto</p>
                            <div class="metric-value <?php echo ($total_ingresos - $total_egresos) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                $<?php echo number_format($total_ingresos - $total_egresos, 2); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Movimientos -->
                <div class="row">
                    <div class="col-md-12 grid-margin stretch-card">
                        <div class="card">
                            <div class="card-body">
                                <h4 class="card-title">
                                    Detalle de Movimientos
                                    <span class="float-right">
                                        <small class="text-muted">
                                            Mostrando <?php echo count($movimientos); ?> registros
                                        </small>
                                    </span>
                                </h4>
                                
                                <div class="table-responsive">
                                    <table id="tablaMovimientos" class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID Corte</th>
                                                <th>Fecha/Hora</th>
                                                <th>Tipo</th>
                                                <th>Concepto</th>
                                                <th>Método Pago</th>
                                                <th>Monto</th>
                                                <th>Usuario</th>
                                                <th>Referencia</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($movimientos as $mov): ?>
                                            <tr>
                                                <td>
                                                    <a href="cortes_caja.php#corte-<?php echo $mov['id_corte']; ?>">
                                                        #<?php echo $mov['id_corte']; ?>
                                                    </a>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_hora'])); ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = '';
                                                    switch ($mov['tipo_movimiento']) {
                                                        case 'apertura': $badge_class = 'badge-primary'; break;
                                                        case 'ingreso': $badge_class = 'badge-success'; break;
                                                        case 'egreso': $badge_class = 'badge-danger'; break;
                                                        case 'cierre': $badge_class = 'badge-dark'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?> badge-tipo">
                                                        <?php echo strtoupper($mov['tipo_movimiento']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($mov['concepto']); ?>
                                                    <?php if ($mov['notas']): ?>
                                                        <br><small class="text-muted">
                                                            <i><?php echo htmlspecialchars($mov['notas']); ?></i>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php echo $mov['metodo_pago'] ? htmlspecialchars($mov['metodo_pago']) : '-'; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $monto_class = $mov['tipo_movimiento'] == 'egreso' ? 'text-danger' : 'text-success';
                                                    $signo = $mov['tipo_movimiento'] == 'egreso' ? '-' : '+';
                                                    ?>
                                                    <strong class="<?php echo $monto_class; ?>">
                                                        <?php echo $signo; ?>$<?php echo number_format($mov['monto'], 2); ?>
                                                    </strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($mov['nombre_usuario'] ?? 'Sistema'); ?></td>
                                                <td>
                                                    <?php if ($mov['referencia']): ?>
                                                        <small><?php echo htmlspecialchars($mov['referencia']); ?></small>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
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

<!-- Scripts -->
<script src="assets/vendors/js/vendor.bundle.base.js"></script>
<script src="assets/js/off-canvas.js"></script>
<script src="assets/js/template.js"></script>
<script src="assets/js/settings.js"></script>
<script src="assets/js/todolist.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>

<script>
$(document).ready(function() {
    $('#tablaMovimientos').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[1, 'desc']],
        pageLength: 25,
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excel',
                text: '<i class="mdi mdi-file-excel"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            },
            {
                extend: 'print',
                text: '<i class="mdi mdi-printer"></i> Imprimir',
                className: 'btn btn-primary btn-sm',
                exportOptions: {
                    columns: [0, 1, 2, 3, 4, 5, 6, 7]
                }
            }
        ]
    });
});
</script>
</body>
</html>
