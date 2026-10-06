<?php

include "db_config_dual.php";
// Registrar errores en un archivo de log
ini_set("log_errors", 1);
ini_set("error_log", "php_errors.log");

// Aumentar límite de memoria para manejar muchos productos
ini_set('memory_limit', '2048M');

// Cargar imagen principal para mostrar en la tabla
$sql = "SELECT DISTINCT p.id_producto, p.id_usuario, p.id_categoria, p.id_subcategoria, 
        p.nombre_producto, p.precio, p.descuento, p.stock, 
        p.sku, p.ubicacion, p.destacado, p.orden_destacado, p.activo,
        p.fecha_creacion, p.fecha_actualizacion, p.imagen_principal,
        c.nombre_categoria, s.nombre_subcategoria 
        FROM productos p
        LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
        LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
        ORDER BY p.id_producto";
$result = $conn->query($sql);
$productos = [];
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $productos[] = $row;
  }
}
// Liberar memoria del result set
$result->free();
// Obtener categorías
$categorias = [];
$resCat = $conn->query("SELECT * FROM categorias");
if ($resCat && $resCat->num_rows > 0) {
  while ($row = $resCat->fetch_assoc()) {
    $categorias[] = $row;
  }
  $resCat->free();
}
// Obtener subcategorías
$subcategorias = [];
$resSub = $conn->query("SELECT * FROM subcategorias");
if ($resSub && $resSub->num_rows > 0) {
  while ($row = $resSub->fetch_assoc()) {
    $subcategorias[] = $row;
  }
  $resSub->free();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Tabla Producto</title>
  <!-- plugins:css -->
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  
  <!-- DataTables CSS desde CDN -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- endinject -->
  <!-- inject:css -->
  <link rel="stylesheet" href="assets/css/style.css">
  <!-- endinject -->
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  
  <!-- Estilos personalizados de tabla de productos -->
  <link rel="stylesheet" href="styles_css/admin-layout.css">
  <link rel="stylesheet" href="styles_css/tabla_productos.css">
</head>

<body>
  <div class="container-scroller">
    <?php include "navbar.php"; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include "menu.php"; ?>
      <div class="main-panel">
        <div class="content-wrapper">

          <?php
            $totalProductos = count($productos);
            $totalStock = array_sum(array_column($productos, 'stock'));
            $productosActivos = count(array_filter($productos, function ($p) { return $p['activo'] == 1; }));
            $totalCategorias = count($categorias);
          ?>

          <div class="tp-page">

            <header class="tp-header">
              <div class="tp-header-icon"><i class="mdi mdi-package-variant"></i></div>
              <div class="tp-header-text">
                <h1>Consulta de Productos</h1>
                <p>Inventario y gestión de productos en tu máquina</p>
              </div>
              <a href="formulario_producto.php" class="btn btn-primary tp-header-btn">
                <i class="mdi mdi-plus"></i> Nuevo Producto
              </a>
            </header>

            <!-- KPIs -->
            <div class="tp-kpi-grid">
              <div class="tp-kpi">
                <div class="tp-kpi-icon"><i class="mdi mdi-package-variant"></i></div>
                <div class="tp-kpi-data">
                  <span class="tp-kpi-label">Total Productos</span>
                  <strong class="tp-kpi-value"><?php echo $totalProductos; ?></strong>
                </div>
              </div>
              <div class="tp-kpi">
                <div class="tp-kpi-icon tp-kpi-icon--stock"><i class="mdi mdi-warehouse"></i></div>
                <div class="tp-kpi-data">
                  <span class="tp-kpi-label">Stock Total</span>
                  <strong class="tp-kpi-value"><?php echo number_format($totalStock); ?></strong>
                </div>
              </div>
              <div class="tp-kpi">
                <div class="tp-kpi-icon tp-kpi-icon--active"><i class="mdi mdi-check-circle-outline"></i></div>
                <div class="tp-kpi-data">
                  <span class="tp-kpi-label">Productos Activos</span>
                  <strong class="tp-kpi-value"><?php echo $productosActivos; ?></strong>
                </div>
              </div>
              <div class="tp-kpi">
                <div class="tp-kpi-icon tp-kpi-icon--cat"><i class="mdi mdi-tag-multiple-outline"></i></div>
                <div class="tp-kpi-data">
                  <span class="tp-kpi-label">Categorías</span>
                  <strong class="tp-kpi-value"><?php echo $totalCategorias; ?></strong>
                </div>
              </div>
            </div>

            <!-- Tabla -->
            <section class="tp-card">
              <header class="tp-card-toolbar">
                <div class="tp-toolbar-title">
                  <h2><i class="mdi mdi-format-list-bulleted"></i> Gestión de Productos</h2>
                  <span><?php echo $totalProductos; ?> productos registrados</span>
                </div>
                <div class="tp-toolbar-search" id="tpSearchSlot"></div>
              </header>
              <div class="tp-card-body">
                <div class="table-responsive tp-table-wrap">
                  <table class="table table-striped table-borderless" id="productosTable">
                      <thead>
                        <tr>
                          <th>Imagen</th>
                          <th>Producto</th>
                          <th>Categoría</th>
                          <th>Precio</th>
                          <th>Stock</th>
                          <th>Ubicación</th>
                          <th>Estado</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($productos)): ?>
                          <tr>
                            <td colspan="8" class="text-center">No hay productos registrados</td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($productos as $producto): ?>
                            <?php
                            // NO incluir imágenes en data-producto para evitar problemas de memoria
                            $productoParaJSON = $producto;
                            // Las imágenes se cargarán después mediante AJAX si se necesitan
                            ?>
                            <tr data-producto='<?php echo json_encode($productoParaJSON, JSON_HEX_APOS | JSON_HEX_QUOT); ?>' 
                                data-id="<?php echo $producto['id_producto']; ?>">
                              <td>
                                <?php if (!empty($producto['imagen_principal'])): ?>
                                  <img src="<?php echo $producto['imagen_principal']; ?>" 
                                       alt="<?php echo htmlspecialchars($producto['nombre_producto']); ?>" 
                                       class="product-image">
                                <?php else: ?>
                                  <div class="product-image d-flex align-items-center justify-content-center bg-light">
                                    <i class="mdi mdi-image-outline text-muted"></i>
                                  </div>
                                <?php endif; ?>
                              </td>
                              <td>
                                <div>
                                  <strong><?php echo htmlspecialchars($producto['nombre_producto'] ?? ''); ?></strong>
                                  <?php if (!empty($producto['sku'])): ?>
                                    <br><small class="text-muted">SKU: <?php echo htmlspecialchars($producto['sku']); ?></small>
                                  <?php endif; ?>
                                </div>
                              </td>
                              <td>
                                <span class="badge badge-info">
                                  <?php echo htmlspecialchars($producto['nombre_categoria'] ?? 'Sin categoría'); ?>
                                </span>
                                <?php if (!empty($producto['nombre_subcategoria'])): ?>
                                  <br><small class="text-muted"><?php echo htmlspecialchars($producto['nombre_subcategoria']); ?></small>
                                <?php endif; ?>
                              </td>
                              <td>
                                <div class="price-display">
                                  $<?php echo number_format($producto['precio'] ?? 0, 2); ?>
                                </div>
                                <?php if (!empty($producto['descuento']) && $producto['descuento'] > 0): ?>
                                  <small class="discount-price">
                                    <?php echo $producto['descuento']; ?>% desc.
                                  </small>
                                <?php endif; ?>
                              </td>
                              <td>
                                <?php 
                                $stock = $producto['stock'] ?? 0;
                                $badgeClass = $stock > 10 ? 'success' : ($stock > 0 ? 'warning' : 'danger');
                                ?>
                                <span class="badge badge-<?php echo $badgeClass; ?> stock-badge">
                                  <?php echo $stock; ?> unidades
                                </span>
                              </td>
                              <td>
                                <?php if (!empty($producto['ubicacion'])): ?>
                                  <span class="badge badge-primary tp-ubicacion-badge">
                                    <i class="mdi mdi-map-marker"></i> <?php echo htmlspecialchars($producto['ubicacion']); ?>
                                  </span>
                                <?php else: ?>
                                  <span class="badge badge-secondary">
                                    <i class="mdi mdi-map-marker-off"></i> Sin ubicación
                                  </span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <?php if ($producto['activo'] == 1): ?>
                                  <span class="badge badge-success">Activo</span>
                                <?php else: ?>
                                  <span class="badge badge-secondary">Inactivo</span>
                                <?php endif; ?>
                                
                                <?php if ($producto['destacado'] == 1): ?>
                                  <br><span class="badge badge-warning">Destacado</span>
                                <?php endif; ?>
                              </td>
                              <td>
                                <button class="btn btn-sm btn-outline-primary btn-editar" data-id="<?php echo $producto['id_producto']; ?>">
                                  <i class="mdi mdi-pencil-outline"></i> Editar
                                </button>
                              </td>
                            </tr>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                </div>
              </div>
            </section>

          </div><!-- /.tp-page -->
          
        </div>
        
        <!-- Modal Editar Producto (Bootstrap Modal) -->
        <div class="modal fade" id="modalEditarProducto" tabindex="-1" aria-labelledby="modalEditarProductoLabel" aria-hidden="true">
          <div class="modal-dialog modal-xl">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalEditarProductoLabel">
                  <i class="mdi mdi-pencil-outline"></i> Editar Producto
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="formEditarProducto" enctype="multipart/form-data">
                  <input type="hidden" name="id_producto" id="edit_id_producto">
                  
                  <div class="row">
                    <!-- Columna de Imágenes -->
                    <div class="col-md-6">
                      <h6 class="mb-3"><i class="mdi mdi-image-outline"></i> Imagen del Producto</h6>
                      
                      <div class="mb-3">
                        <label class="form-label">Imagen Principal</label>
                        <div class="image-preview-container">
                          <img id="edit_imagen_principal_actual" src="" alt="Imagen principal" 
                               class="img-preview" style="display: none;">
                          <div class="no-image-placeholder" id="edit_no_image_principal">
                            <i class="mdi mdi-image-outline"></i>
                            <span>Sin imagen</span>
                          </div>
                        </div>
                        <input type="file" class="form-control mt-2" name="imagen_principal" id="edit_imagen_principal" accept="image/*">
                      </div>
                    </div>
                    
                    <!-- Columna de Datos -->
                    <div class="col-md-6">
                      <h6 class="mb-3"><i class="mdi mdi-information-outline"></i> Información del Producto</h6>
                      
                      <div class="mb-3">
                        <label class="form-label">Nombre del Producto</label>
                        <input type="text" class="form-control" name="nombre_producto" id="edit_nombre_producto" required>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">Categoría</label>
                            <select class="form-control" name="id_categoria" id="edit_id_categoria" required>
                              <option value="">Selecciona una categoría</option>
                              <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat['id_categoria']; ?>">
                                  <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">Subcategoría</label>
                            <select class="form-control" name="id_subcategoria" id="edit_id_subcategoria">
                              <option value="">Selecciona una subcategoría</option>
                              <?php foreach ($subcategorias as $sub): ?>
                                <option value="<?php echo $sub['id_subcategoria']; ?>" data-categoria="<?php echo $sub['id_categoria']; ?>">
                                  <?php echo htmlspecialchars($sub['nombre_subcategoria']); ?>
                                </option>
                              <?php endforeach; ?>
                            </select>
                          </div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">Precio</label>
                            <div class="input-group">
                              <span class="input-group-text">$</span>
                              <input type="number" class="form-control" name="precio" id="edit_precio" step="0.01" min="0" required>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">Descuento (%)</label>
                            <input type="number" class="form-control" name="descuento" id="edit_descuento" step="0.01" min="0" max="100">
                          </div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">SKU</label>
                            <input type="text" class="form-control" name="sku" id="edit_sku">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control" name="stock" id="edit_stock" min="0" required>
                          </div>
                        </div>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-3">
                            <label class="form-label">
                              <i class="mdi mdi-map-marker"></i> Ubicación en Máquina <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control text-uppercase" name="ubicacion" id="edit_ubicacion" 
                                   placeholder="Ej: A1, B2, C3" maxlength="2" pattern="[A-Za-z][0-9]" 
                                   style="font-weight: bold; font-size: 1.1rem; text-align: center; letter-spacing: 2px;" required>
                            <small class="form-text text-muted">
                              <i class="mdi mdi-information-outline"></i> Formato: Letra + Número (A1, B2, etc.)
                            </small>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="4"></textarea>
                      </div>
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="mb-3">
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" name="activo" id="edit_activo" value="1">
                              <label class="form-check-label" for="edit_activo">
                                Producto Activo
                              </label>
                            </div>
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="mb-3">
                            <div class="form-check">
                              <input class="form-check-input" type="checkbox" name="destacado" id="edit_destacado" value="1">
                              <label class="form-check-label" for="edit_destacado">
                                Producto Destacado
                              </label>
                            </div>
                          </div>
                        </div>
                      </div>
                      
                      <div class="mb-3" id="edit_grupoOrdenDestacado" style="display: none;">
                        <label class="form-label">Orden de destacado <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="orden_destacado" id="edit_orden_destacado"
                          placeholder="Número de orden (1, 2, 3...)" min="1" step="1" style="max-width: 150px;">
                        <small class="form-text text-muted">
                          <i class="mdi mdi-information-outline"></i> Define la posición en la que aparecerá este producto destacado
                        </small>
                      </div>
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="mdi mdi-close"></i> Cancelar
                </button>
                <button type="submit" form="formEditarProducto" class="btn btn-success">
                  <i class="mdi mdi-content-save-outline"></i> Guardar Cambios
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- plugins:js -->
  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <!-- endinject -->
  <!-- inject:js -->
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/template.js"></script>
  <script src="assets/js/settings.js"></script>
  <script src="assets/js/todolist.js"></script>
  <!-- endinject -->
  <!-- Custom js for this page-->
  <script src="assets/js/file-upload.js"></script>
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  <!-- Script principal de tabla de productos -->
  <script>
    // NO cargar todos los productos en memoria - se obtienen del DOM cuando se necesiten
    // const productos = <?php // echo json_encode($productos, JSON_UNESCAPED_UNICODE); ?>;
  </script>
  <script src="modulos_JS/tabla_productos.js"></script>
  
</body>

</html>
