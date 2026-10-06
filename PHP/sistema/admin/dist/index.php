<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
    header('Location: login.php');
    exit();
}

require_once 'db_config_dual.php';

$nombreUsuario = $_SESSION['nombre_usuario'] ?? 'Administrador';
$hoy = date('Y-m-d');
$mesActual = date('Y-m');
$fechaLabel = date('d/m/Y');

// --- Métricas de ventas ---
$ventasHoyCnt = 0;
$ventasHoyMonto = 0.0;
$ventasMesCnt = 0;
$ventasMesMonto = 0.0;
$promedioVenta = 0.0;

$sqlHoy = "SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS monto
           FROM ventas_comanda
           WHERE DATE(fecha_venta) = ? AND estatus = 1";
if ($stmt = $conn->prepare($sqlHoy)) {
    $stmt->bind_param('s', $hoy);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $ventasHoyCnt = (int)($row['cnt'] ?? 0);
    $ventasHoyMonto = (float)($row['monto'] ?? 0);
    $stmt->close();
}

$sqlMes = "SELECT COUNT(*) AS cnt, COALESCE(SUM(total), 0) AS monto
           FROM ventas_comanda
           WHERE DATE_FORMAT(fecha_venta, '%Y-%m') = ? AND estatus = 1";
if ($stmt = $conn->prepare($sqlMes)) {
    $stmt->bind_param('s', $mesActual);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $ventasMesCnt = (int)($row['cnt'] ?? 0);
    $ventasMesMonto = (float)($row['monto'] ?? 0);
    $stmt->close();
}

if ($ventasMesCnt > 0) {
    $promedioVenta = $ventasMesMonto / $ventasMesCnt;
}

// --- Inventario ---
$totalProductos = 0;
$stockTotal = 0;
$stockBajo = 0;
$productosInactivos = 0;

$sqlProd = "SELECT
    COUNT(*) AS total,
    COALESCE(SUM(stock), 0) AS stock_total,
    SUM(CASE WHEN stock <= 5 AND activo = 1 THEN 1 ELSE 0 END) AS stock_bajo,
    SUM(CASE WHEN activo = 0 THEN 1 ELSE 0 END) AS inactivos
    FROM productos";
if ($res = $conn->query($sqlProd)) {
    $p = $res->fetch_assoc();
    $totalProductos = (int)($p['total'] ?? 0);
    $stockTotal = (int)($p['stock_total'] ?? 0);
    $stockBajo = (int)($p['stock_bajo'] ?? 0);
    $productosInactivos = (int)($p['inactivos'] ?? 0);
}

// --- Ventas últimos 7 días (gráfica) ---
$chartLabels = [];
$chartVentas = [];
$chartMontos = [];
for ($i = 6; $i >= 0; $i--) {
    $dia = date('Y-m-d', strtotime("-$i days"));
    $chartLabels[] = date('d/m', strtotime($dia));
    $chartVentas[$dia] = 0;
    $chartMontos[$dia] = 0.0;
}

$sql7d = "SELECT DATE(fecha_venta) AS dia, COUNT(*) AS ventas, COALESCE(SUM(total), 0) AS monto
          FROM ventas_comanda
          WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND estatus = 1
          GROUP BY DATE(fecha_venta)";
if ($res = $conn->query($sql7d)) {
    while ($r = $res->fetch_assoc()) {
        $d = $r['dia'];
        if (isset($chartVentas[$d])) {
            $chartVentas[$d] = (int)$r['ventas'];
            $chartMontos[$d] = (float)$r['monto'];
        }
    }
}

$chartVentasData = array_values($chartVentas);
$chartMontosData = array_values($chartMontos);

// --- Top productos (30 días) ---
$topProductos = [];
$sqlTop = "SELECT p.nombre_producto, SUM(vd.cantidad) AS unidades, COALESCE(SUM(vd.total), 0) AS ingresos
           FROM ventas_detalle vd
           INNER JOIN productos p ON p.id_producto = vd.id_producto
           INNER JOIN ventas_comanda vc ON vc.id_comanda = vd.id_comandC
           WHERE vc.fecha_venta >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND vc.estatus = 1
           GROUP BY p.id_producto, p.nombre_producto
           ORDER BY unidades DESC
           LIMIT 5";
if ($res = $conn->query($sqlTop)) {
    while ($r = $res->fetch_assoc()) {
        $topProductos[] = $r;
    }
}

// --- Últimas ventas ---
$ultimasVentas = [];
$sqlUlt = "SELECT folio, fecha_venta, total, metodo_pago, tipo_pago
           FROM ventas_comanda
           WHERE estatus = 1
           ORDER BY fecha_venta DESC
           LIMIT 6";
if ($res = $conn->query($sqlUlt)) {
    while ($r = $res->fetch_assoc()) {
        $ultimasVentas[] = $r;
    }
}

// --- Productos con stock bajo ---
$productosStockBajo = [];
$sqlBajo = "SELECT nombre_producto, stock, ubicacion
            FROM productos
            WHERE activo = 1 AND stock <= 5
            ORDER BY stock ASC, nombre_producto ASC
            LIMIT 6";
if ($res = $conn->query($sqlBajo)) {
    while ($r = $res->fetch_assoc()) {
        $productosStockBajo[] = $r;
    }
}

$alertas = $stockBajo + ($ventasHoyCnt === 0 ? 1 : 0);
?>
<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Dashboard — VendingBox Admin</title>
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="styles_css/tabla_productos.css">
    <link rel="stylesheet" href="styles_css/dashboard.css">
    <link rel="shortcut icon" href="assets/images/favicon.png" />
  </head>
  <body>
    <div class="container-scroller">
      <?php include 'navbar.php'; ?>
      <div class="container-fluid page-body-wrapper">
        <?php include 'menu.php'; ?>
        <div class="main-panel">
          <div class="content-wrapper">
            <div class="vb-dashboard">

              <header class="vb-hero">
                <div>
                  <h2>Hola, <?php echo htmlspecialchars($nombreUsuario); ?></h2>
                  <p>Resumen de tu máquina VendingBox</p>
                </div>
                <div class="vb-hero-meta">
                  <span class="vb-pill"><i class="mdi mdi-calendar"></i> <?php echo htmlspecialchars($fechaLabel); ?></span>
                  <?php if ($alertas > 0): ?>
                    <span class="vb-pill"><i class="mdi mdi-bell-alert"></i> <?php echo (int)$alertas; ?> alerta(s)</span>
                  <?php else: ?>
                    <span class="vb-pill vb-pill--ok"><i class="mdi mdi-check-circle"></i> Operación normal</span>
                  <?php endif; ?>
                  <span class="vb-pill vb-pill--muted"><i class="mdi mdi-database"></i> <?php echo defined('USING_DB') ? USING_DB : 'BD'; ?></span>
                </div>
              </header>

              <section class="vb-kpi-grid">
                <div class="vb-kpi-featured">
                  <div class="vb-kpi-label">Ingresos del mes</div>
                  <div class="vb-kpi-value">$<?php echo number_format($ventasMesMonto, 0); ?></div>
                  <div class="vb-kpi-foot"><?php echo (int)$ventasMesCnt; ?> ventas · Promedio $<?php echo number_format($promedioVenta, 2); ?></div>
                </div>
                <div class="vb-kpi-mini">
                  <div class="vb-kpi-mini-top">
                    <span class="vb-kpi-label">Ventas hoy</span>
                    <span class="vb-kpi-icon"><i class="mdi mdi-cash-multiple"></i></span>
                  </div>
                  <div class="vb-kpi-value"><?php echo (int)$ventasHoyCnt; ?></div>
                  <div class="vb-kpi-foot">$<?php echo number_format($ventasHoyMonto, 2); ?></div>
                </div>
                <div class="vb-kpi-mini">
                  <div class="vb-kpi-mini-top">
                    <span class="vb-kpi-label">Productos</span>
                    <span class="vb-kpi-icon"><i class="mdi mdi-package-variant"></i></span>
                  </div>
                  <div class="vb-kpi-value"><?php echo (int)$totalProductos; ?></div>
                  <div class="vb-kpi-foot"><?php echo (int)$stockTotal; ?> en stock</div>
                </div>
                <div class="vb-kpi-mini">
                  <div class="vb-kpi-mini-top">
                    <span class="vb-kpi-label">Stock bajo</span>
                    <span class="vb-kpi-icon"><i class="mdi mdi-alert-circle-outline"></i></span>
                  </div>
                  <div class="vb-kpi-value"><?php echo (int)$stockBajo; ?></div>
                  <div class="vb-kpi-foot"><?php echo (int)$productosInactivos; ?> inactivos</div>
                </div>
              </section>

              <section class="vb-main-grid">
                <div class="vb-stack">
                  <article class="vb-panel">
                    <div class="vb-panel-head">
                      <h3><i class="mdi mdi-chart-bar"></i> Ventas últimos 7 días</h3>
                      <a href="movimientos.php">Ver movimientos →</a>
                    </div>
                    <div class="vb-panel-body vb-panel-body--chart">
                      <canvas id="chartVentas7d" height="110"></canvas>
                    </div>
                  </article>

                  <article class="vb-panel">
                    <div class="vb-panel-head">
                      <h3><i class="mdi mdi-receipt"></i> Últimas ventas</h3>
                      <a href="movimientos.php">Ver todas →</a>
                    </div>
                    <div class="vb-panel-body vb-panel-body--table">
                      <div class="vb-table-scroll">
                        <table class="table table-borderless vb-table mb-0">
                          <thead>
                            <tr>
                              <th>Folio</th>
                              <th>Fecha</th>
                              <th>Total</th>
                              <th>Pago</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php if (empty($ultimasVentas)): ?>
                              <tr><td colspan="4" class="vb-empty">Aún no hay ventas registradas</td></tr>
                            <?php else: ?>
                              <?php foreach ($ultimasVentas as $v): ?>
                                <tr>
                                  <td class="font-weight-bold"><?php echo htmlspecialchars($v['folio']); ?></td>
                                  <td><?php echo date('d/m H:i', strtotime($v['fecha_venta'])); ?></td>
                                  <td class="font-weight-bold">$<?php echo number_format((float)$v['total'], 2); ?></td>
                                  <td>
                                    <span class="vb-badge-ok">
                                      <?php echo htmlspecialchars($v['metodo_pago'] ?: ((int)$v['tipo_pago'] === 1 ? 'Tarjeta' : 'Efectivo')); ?>
                                    </span>
                                  </td>
                                </tr>
                              <?php endforeach; ?>
                            <?php endif; ?>
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </article>
                </div>

                <aside class="vb-stack vb-stack--rail">
                  <article class="vb-panel">
                    <div class="vb-panel-head">
                      <h3><i class="mdi mdi-flash"></i> Accesos rápidos</h3>
                    </div>
                    <div class="vb-panel-body">
                      <nav class="vb-actions">
                        <a href="formulario_producto.php" class="vb-action-link"><i class="mdi mdi-plus-box"></i> Agregar producto</a>
                        <a href="tabla_productos.php" class="vb-action-link"><i class="mdi mdi-format-list-bulleted"></i> Consultar productos</a>
                        <a href="movimientos.php" class="vb-action-link"><i class="mdi mdi-swap-horizontal"></i> Movimientos</a>
                        <a href="cortes_caja.php" class="vb-action-link"><i class="mdi mdi-cash-register"></i> Cortes de caja</a>
                      </nav>
                    </div>
                  </article>

                  <article class="vb-panel">
                    <div class="vb-panel-head">
                      <h3><i class="mdi mdi-trophy"></i> Top productos</h3>
                      <span style="color:var(--vb-muted);font-size:0.72rem">30 días</span>
                    </div>
                    <div class="vb-panel-body">
                      <?php if (empty($topProductos)): ?>
                        <div class="vb-empty-state"><i class="mdi mdi-chart-line-variant"></i>Sin ventas en el período</div>
                      <?php else: ?>
                        <ol class="vb-rank-list">
                          <?php foreach ($topProductos as $i => $tp): ?>
                            <li class="vb-rank-item">
                              <span class="vb-rank-num"><?php echo $i + 1; ?></span>
                              <span class="vb-rank-name" title="<?php echo htmlspecialchars($tp['nombre_producto']); ?>">
                                <?php echo htmlspecialchars($tp['nombre_producto']); ?>
                              </span>
                              <span class="vb-rank-qty">×<?php echo (int)$tp['unidades']; ?></span>
                              <span class="vb-rank-money">$<?php echo number_format((float)$tp['ingresos'], 0); ?></span>
                            </li>
                          <?php endforeach; ?>
                        </ol>
                      <?php endif; ?>
                    </div>
                  </article>

                  <article class="vb-panel vb-panel--grow">
                    <div class="vb-panel-head">
                      <h3><i class="mdi mdi-alert"></i> Inventario</h3>
                      <a href="tabla_productos.php">Ver stock →</a>
                    </div>
                    <div class="vb-panel-body vb-panel-body--fill">
                      <?php if (empty($productosStockBajo)): ?>
                        <div class="vb-empty-state"><i class="mdi mdi-check-all"></i>Stock en niveles normales</div>
                      <?php else: ?>
                        <div class="table-responsive">
                          <table class="table table-borderless vb-table mb-0">
                            <thead>
                              <tr>
                                <th>Producto</th>
                                <th>Stock</th>
                                <th>Ubic.</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($productosStockBajo as $pb): ?>
                                <tr>
                                  <td><?php echo htmlspecialchars($pb['nombre_producto']); ?></td>
                                  <td>
                                    <span class="<?php echo (int)$pb['stock'] === 0 ? 'vb-badge-danger' : 'vb-badge-warn'; ?>">
                                      <?php echo (int)$pb['stock']; ?>
                                    </span>
                                  </td>
                                  <td><?php echo htmlspecialchars($pb['ubicacion'] ?? '—'); ?></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      <?php endif; ?>
                    </div>
                  </article>
                </aside>
              </section>

            </div>
          </div>
          <?php include 'footer.php'; ?>
        </div>
      </div>
    </div>

    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="assets/vendors/chart.js/chart.umd.js"></script>
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/template.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/todolist.js"></script>
    <script src="../../js/corte-automatico.js"></script>
    <script>
    (function () {
      const ctx = document.getElementById('chartVentas7d');
      if (!ctx || typeof Chart === 'undefined') return;

      const labels = <?php echo json_encode($chartLabels, JSON_UNESCAPED_UNICODE); ?>;
      const ventas = <?php echo json_encode($chartVentasData); ?>;
      const montos = <?php echo json_encode($chartMontosData); ?>;

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: labels,
          datasets: [
            {
              label: 'Ventas',
              data: ventas,
              backgroundColor: 'rgba(242, 220, 0, 0.55)',
              borderColor: '#f2dc00',
              borderWidth: 1,
              borderRadius: 6,
              yAxisID: 'y'
            },
            {
              label: 'Monto ($)',
              data: montos,
              type: 'line',
              borderColor: '#ffffff',
              backgroundColor: 'rgba(255,255,255,0.08)',
              tension: 0.35,
              yAxisID: 'y1'
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: true,
          plugins: {
            legend: {
              labels: { color: '#f5f5f5' }
            }
          },
          scales: {
            x: {
              ticks: { color: '#a3a3a3' },
              grid: { color: 'rgba(255,255,255,0.06)' }
            },
            y: {
              position: 'left',
              ticks: { color: '#a3a3a3' },
              grid: { color: 'rgba(255,255,255,0.06)' }
            },
            y1: {
              position: 'right',
              ticks: { color: '#f2dc00' },
              grid: { drawOnChartArea: false }
            }
          }
        }
      });
    })();
    </script>
  </body>
</html>
