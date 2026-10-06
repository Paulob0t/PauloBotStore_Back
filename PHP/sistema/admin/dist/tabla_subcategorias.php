<?php

include "db_config_dual.php";
// Registrar errores en un archivo de log
ini_set("log_errors", 1);
ini_set("error_log", "php_errors.log");

// Obtener categorías y subcategorías agrupadas correctamente
$categorias = [];
$sql = "SELECT c.id_categoria, c.nombre_categoria, s.id_subcategoria, s.nombre_subcategoria, s.imagen_subcategoria 
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
        'subcategorias' => []
      ];
    }
    if ($row['id_subcategoria']) {
      $categorias[$id_categoria]['subcategorias'][] = [
        'id_subcategoria' => $row['id_subcategoria'],
        'nombre_subcategoria' => $row['nombre_subcategoria'],
        'imagen_subcategoria' => $row['imagen_subcategoria']
      ];
    }
  }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Tabla Subcategorias</title>
  <link rel="stylesheet" href="assets/vendors/feather/feather.css">
  <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
  <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
  <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
  <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <!-- DataTables CSS desde CDN -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="shortcut icon" href="assets/images/favicon.png" />
  <!-- Tema premium amarillo/negro -->
  <link rel="stylesheet" href="styles_css/tabla_productos.css">
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
                  <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <p class="card-title mb-0">Gestión de Subcategorías</p>
                    <div class="d-flex align-items-center gap-2" style="min-width: 320px;">
                      <input id="titulo_subcategorias" type="text" class="form-control form-control-sm" placeholder="Título para la vista de subcategorías">
                      <button id="guardar_titulo_subcategorias" type="button" class="btn btn-outline-primary btn-sm" title="Guardar título">
                        <i class="fa fa-save"></i>
                      </button>
                    </div>

                    <button class="btn btn-primary" id="btnAgregarSubcategoria">
                      <i class="fa fa-plus"></i> Agregar Subcategoría
                    </button>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-striped table-borderless" id="productosTable">
                      <thead>
                        <tr>
                          <th>Categoría</th>
                          <th>Subcategoría</th>
                          <th>Acciones</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php if (empty($categorias)): ?>
                          <tr>
                            <td colspan="3" class="text-center">No hay subcategorías registradas</td>
                          </tr>
                        <?php else: ?>
                          <?php foreach ($categorias as $cat): ?>
                            <?php foreach ($cat['subcategorias'] as $sub): ?>
                              <tr>
                                <td>
                                  <span
                                    class="badge badge-primary"><?php echo htmlspecialchars($cat['nombre_categoria']); ?></span>
                                </td>
                                <td>
                                  <div class="d-flex align-items-center">
                                    <?php if (!empty($sub['imagen_subcategoria'])): ?>
                                      <img src="<?php echo htmlspecialchars($sub['imagen_subcategoria']); ?>"
                                        alt="<?php echo htmlspecialchars($sub['nombre_subcategoria']); ?>" class="me-2 rounded"
                                        style="width: 40px; height: 40px; object-fit: cover;">
                                    <?php else: ?>
                                      <div class="me-2 rounded bg-light d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="mdi mdi-image-outline text-muted"></i>
                                      </div>
                                    <?php endif; ?>
                                    <span
                                      class="font-weight-medium"><?php echo htmlspecialchars($sub['nombre_subcategoria']); ?></span>
                                  </div>
                                </td>
                                <td>
                                  <button class="btn btn-sm btn-outline-primary btn-editar-subcategoria"
                                    data-id="<?php echo $sub['id_subcategoria']; ?>"
                                    data-nombre="<?php echo htmlspecialchars($sub['nombre_subcategoria']); ?>"
                                    data-id_categoria="<?php echo $cat['id_categoria']; ?>"
                                    data-imagen="<?php echo htmlspecialchars($sub['imagen_subcategoria'] ?? ''); ?>">
                                    <i class="fa fa-edit"></i> Editar
                                  </button>
                                  <button class="btn btn-sm btn-outline-danger btn-eliminar-subcategoria"
                                    data-id="<?php echo $sub['id_subcategoria']; ?>">
                                    <i class="fa fa-trash"></i> Eliminar
                                  </button>
                                </td>
                              </tr>
                            <?php endforeach; ?>
                          <?php endforeach; ?>
                        <?php endif; ?>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="modal fade" id="modalAgregarSubcategoria" tabindex="-1" role="dialog"
            aria-labelledby="modalAgregarSubcategoriaLabel" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="modalAgregarSubcategoriaLabel">
                    <i class="fa fa-plus text-success"></i> Agregar Subcategoría
                  </h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    id="cerrarModalAgregarSubcategoria">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <form id="formAgregarSubcategoria">
                    <div class="form-group mb-3">
                      <label for="select_categoria_agregar" class="form-label">Selecciona una Categoría</label>
                      <select class="form-control" name="id_categoria" id="select_categoria_agregar" required>
                        <option value="">-- Selecciona una categoría --</option>
                        <?php foreach ($categorias as $cat): ?>
                          <option value="<?php echo $cat['id_categoria']; ?>">
                            <?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group mb-3">
                      <label for="add_nombre_subcategoria" class="form-label">Nombre de la Subcategoría</label>
                      <input type="text" class="form-control" name="nombre_subcategoria" id="add_nombre_subcategoria"
                        placeholder="Ingresa el nombre de la subcategoría" required>
                    </div>
                    <div class="form-group mb-3">
                      <label for="add_imagen_subcategoria" class="form-label">Imagen de la Subcategoría</label>
                      <div class="image-preview-container">
                        <img id="add_imagen_preview" src="#" alt="Preview" class="img-preview" style="display: none;">
                        <div class="no-image-placeholder" id="add_no_image_preview">
                          <i class="mdi mdi-image-outline"></i>
                          <span>Sin imagen</span>
                        </div>
                      </div>
                      <input type="file" class="form-control mt-2" name="imagen_subcategoria" id="add_imagen_subcategoria" accept="image/*">
                      <input type="hidden" name="imagen_base64" id="add_imagen_base64">
                    </div>
                  </form>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal"
                    id="cerrarModalAgregarSubcategoria2">
                    <i class="fa fa-times"></i> Cancelar
                  </button>
                  <button type="submit" form="formAgregarSubcategoria" class="btn btn-success">
                    <i class="fa fa-save"></i> Guardar
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="modal fade" id="modalEditarSubcategoria" tabindex="-1" role="dialog"
            aria-labelledby="modalEditarSubcategoriaLabel" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="modalEditarSubcategoriaLabel">
                    <i class="fa fa-edit text-primary"></i> Editar Subcategoría
                  </h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                    id="cerrarModalSubcategoria">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <form id="formEditarSubcategoria">
                    <input type="hidden" name="id_subcategoria" id="edit_id_subcategoria_modal">
                    <div class="form-group mb-3">
                      <label for="add_id_categoria_sub" class="form-label">Selecciona una Categoría</label>
                      <select class="form-control" name="id_categoria" id="add_id_categoria_sub" required>
                        <option value="">-- Selecciona una categoría --</option>
                        <?php foreach ($categorias as $cat): ?>
                          <option value="<?php echo $cat['id_categoria']; ?>">
                            <?php echo htmlspecialchars($cat['nombre_categoria']); ?></option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="form-group mb-3">
                      <label for="edit_nombre_subcategoria_modal" class="form-label">Nombre de la Subcategoría</label>
                      <input type="text" class="form-control" name="nombre_subcategoria"
                        id="edit_nombre_subcategoria_modal" placeholder="Ingresa el nombre de la subcategoría" required>
                    </div>
                    <div class="form-group mb-3">
                      <label for="edit_imagen_subcategoria" class="form-label">Imagen de la Subcategoría</label>
                      <div class="image-preview-container">
                        <img id="edit_imagen_preview" src="#" alt="Preview" class="img-preview" style="display: none;">
                        <div class="no-image-placeholder" id="edit_no_image_preview">
                          <i class="mdi mdi-image-outline"></i>
                          <span>Sin imagen</span>
                        </div>
                      </div>
                      <input type="file" class="form-control mt-2" name="imagen_subcategoria" id="edit_imagen_subcategoria" accept="image/*">
                      <input type="hidden" name="imagen_base64" id="edit_imagen_base64">
                    </div>
                  </form>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal" id="cerrarModalSubcategoria2">
                    <i class="fa fa-times"></i> Cancelar
                  </button>
                  <button type="submit" form="formEditarSubcategoria" class="btn btn-success">
                    <i class="fa fa-save"></i> Actualizar
                  </button>
                </div>
              </div>
            </div>
          </div>

        </div>
      </div>


      <!-- jQuery PRIMERO -->
      <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
      <!-- Bootstrap JS para los modales -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
      <!-- DataTables JS después de jQuery -->
      <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
      <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap4.min.js"></script>
      <!-- SweetAlert2 -->
      <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
      <!-- plugins:js -->
      <!-- inject:js -->
      <script src="assets/js/off-canvas.js"></script>
      <script src="assets/js/template.js"></script>
      <script src="assets/js/settings.js"></script>
      <script src="assets/js/todolist.js"></script>
      <!-- endinject -->
      <!-- Custom js for this page-->
      <script src="assets/js/file-upload.js"></script>
      <!-- Script modular para tabla de subcategorías -->
      <script src="modulos_JS/tabla_subcategorias.js"></script>

      <script>
        // Cargar/Guardar título de subcategorías
        (function() {
          const input = document.getElementById('titulo_subcategorias');
          const btn = document.getElementById('guardar_titulo_subcategorias');
          if (!input || !btn) return;

          fetch('../../get_page_title.php?page=subcategorias')
            .then(r => r.json())
            .then(data => { if (data && data.success) input.value = data.title || ''; })
            .catch(() => {});

          function save() {
            const title = (input.value || '').trim();
            fetch('save_page_title.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ page: 'subcategorias', title })
            })
            .then(r => r.json())
            .then(res => {
              if (res.success) {
                Swal.fire({ icon: 'success', title: 'Guardado', text: title ? 'Título actualizado correctamente' : 'Título eliminado', timer: 1500, showConfirmButton: false });
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
