<?php
require_once __DIR__ . '/../app/Core/Database.php';
require_once __DIR__ . '/../app/Models/Category.php';
require_once __DIR__ . '/../app/Models/Product.php';
require_once __DIR__ . '/../app/Controllers/ProductController.php';

use App\Controllers\ProductController;

$controller = new ProductController();
$data = $controller->getProductsTableViewData();

$productos = $data['productos'];
$totalProductos = $data['totalProductos'];
$totalStock = $data['totalStock'];
$productosActivos = $data['productosActivos'];
$totalCategorias = $data['totalCategorias'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Consulta de Productos | PauloBot Store</title>

    <!-- Google Fonts & FontAwesome 6 (HTTPS CDN) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Vendor Styles (HTTPS CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- Layout & Component CSS -->
    <link rel="stylesheet" href="./assets/css/layout.css">
    <link rel="stylesheet" href="./assets/css/tabla_productos.css">
</head>
<body>

    <div class="container-scroller">
        <!-- Navbar Component -->
        <?php include 'navbar.php'; ?>

        <div class="container-fluid page-body-wrapper">
            <!-- Sidebar Component -->
            <?php include 'menu.php'; ?>

            <!-- Panel Principal -->
            <div class="main-panel">
                <div class="content-wrapper p-4">

                    <div class="tp-page">
                        <!-- Header -->
                        <header class="tp-header">
                            <div class="tp-header-icon"><i class="fas fa-boxes-stacked"></i></div>
                            <div class="tp-header-text">
                                <h1>Consulta de Productos</h1>
                                <p>Inventario general y gestión de productos en PauloBot Store</p>
                            </div>
                        </header>

                        <!-- Mini Stats Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <div class="stat-card-mini">
                                    <div class="stat-icon stat-icon-indigo">
                                        <i class="fas fa-box"></i>
                                    </div>
                                    <div>
                                        <div class="stat-num"><?php echo $totalProductos; ?></div>
                                        <div class="stat-label">Total Productos Registrados</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card-mini">
                                    <div class="stat-icon stat-icon-green">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div>
                                        <div class="stat-num"><?php echo $productosActivos; ?></div>
                                        <div class="stat-label">Productos Activos</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card-mini">
                                    <div class="stat-icon stat-icon-yellow">
                                        <i class="fas fa-layer-group"></i>
                                    </div>
                                    <div>
                                        <div class="stat-num"><?php echo $totalStock; ?></div>
                                        <div class="stat-label">Unidades Totales en Stock</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table Card -->
                        <div class="table-card">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <h2 class="fs-5 fw-bold text-white mb-0"><i class="fas fa-list me-2 text-primary"></i> Catálogo Completo</h2>
                                <a href="formulario_producto.php" class="btn btn-primary btn-sm rounded-pill px-3">
                                    <i class="fas fa-plus me-1"></i> Nuevo Producto
                                </a>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-custom align-middle" id="tablaProductos">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Imagen</th>
                                            <th>Producto</th>
                                            <th>Categoría</th>
                                            <th>Ubicación</th>
                                            <th>Precio</th>
                                            <th>Stock</th>
                                            <th>Estado</th>
                                            <th class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($productos)): ?>
                                            <?php foreach ($productos as $prod): ?>
                                                <tr>
                                                    <td><code>#<?php echo $prod['id_producto']; ?></code></td>
                                                    <td>
                                                        <img src="get_producto_imagen.php?id=<?php echo $prod['id_producto']; ?>" class="product-thumb" loading="lazy" alt="<?php echo htmlspecialchars($prod['nombre_producto']); ?>" />
                                                    </td>
                                                    <td>
                                                        <div class="fw-bold text-white"><?php echo htmlspecialchars($prod['nombre_producto']); ?></div>
                                                        <?php if (!empty($prod['sku'])): ?>
                                                            <small class="text-muted">SKU: <?php echo htmlspecialchars($prod['sku']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-dark border border-secondary text-light">
                                                            <?php echo htmlspecialchars($prod['nombre_categoria'] ?? 'Sin categoría'); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-indigo-subtle text-primary border border-primary-subtle fw-bold">
                                                            <?php echo htmlspecialchars($prod['ubicacion'] ?? 'N/A'); ?>
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold text-success">
                                                        $<?php echo number_format((float)$prod['precio'], 2); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ((int)$prod['stock'] <= 5): ?>
                                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle fw-bold">
                                                                <?php echo $prod['stock']; ?> uds (Bajo)
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">
                                                                <?php echo $prod['stock']; ?> uds
                                                            </span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ((int)$prod['activo'] === 1): ?>
                                                            <span class="badge bg-success rounded-pill px-2 py-1">Activo</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary rounded-pill px-2 py-1">Inactivo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="formulario_producto.php?id=<?php echo $prod['id_producto']; ?>" class="btn btn-outline-light btn-sm rounded-circle me-1" title="Editar">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger btn-sm rounded-circle btn-delete-product" data-id="<?php echo $prod['id_producto']; ?>" data-nombre="<?php echo htmlspecialchars($prod['nombre_producto']); ?>" title="Eliminar">
                                                            <i class="fas fa-trash-can"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
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

    <!-- Scripts JS Externos (HTTPS CDN) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Scripts JS Separados -->
    <script src="./assets/js/layout.js" defer></script>
    <script src="./assets/js/tabla_productos.js" defer></script>
</body>
</html>
