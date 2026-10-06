<?php

include "db_config_dual.php";
// Registrar errores en un archivo de log
ini_set("log_errors", 1);
ini_set("error_log", "php_errors.log");


// Obtener categorías y subcategorías agrupadas correctamente
$categorias = [];
$sql = "SELECT c.id_categoria, c.nombre_categoria, c.imagen_categoria, s.id_subcategoria, s.nombre_subcategoria 
        FROM categorias c 
        LEFT JOIN subcategorias s ON s.id_categoria = c.id_categoria 
        ORDER BY c.nombre_categoria, s.nombre_subcategoria";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $id_categoria = $row['id_categoria'];
    if (!isset($categorias[$id_categoria])) {
      $categorias[$id_categoria] = [
        'id_categoria' => $id_categoria,
        'nombre_categoria' => $row['nombre_categoria'],
        'imagen_categoria' => $row['imagen_categoria'],
        'subcategorias' => []
      ];
    }
    if ($row['id_subcategoria']) {
      $categorias[$id_categoria]['subcategorias'][] = [
        'id_subcategoria' => $row['id_subcategoria'],
        'nombre_subcategoria' => $row['nombre_subcategoria']
      ];
    }
  }
}

// Obtener subcategorías para el select del modal
$subcategorias = [];
$subResult = $conn->query("SELECT * FROM subcategorias");
if ($subResult && $subResult->num_rows > 0) {
  while ($row = $subResult->fetch_assoc()) {
    $subcategorias[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Tabla Categorias</title>
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
  <link rel="stylesheet" href="styles_css/tabla_productos.css">
</head>

<body>
  <div class="container-scroller">
    <?php include "navbar.php"; ?>
    <div class="container-fluid page-body-wrapper">
      <?php include "menu.php"; ?>
      <div class="main-panel">
        <div class="content-wrapper">

          <!-- Resumen de Categorías -->
          <div class="row">
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <p class="card-title">Total Categorías</p>
                    <i class="mdi mdi-tag-multiple-outline icon-lg"></i>
                  </div>
                  <p class="font-weight-500"><?php echo count($categorias); ?></p>
                </div>
              </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <p class="card-title">Total Subcategorías</p>
                    <i class="mdi mdi-tag-outline icon-lg"></i>
                  </div>
                  <p class="font-weight-500"><?php
                  $totalSubcategorias = 0;
                  foreach ($categorias as $cat) {
                    $totalSubcategorias += count($cat['subcategorias']);
                  }
                  echo $totalSubcategorias;
                  ?></p>
                </div>
              </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <p class="card-title">Sin Subcategorías</p>
                    <i class="mdi mdi-alert-circle-outline icon-lg"></i>
                  </div>
                  <p class="font-weight-500"><?php
                  $sinSubcategorias = 0;
                  foreach ($categorias as $cat) {
                    if (empty($cat['subcategorias']))
                      $sinSubcategorias++;
                  }
                  echo $sinSubcategorias;
                  ?></p>
                </div>
              </div>
            </div>
            <div class="col-md-3 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between">
                    <p class="card-title">Organización</p>
                    <i class="mdi mdi-folder-multiple-outline icon-lg"></i>
                  </div>
                  <p class="font-weight-500"><?php echo count($categorias) + $totalSubcategorias; ?></p>
                </div>
              </div>
            </div>
          </div>

          <!-- Tabla de Categorías -->
          <div class="row">
            <div class="col-md-12 grid-margin stretch-card">
              <div class="card">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <p class="card-title mb-0">Gestión de Categorías</p>
                    <div class="d-flex align-items-center gap-2" style="min-width: 320px;">
                      <input id="titulo_categorias" type="text" class="form-control form-control-sm" placeholder="Título para la vista de categorías">
                      <button id="guardar_titulo_categorias" type="button" class="btn btn-outline-primary btn-sm" title="Guardar título">
                        <i class="mdi mdi-content-save"></i>
                      </button>
                    </div>
                    <div>
                      <small class="text-muted"><?php echo count($categorias); ?> categorías registradas</small>
                      <button class="btn btn-primary btn-sm ms-2" id="btnAgregarCategoria">
                        <i class="mdi mdi-plus"></i> Nueva Categoría
                      </button>
                    </div>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-striped table-borderless" id="productosTable">
                      <thead>
                        <tr>
                          <th>Categoría</th>
                          <th>Subcategoría</th>
                          <th>Estado</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($categorias as $cat): ?>
                          <?php if (empty($cat['subcategorias'])): ?>
                            <tr
                              data-producto='<?php echo json_encode(['id_categoria' => $cat['id_categoria'], 'nombre_categoria' => $cat['nombre_categoria'], 'imagen_categoria' => $cat['imagen_categoria']], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                              <td>
                                <div class="d-flex align-items-center">
                                  <?php if (!empty($cat['imagen_categoria'])): ?>
                                    <img src="<?php echo htmlspecialchars($cat['imagen_categoria']); ?>"
                                      alt="<?php echo htmlspecialchars($cat['nombre_categoria']); ?>" 
                                      class="product-image me-2">
                                  <?php else: ?>
                                    <div class="product-image d-flex align-items-center justify-content-center bg-light me-2">
                                      <i class="mdi mdi-image-outline text-muted"></i>
                                    </div>
                                  <?php endif; ?>
                                  <div>
                                    <strong><?php echo htmlspecialchars($cat['nombre_categoria']); ?></strong>
                                    <br><small class="text-muted">ID: <?php echo $cat['id_categoria']; ?></small>
                                  </div>
                                </div>
                              </td>
                              <td>
                                <span class="badge badge-secondary">Sin subcategorías</span>
                              </td>
                              <td>
                                <span class="badge badge-warning">Vacía</span>
                              </td>
                              <td>
                                <button class="btn btn-sm btn-outline-primary btn-editar">
                                  <i class="mdi mdi-pencil-outline"></i> Editar
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-eliminar-categoria ms-1"
                                  data-id="<?php echo $cat['id_categoria']; ?>">
                                  <i class="mdi mdi-delete-outline"></i> Eliminar
                                </button>
                              </td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($cat['subcategorias'] as $index => $sub): ?>
                              <tr
                                data-producto='<?php echo json_encode(['id_categoria' => $cat['id_categoria'], 'nombre_categoria' => $cat['nombre_categoria'], 'imagen_categoria' => $cat['imagen_categoria']], JSON_HEX_APOS | JSON_HEX_QUOT); ?>'>
                                <td>
                                  <?php if ($index === 0): ?>
                                    <div class="d-flex align-items-center">
                                      <?php if (!empty($cat['imagen_categoria'])): ?>
                                        <img src="<?php echo htmlspecialchars($cat['imagen_categoria']); ?>"
                                          alt="<?php echo htmlspecialchars($cat['nombre_categoria']); ?>" 
                                          class="product-image me-2">
                                      <?php else: ?>
                                        <div class="product-image d-flex align-items-center justify-content-center bg-light me-2">
                                          <i class="mdi mdi-image-outline text-muted"></i>
                                        </div>
                                      <?php endif; ?>
                                      <div>
                                        <strong><?php echo htmlspecialchars($cat['nombre_categoria']); ?></strong>
                                        <br><small class="text-muted">ID: <?php echo $cat['id_categoria']; ?></small>
                                      </div>
                                    </div>
                                  <?php else: ?>
                                    <div class="d-flex align-items-center">
                                      <?php if (!empty($cat['imagen_categoria'])): ?>
                                        <img src="<?php echo htmlspecialchars($cat['imagen_categoria']); ?>"
                                          alt="<?php echo htmlspecialchars($cat['nombre_categoria']); ?>" 
                                          class="product-image me-2">
                                      <?php else: ?>
                                        <div class="product-image d-flex align-items-center justify-content-center bg-light me-2">
                                          <i class="mdi mdi-image-outline text-muted"></i>
                                        </div>
                                      <?php endif; ?>
                                      <div>
                                        <strong><?php echo htmlspecialchars($cat['nombre_categoria']); ?></strong>
                                        <br><small class="text-muted">ID: <?php echo $cat['id_categoria']; ?></small>
                                      </div>
                                    </div>
                                  <?php endif; ?>
                                </td>
                                <td>
                                  <span class="badge badge-info">
                                    <?php echo htmlspecialchars($sub['nombre_subcategoria']); ?>
                                  </span>
                                </td>
                                <td>
                                  <span class="badge badge-success">Activa</span>
                                </td>
                                <td>
                                  <?php if ($index === 0): ?>
                                    <button class="btn btn-sm btn-outline-primary btn-editar">
                                      <i class="mdi mdi-pencil-outline"></i> Editar
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-eliminar-categoria ms-1"
                                      data-id="<?php echo $cat['id_categoria']; ?>">
                                      <i class="mdi mdi-delete-outline"></i> Eliminar
                                    </button>
                                  <?php else: ?>
                                    <small class="text-muted">Editar categoría principal</small>
                                  <?php endif; ?>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Modal Bootstrap para Editar Categoría -->
        <div class="modal fade" id="modalEditarCategoria" tabindex="-1" aria-labelledby="modalEditarCategoriaLabel"
          aria-hidden="true">
          <div class="modal-dialog modal-md">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="modalEditarCategoriaLabel">
                  <i class="mdi mdi-tag-multiple-outline me-2"></i>Gestión de Categoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <form id="formEditarCategoria">
                  <input type="hidden" name="id_categoria" id="edit_id_categoria_modal">
                  <input type="hidden" name="imagen_categoria_base64" id="imagen_categoria_base64">

                  <div class="row">
                    <div class="col-md-12 mb-3">
                      <label for="edit_nombre_categoria_modal" class="form-label">
                        Nombre de la Categoría
                      </label>
                      <input type="text" class="form-control" name="nombre_categoria" id="edit_nombre_categoria_modal"
                        placeholder="Ingresa el nombre de la categoría" required>
                    </div>
                    <div class="col-md-12 mb-3">
                      <label for="edit_imagen_categoria" class="form-label">
                        Imagen de la Categoría
                      </label>
                      <div class="image-preview-container">
                        <img id="imagen_preview" src="#" alt="Preview" 
                             class="img-preview" style="display: none;">
                        <div class="no-image-placeholder" id="no_image_preview">
                          <i class="mdi mdi-image-outline"></i>
                          <span>Sin imagen</span>
                        </div>
                      </div>
                      <input type="file" class="form-control mt-2" name="imagen_categoria" id="edit_imagen_categoria"
                        accept="image/*">
                    </div>
                  </div>
                </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  <i class="mdi mdi-close"></i> Cancelar
                </button>
                <button type="button" class="btn btn-success" id="btnGuardarCategoria">
                  <i class="mdi mdi-content-save"></i> Guardar Cambios
                </button>
              </div>
            </div>
          </div>
        </div>

      </div>
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

  <!-- SweetAlert2 para notificaciones modernas -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- DataTables -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

  <style>
    /* Solo CSS para alinear buscador a la derecha */
    .dataTables_wrapper .dataTables_filter {
      float: right;
      text-align: right;
    }

    .dataTables_wrapper .dataTables_length {
      float: left;
    }
  </style>
  <!-- Script modular para tabla de categorías -->
  <script src="modulos_JS/tabla_categorias.js"></script>

  <script>
    // Cargar el título actual y permitir guardarlo
    (function() {
      const input = document.getElementById('titulo_categorias');
      const btn = document.getElementById('guardar_titulo_categorias');
      if (!input || !btn) return;

      fetch('../../get_page_title.php?page=categorias')
        .then(r => r.json())
        .then(data => { if (data && data.success) input.value = data.title || ''; })
        .catch(() => {});

      function save() {
        const title = (input.value || '').trim();
        fetch('save_page_title.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ page: 'categorias', title })
        })
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            Swal.fire({ icon: 'success', title: 'Guardado', text: 'Título actualizado', timer: 1400, showConfirmButton: false });
          } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.error || 'No se pudo guardar' });
          }
        })
        .catch(err => Swal.fire({ icon: 'error', title: 'Error', text: err?.message || 'No se pudo guardar' }));
      }

      btn.addEventListener('click', save);
      input.addEventListener('keydown', (e) => { if (e.key === 'Enter') save(); });
    })();
  </script>

  <script>
    const categorias = <?php echo json_encode($categorias, JSON_UNESCAPED_UNICODE); ?>;
    console.log("categorias y subcategorias:", categorias);
  </script>
</body>

</html>
