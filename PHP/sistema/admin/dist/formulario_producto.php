<?php
ini_set("log_errors", 1);
ini_set("error_log", "php_errors.log");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Agregar Producto - Vending Box</title>
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  <link rel="stylesheet" href="styles_css/admin-layout.css">
  <link rel="stylesheet" href="styles_css/formulario_producto.css">
</head>

<body>
  <div class="container-scroller">
    <?php include "navbar.php"; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include "menu.php"; ?>
      <div class="main-panel">
        <div class="content-wrapper">

          <div class="fp-page">
            <header class="fp-header">
              <div class="fp-header-icon"><i class="fas fa-box-open"></i></div>
              <div class="fp-header-text">
                <h1>Agregar Nuevo Producto</h1>
                <p>Registra un producto en tu máquina VendingBox</p>
              </div>
            </header>

            <form class="fp-form forms-sample" method="post" enctype="multipart/form-data" id="formProducto">
              <input type="hidden" name="user_id" id="user_id" required />

              <div class="fp-grid">

                <!-- ── Columna principal ── -->
                <div class="fp-main">

                  <!-- Datos básicos -->
                  <section class="fp-card">
                    <header class="fp-card-head">
                      <span class="fp-card-num">1</span>
                      <div>
                        <h2><i class="fas fa-info-circle"></i> Datos del producto</h2>
                        <span>Información general y descripción</span>
                      </div>
                    </header>
                    <div class="fp-card-body">
                      <div class="fp-row fp-row-2">
                        <div class="form-group">
                          <label for="nombreProducto">
                            <i class="fas fa-tag"></i> Nombre <span class="text-danger">*</span>
                          </label>
                          <input type="text" class="form-control" id="nombreProducto" name="nombre_producto"
                            placeholder="Ej: Coca Cola 600ml" required>
                        </div>
                        <div class="form-group">
                          <label for="sku">
                            <i class="fas fa-barcode"></i> SKU / Código
                          </label>
                          <input type="text" class="form-control" id="sku" name="sku" placeholder="COD-001">
                        </div>
                      </div>
                      <div class="form-group fp-mb-0">
                        <label for="descripcionProducto">
                          <i class="fas fa-align-left"></i> Descripción <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="descripcionProducto" name="descripcion" rows="3"
                          placeholder="Describe el producto..." required></textarea>
                      </div>
                    </div>
                  </section>

                  <!-- Categorización -->
                  <section class="fp-card">
                    <header class="fp-card-head">
                      <span class="fp-card-num">2</span>
                      <div>
                        <h2><i class="fas fa-folder-tree"></i> Clasificación</h2>
                        <span>Categoría y subcategoría del producto</span>
                      </div>
                    </header>
                    <div class="fp-card-body">
                      <div class="fp-row fp-row-2">
                        <div class="form-group fp-mb-0">
                          <label for="categoriaProducto">
                            <i class="fas fa-folder"></i> Categoría <span class="text-danger">*</span>
                          </label>
                          <div class="fp-input-action">
                            <select class="form-control" id="categoriaProducto" name="id_categoria" required>
                              <option value="" disabled selected>Selecciona una categoría</option>
                            </select>
                            <button type="button" class="btn btn-success btn-sm fp-btn-icon" id="btnAddCategoria" title="Nueva categoría">
                              <i class="fas fa-plus"></i>
                            </button>
                          </div>
                        </div>
                        <div class="form-group fp-mb-0" id="subcategoriaContainer" style="display:none;">
                          <label for="subcategoriaProducto">
                            <i class="fas fa-folder-open"></i> Subcategoría
                          </label>
                          <div class="fp-input-action">
                            <select class="form-control" id="subcategoriaProducto" name="id_subcategoria">
                              <option value="">Sin subcategoría</option>
                            </select>
                            <button type="button" class="btn btn-secondary btn-sm fp-btn-icon" id="btnAddSubcatModal" title="Nueva subcategoría">
                              <i class="fas fa-plus"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </section>

                  <!-- Precio e inventario -->
                  <section class="fp-card">
                    <header class="fp-card-head">
                      <span class="fp-card-num">3</span>
                      <div>
                        <h2><i class="fas fa-dollar-sign"></i> Precio e inventario</h2>
                        <span>Precio, stock y ubicación en la máquina</span>
                      </div>
                    </header>
                    <div class="fp-card-body">
                      <div class="fp-row fp-row-4">
                        <div class="form-group">
                          <label for="precioProducto">
                            <i class="fas fa-money-bill-wave"></i> Precio <span class="text-danger">*</span>
                          </label>
                          <div class="fp-input-prefix">
                            <span>$</span>
                            <input type="number" class="form-control" id="precioProducto" name="precio"
                              placeholder="0.00" step="0.01" min="0" required>
                          </div>
                        </div>
                        <div class="form-group">
                          <label for="stockDisponible">
                            <i class="fas fa-warehouse"></i> Stock <span class="text-danger">*</span>
                          </label>
                          <input type="number" class="form-control" id="stockDisponible" name="stock"
                            placeholder="0" min="0" required>
                        </div>
                        <div class="form-group">
                          <label for="ubicacion">
                            <i class="fas fa-map-marker-alt"></i> Ubicación <span class="text-danger">*</span>
                          </label>
                          <input type="text" class="form-control text-uppercase fp-ubicacion" id="ubicacion" name="ubicacion"
                            placeholder="A1" maxlength="2" pattern="[A-Za-z][0-9]" required>
                          <small class="form-text text-muted">Formato: A1, B2…</small>
                        </div>
                        <div class="form-group">
                          <label class="fp-label-invisible">Opciones</label>
                          <div class="form-check-custom fp-check-inline">
                            <input type="checkbox" class="form-check-input" id="checkDescuento" name="usa_descuento">
                            <label class="form-check-label" for="checkDescuento">
                              <i class="fas fa-percent"></i> Con descuento
                            </label>
                          </div>
                        </div>
                      </div>
                      <div class="fp-row fp-row-2" id="grupoPrecioDescuento" style="display:none;">
                        <div class="form-group fp-mb-0">
                          <label for="precioDescuento">
                            <i class="fas fa-tags"></i> Precio con descuento
                          </label>
                          <div class="fp-input-prefix">
                            <span>$</span>
                            <input type="number" class="form-control" id="precioDescuento" name="precio_descuento"
                              placeholder="0.00" step="0.01" min="0">
                          </div>
                        </div>
                      </div>
                    </div>
                  </section>

                </div>

                <!-- ── Panel lateral ── -->
                <aside class="fp-aside">

                  <!-- Vista previa -->
                  <div class="fp-preview-panel product-preview" id="productPreview">
                    <header class="fp-preview-head">
                      <i class="fas fa-eye"></i> Vista previa
                    </header>
                    <div class="fp-preview-body">
                      <div class="fp-preview-img-wrap">
                        <img id="previewImg" src="https://via.placeholder.com/150/1a1a1a/f2dc00?text=VB" alt="Producto">
                      </div>
                      <div class="fp-preview-info">
                        <h3 id="previewNombre">—</h3>
                        <p class="fp-preview-price">$<span id="previewPrecio">0.00</span></p>
                        <ul class="fp-preview-meta">
                          <li><i class="fas fa-warehouse"></i> Stock: <strong id="previewStock">0</strong></li>
                          <li><i class="fas fa-map-marker-alt"></i> Ubicación: <strong id="previewUbicacion">—</strong></li>
                        </ul>
                        <div class="fp-preview-badges">
                          <span class="badge badge-success" id="previewActivoBadge" style="display:none;">
                            <i class="fas fa-check"></i> Activo
                          </span>
                          <span class="badge badge-warning" id="previewDestacadoBadge" style="display:none;">
                            <i class="fas fa-star"></i> Destacado
                          </span>
                          <span class="badge badge-info" id="previewDescuentoBadge" style="display:none;">
                            <i class="fas fa-percent"></i> Descuento
                          </span>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Imagen -->
                  <section class="fp-card fp-card-compact">
                    <header class="fp-card-head fp-card-head-sm">
                      <h2><i class="fas fa-camera"></i> Imagen <span class="text-danger">*</span></h2>
                    </header>
                    <div class="fp-card-body">
                      <input type="file" name="img[]" class="file-upload-default" id="imgInput" required accept="image/*">
                      <div class="fp-upload-zone">
                        <div class="input-group">
                          <input type="text" class="form-control file-upload-info" disabled placeholder="Seleccionar imagen">
                          <button class="file-upload-browse btn btn-primary" type="button">
                            <i class="fas fa-upload"></i>
                          </button>
                        </div>
                        <div class="fp-img-preview-wrap">
                          <img id="imgPreview" src="#" alt="Vista previa" style="display:none;" />
                        </div>
                      </div>
                      <input type="hidden" name="imagen_principal" id="imgBase64">
                    </div>
                  </section>

                  <!-- Estado y opciones -->
                  <section class="fp-card fp-card-compact">
                    <header class="fp-card-head fp-card-head-sm">
                      <h2><i class="fas fa-sliders-h"></i> Estado</h2>
                    </header>
                    <div class="fp-card-body fp-options-stack">
                      <div class="form-check-custom">
                        <input type="checkbox" class="form-check-input" id="activo" name="activo" checked>
                        <label class="form-check-label" for="activo">
                          <i class="fas fa-toggle-on"></i> Producto activo
                        </label>
                      </div>
                      <div class="form-check-custom">
                        <input type="checkbox" class="form-check-input" id="destacado" name="destacado">
                        <label class="form-check-label" for="destacado">
                          <i class="fas fa-star"></i> Producto destacado
                        </label>
                      </div>
                      <div class="form-group fp-mb-0" id="grupoOrdenDestacado" style="display:none;">
                        <label for="ordenDestacado">
                          <i class="fas fa-sort-numeric-down"></i> Orden en destacados
                        </label>
                        <input type="number" class="form-control" id="ordenDestacado" name="orden_destacado"
                          placeholder="1" min="1" step="1">
                      </div>
                    </div>
                  </section>

                </aside>
              </div>

              <!-- Barra de acciones -->
              <footer class="fp-footer">
                <button type="submit" class="btn btn-primary btn-lg">
                  <i class="fas fa-save"></i> Guardar Producto
                </button>
                <button class="btn btn-light btn-lg" type="reset">
                  <i class="fas fa-times"></i> Cancelar
                </button>
              </footer>
            </form>
          </div>

          <?php include "footer.php"; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Agregar Categoría -->
  <div class="modal fade" id="modalCategoria" tabindex="-1" aria-labelledby="modalCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalCategoriaLabel">
            <i class="fas fa-folder-plus"></i> Agregar Nueva Categoría
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="inputNuevaCategoria" class="form-label">
              <i class="fas fa-tag"></i> Nombre de la categoría <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="inputNuevaCategoria" placeholder="Ej: Bebidas, Snacks..." />
          </div>
          <div class="form-check-custom">
            <input type="checkbox" class="form-check-input" id="checkSubcategorias" />
            <label class="form-check-label" for="checkSubcategorias">
              <i class="fas fa-folder-open"></i> ¿Agregar subcategoría inmediatamente?
            </label>
          </div>
          <div class="mb-3" id="subcatInputSection" style="display:none;">
            <label for="inputNuevaSubcategoria" class="form-label">
              <i class="fas fa-tag"></i> Subcategoría
            </label>
            <input type="text" class="form-control" id="inputNuevaSubcategoria" placeholder="Ej: Refrescos, Jugos..." />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button type="button" class="btn btn-success" id="btnGuardarCategoria">
            <i class="fas fa-save"></i> Guardar Categoría
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal Agregar Subcategoría -->
  <div class="modal fade" id="modalSubcat" tabindex="-1" aria-labelledby="modalSubcatLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalSubcatLabel">
            <i class="fas fa-folder-open"></i> Agregar Subcategoría
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Categoría seleccionada</label>
            <div class="alert alert-info py-2 px-3 mb-0" id="categoriaSeleccionadaInfo">
              <i class="fas fa-info-circle"></i> <strong id="nombreCategoriaSeleccionada"></strong>
            </div>
          </div>
          <div class="mb-3">
            <label for="inputSubcatModal" class="form-label">
              <i class="fas fa-tag"></i> Nueva subcategoría <span class="text-danger">*</span>
            </label>
            <input type="text" class="form-control" id="inputSubcatModal" placeholder="Nombre de la subcategoría" />
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
          </button>
          <button type="button" class="btn btn-success" id="btnGuardarSubcatModal">
            <i class="fas fa-plus"></i> Agregar Subcategoría
          </button>
        </div>
      </div>
    </div>
  </div>

  <script src="assets/vendors/js/vendor.bundle.base.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/js/off-canvas.js"></script>
  <script src="assets/js/template.js"></script>
  <script src="assets/js/settings.js"></script>
  <script src="assets/js/todolist.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="modulos_JS/formulario_producto.js"></script>
</body>
</html>
