<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Dashboard.php';
require_once __DIR__ . '/../app/Controllers/DashboardController.php';

use App\Controllers\DashboardController;

$controller = new DashboardController();
$data = $controller->getDashboardData();

$nombreUsuario = $data['nombreUsuario'];
$sales = $data['sales'];
$inventory = $data['inventory'];
$chart = $data['chart'];
$topProducts = $data['topProducts'];
$recentSales = $data['recentSales'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard Administrador | PauloBot Store</title>

    <!-- Google Fonts & FontAwesome 6 (HTTPS CDN) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Vendor Styles (HTTPS CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Estilos Separados del Dashboard y Layout -->
    <link rel="stylesheet" href="./assets/css/layout.css">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
</head>
<body>

    <div class="container-scroller">
        <!-- Navbar Superior -->
        <?php include 'navbar.php'; ?>

        <div class="container-fluid page-body-wrapper">
            <!-- Menú Lateral -->
            <?php include 'menu.php'; ?>

            <!-- Panel Principal -->
            <div class="main-panel">
                <div class="content-wrapper p-4">

                    <!-- Header Bienvenida -->
                    <div class="dashboard-header d-flex align-items-center justify-content-between">
                        <div>
                            <h1 class="welcome-title">Hola, <span><?php echo htmlspecialchars($nombreUsuario); ?></span> 👋</h1>
                            <p class="dashboard-subtitle mb-0">Resumen operativo y rendimiento del sistema de ventas en tiempo real.</p>
                        </div>
                        <div class="text-end">
                            <span class="badge badge-success-custom px-3 py-2">
                                <i class="fas fa-circle me-1"></i> Sistema En Línea
                            </span>
                        </div>
                    </div>

                    <!-- Métricas de Tarjetas (Grid) -->
                    <div class="row g-4 mb-4">
                        <!-- Ventas Hoy -->
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-icon-wrapper icon-blue">
                                    <i class="fas fa-cash-register"></i>
                                </div>
                                <div class="metric-value">$<?php echo number_format($sales['ventasHoyMonto'], 2); ?></div>
                                <div class="metric-label">Ventas de Hoy (<?php echo $sales['ventasHoyCnt']; ?> transacciones)</div>
                            </div>
                        </div>

                        <!-- Ventas del Mes -->
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-icon-wrapper icon-green">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                                <div class="metric-value">$<?php echo number_format($sales['ventasMesMonto'], 2); ?></div>
                                <div class="metric-label">Ventas del Mes (<?php echo $sales['ventasMesCnt']; ?> acumuladas)</div>
                            </div>
                        </div>

                        <!-- Total Productos -->
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-icon-wrapper icon-yellow">
                                    <i class="fas fa-boxes-stacked"></i>
                                </div>
                                <div class="metric-value"><?php echo $inventory['totalProductos']; ?></div>
                                <div class="metric-label">Productos Registrados (<?php echo $inventory['stockTotal']; ?> en stock)</div>
                            </div>
                        </div>

                        <!-- Alertas Stock Bajo -->
                        <div class="col-md-3">
                            <div class="metric-card">
                                <div class="metric-icon-wrapper icon-red">
                                    <i class="fas fa-triangle-exclamation"></i>
                                </div>
                                <div class="metric-value"><?php echo $inventory['stockBajo']; ?></div>
                                <div class="metric-label">Productos con Stock Bajo (≤ 5 unidades)</div>
                            </div>
                        </div>
                    </div>

                    <!-- Gráfica de Tendencia (7 días) -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="chart-container-card">
                                <div class="card-title-custom">
                                    <i class="fas fa-chart-area text-primary"></i> Tendencia de Ventas (Últimos 7 días)
                                </div>
                                <div style="height: 300px;">
                                    <canvas id="salesTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tablas de Información (Top Productos & Últimas Ventas) -->
                    <div class="row g-4">
                        <!-- Top Productos -->
                        <div class="col-lg-6">
                            <div class="chart-container-card">
                                <div class="card-title-custom">
                                    <i class="fas fa-trophy text-warning"></i> Top 5 Productos Más Vendidos
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-custom">
                                        <thead>
                                            <tr>
                                                <th>Producto</th>
                                                <th class="text-center">Unidades</th>
                                                <th class="text-end">Ingresos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($topProducts)): ?>
                                                <?php foreach ($topProducts as $prod): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($prod['nombre_producto']); ?></strong></td>
                                                        <td class="text-center"><span class="badge badge-warning-custom"><?php echo $prod['unidades']; ?> uds</span></td>
                                                        <td class="text-end font-weight-bold">$<?php echo number_format($prod['ingresos'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No se registran ventas recientes.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Últimas Ventas -->
                        <div class="col-lg-6">
                            <div class="chart-container-card">
                                <div class="card-title-custom">
                                    <i class="fas fa-clock text-info"></i> Últimas Transacciones Registradas
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-custom">
                                        <thead>
                                            <tr>
                                                <th>Folio</th>
                                                <th>Fecha / Hora</th>
                                                <th class="text-end">Monto Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recentSales)): ?>
                                                <?php foreach ($recentSales as $venta): ?>
                                                    <tr>
                                                        <td><code>#<?php echo htmlspecialchars($venta['folio']); ?></code></td>
                                                        <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                                                        <td class="text-end font-weight-bold text-success">$<?php echo number_format($venta['total'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted">No hay transacciones registradas.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- Pasar datos PHP a JavaScript de forma limpia -->
    <script>
        window.dashboardChartData = <?php echo json_encode($chart); ?>;
    </script>

    <!-- Scripts JS Externos (HTTPS CDN) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- JS Separado del Layout y Dashboard -->
    <script src="./assets/js/layout.js" defer></script>
    <script src="./assets/js/dashboard.js" defer></script>
</body>
</html>
