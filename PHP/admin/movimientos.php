<?php 


// Incluir conexión a la base de datos
include 'db_config_dual.php';

// Consultar las ventas
$sql = "SELECT 
    id_comanda,
    folio,
    fecha_venta,
    subtotal,
    iva,
    descuento_global,
    total,
    metodo_pago,
    estatus,
    tipo_pago,
    tipo_tarjeta,
    notas,
    id_usuario
FROM ventas_comanda 
ORDER BY fecha_venta DESC";

$resultado = mysqli_query($conn, $sql);

if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conn));
}

$ventas = [];
while ($fila = mysqli_fetch_assoc($resultado)) {
    $ventas[] = $fila;
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Consulta Movimientos</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/vendors/mdi/css/materialdesignicons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    
    <link rel="stylesheet" href="assets/vendors/ti-icons/css/themify-icons.css">
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="assets/images/favicon.png" />
    <!-- Tema premium amarillo/negro -->
    <link rel="stylesheet" href="styles_css/tabla_productos.css">
  </head>
  <body>
    <div class="container-scroller">
      <!-- Navbar -->
      <?php include 'navbar.php'; ?>
      
      <!-- Page Body Wrapper -->
      <div class="container-fluid page-body-wrapper">
        <!-- Sidebar -->
        <?php include 'menu.php'; ?>
        
        <!-- Main Panel -->
        <div class="main-panel">
          <div class="content-wrapper">
            
            <!-- Resumen de Ventas -->
            <div class="row">
              <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <p class="card-title">Total Ventas</p>
                      <i class="fa fa-shopping-cart icon-lg text-primary"></i>
                    </div>
                    <p class="font-weight-500"><?php echo count($ventas); ?></p>
                  </div>
                </div>
              </div>
              <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <p class="card-title">Ingresos Totales</p>
                      <i class="fa fa-dollar icon-lg text-success"></i>
                    </div>
                    <p class="font-weight-500">$<?php 
                      $totalIngresos = array_sum(array_column($ventas, 'total'));
                      echo number_format($totalIngresos, 2); 
                    ?></p>
                  </div>
                </div>
              </div>
              <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <p class="card-title">Ventas Hoy</p>
                      <i class="fa fa-calendar icon-lg text-warning"></i>
                    </div>
                    <p class="font-weight-500"><?php 
                      $ventasHoy = 0;
                      $fechaHoy = date('Y-m-d');
                      foreach ($ventas as $venta) {
                        if (date('Y-m-d', strtotime($venta['fecha_venta'])) == $fechaHoy) {
                          $ventasHoy++;
                        }
                      }
                      echo $ventasHoy;
                    ?></p>
                  </div>
                </div>
              </div>
              <div class="col-md-3 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between">
                      <p class="card-title">Promedio por Venta</p>
                      <i class="fa fa-bar-chart icon-lg text-info"></i>
                    </div>
                    <p class="font-weight-500">$<?php 
                      $promedio = count($ventas) > 0 ? $totalIngresos / count($ventas) : 0;
                      echo number_format($promedio, 2); 
                    ?></p>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Tabla de Ventas -->
            <div class="row">
              <div class="col-md-12 grid-margin stretch-card">
                <div class="card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <p class="card-title mb-0">Historial de Ventas</p>
                      <small class="text-muted"><?php echo count($ventas); ?> ventas registradas</small>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-striped table-borderless" id="productosTable">
                        <thead>
                          <tr>
                            <th>Folio</th>
                            <th>Fecha/Hora</th>
                            <th>Notas</th>
                            <th>Total</th>
                            <th>Método de Pago</th>
                            <th>Acciones</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php if (empty($ventas)): ?>
                            <tr>
                              <td colspan="6" class="text-center">No hay ventas registradas</td>
                            </tr>
                          <?php else: ?>
                            <?php foreach ($ventas as $venta): ?>
                              <tr>
                                <td class="font-weight-bold"><?php echo htmlspecialchars($venta['folio']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($venta['fecha_venta'])); ?></td>
                                <td><?php echo htmlspecialchars($venta['notas'] ?? 'Sin notas'); ?></td>
                                <td class="font-weight-bold text-success">$<?php echo number_format($venta['total'], 2); ?></td>
                                <td>
                                  <span class="badge badge-<?php 
                                    echo $venta['tipo_pago'] == 1 ? 'primary' : 'warning'; 
                                  ?>">
                                    <?php 
                                      if ($venta['tipo_pago'] == 1) {
                                        echo $venta['tipo_tarjeta'] == 1 ? 'Débito' : 'Crédito';
                                      } else {
                                        echo 'Efectivo';
                                      }
                                    ?>
                                  </span>
                                </td>
                                <td>
                                  <button class="btn btn-sm btn-outline-primary" onclick="verDetalle(<?php echo $venta['id_comanda']; ?>)">
                                    <i class="fa fa-eye"></i> Ver
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
            
            <!-- Mini Tablas Adicionales - Todas en una fila -->
            <div class="row mt-4">
                <!-- Total de Ventas por Usuario -->
              <div class="col-md-4 grid-margin stretch-card">
                <div class="card auto-height-card">
                  <div class="card-body mini-table-container">
                    <h6 class="card-title mb-3">Total de Ventas por Usuario</h6>
                    <div class="table-responsive">
                      <table class="table table-sm table-borderless">
                        <thead>
                          <tr>
                            <th>Usuario</th>
                            <th class="text-right">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $ventasPorUsuario = [];
                          foreach ($ventas as $venta) {
                            $usuario = $venta['id_usuario'] == 1 ? 'Admin' : 'Usuario ' . $venta['id_usuario'];
                            
                            if (!isset($ventasPorUsuario[$usuario])) {
                              $ventasPorUsuario[$usuario] = ['cantidad' => 0, 'total' => 0];
                            }
                            $ventasPorUsuario[$usuario]['cantidad']++;
                            $ventasPorUsuario[$usuario]['total'] += $venta['total'];
                          }
                          
                          foreach ($ventasPorUsuario as $usuario => $datos):
                          ?>
                            <tr>
                              <td>
                                <span class="badge badge-success"><?php echo $usuario; ?></span>
                              </td>
                              <td class="text-right font-weight-bold">$<?php echo number_format($datos['total'], 2); ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              
              <!-- Ventas Productos/Servicios -->
              <div class="col-md-4 grid-margin stretch-card">
                <div class="card auto-height-card">
                  <div class="card-body mini-table-container">
                    <h6 class="card-title mb-3">Ventas Productos/Servicios</h6>
                    <div class="table-responsive">
                      <table class="table table-sm table-borderless">
                        <thead>
                          <tr>
                            <th>Tipo</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          // Calcular total de productos vendidos
                          $totalProductos = 0;
                          $totalServicios = 0;
                          $montoProductos = $totalIngresos; 
                          $montoServicios = 0;
                          
                          foreach ($ventas as $venta) {
                            // Aquí podrías hacer una consulta para contar productos, por ahora uso el número de ventas
                            $totalProductos += 1; // Cada venta cuenta como al menos 1 producto
                          }
                          
                          // Calcular totales generales para productos/servicios
                          $totalCantidadPS = $totalProductos + $totalServicios;
                          $totalMontoPS = $montoProductos + $montoServicios;
                          ?>
                          <tr>
                            <td>
                              <span class="badge badge-success">Productos</span>
                            </td>
                            <td class="text-center"><?php echo $totalProductos; ?></td>
                            <td class="text-right font-weight-bold">$<?php echo number_format($montoProductos, 2); ?></td>
                          </tr>
                          <tr>
                            <td>
                              <span class="badge badge-info">Servicios</span>
                            </td>
                            <td class="text-center"><?php echo $totalServicios; ?></td>
                            <td class="text-right font-weight-bold">$<?php echo number_format($montoServicios, 2); ?></td>
                          </tr>
                          
                          <tr class="summary-total-row">
                            <td>
                              <span class="badge badge-success"><strong>TOTAL</strong></span>
                            </td>
                            <td class="text-center font-weight-bold"><?php echo $totalCantidadPS; ?></td>
                            <td class="text-right font-weight-bold text-success" style="font-size: 1.1em;"><strong>$<?php echo number_format($totalMontoPS, 2); ?></strong></td>
                          </tr>
                          
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Ventas por Método de Pago -->
              <div class="col-md-4 grid-margin stretch-card">
                <div class="card auto-height-card">
                  <div class="card-body mini-table-container">
                    <h6 class="card-title mb-3">Ventas por Método de Pago</h6>
                    <div class="table-responsive">
                      <table class="table table-sm table-borderless">
                        <thead>
                          <tr>
                            <th>Método</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Total</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $ventasPorMetodo = ['Efectivo' => ['cantidad' => 0, 'total' => 0], 'Tarjeta' => ['cantidad' => 0, 'total' => 0]];
                          
                          foreach ($ventas as $venta) {
                            $metodo = $venta['tipo_pago'] == 1 ? 'Tarjeta' : 'Efectivo';
                            $ventasPorMetodo[$metodo]['cantidad']++;
                            $ventasPorMetodo[$metodo]['total'] += $venta['total'];
                          }
                          // Calcular totales generales
                          $totalCantidad = 0;
                          $totalMonto = 0;
                          
                          foreach ($ventasPorMetodo as $metodo => $datos):
                            $totalCantidad += $datos['cantidad'];
                            $totalMonto += $datos['total'];
                          ?>
                            <tr>
                              <td>
                                <span class="badge badge-<?php 
                                  echo $metodo == 'Efectivo' ? 'warning' : 'primary'; 
                                ?>"><?php echo $metodo; ?></span>
                              </td>
                              <td class="text-center"><?php echo $datos['cantidad']; ?></td>
                              <td class="text-right font-weight-bold">$<?php echo number_format($datos['total'], 2); ?></td>
                            </tr>
                          <?php endforeach; ?>
                          
                          <tr class="summary-total-row">
                            <td>
                              <span class="badge badge-success"><strong>TOTAL</strong></span>
                            </td>
                            <td class="text-center font-weight-bold"><?php echo $totalCantidad; ?></td>
                            <td class="text-right font-weight-bold text-success" style="font-size: 1.1em;"><strong>$<?php echo number_format($totalMonto, 2); ?></strong></td>
                          </tr>
                          
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            
          </div>
          <!-- content-wrapper ends -->
          
          <!-- Modal para mostrar detalle de productos -->
          <div class="modal fade" id="detalleVentaModal" tabindex="-1" role="dialog" aria-labelledby="detalleVentaModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="detalleVentaModalLabel">
                    <i class="fa fa-shopping-cart text-primary"></i> 
                    Detalle de Productos - Folio: <span id="folioModal"></span>
                  </h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="cerrarModal()">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
                <div class="modal-body">
                  <div id="loadingDetalle" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                      <span class="sr-only">Cargando...</span>
                    </div>
                    <p class="mt-2">Cargando productos...</p>
                  </div>
                  
                  <div id="tablaProductos" style="display: none;">
                    <div class="table-responsive">
                      <table class="table table-striped table-hover">
                        <thead class="table-dark">
                          <tr>
                            <th>Producto</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-right">Precio Unit.</th>
                            <th class="text-right">Descuento</th>
                            <th class="text-right">Subtotal</th>
                            <th class="text-right">IVA</th>
                            <th class="text-right">Total</th>
                          </tr>
                        </thead>
                        <tbody id="productosDetalle">
                          <!-- Los productos se cargarán aquí via AJAX -->
                        </tbody>
                        <tfoot class="table-light">
                          <tr>
                            <td colspan="6" class="text-right font-weight-bold"></td>
                            <td class="text-right font-weight-bold text-success" id="totalGeneral">
                              <span style="margin-right: 8px; color: #333;"><strong>TOTAL GENERAL:</strong></span>$0.00
                            </td>
                          </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>
                  
                  <div id="errorDetalle" style="display: none;" class="alert alert-danger">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Error:</strong> No se pudieron cargar los productos de esta venta.
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="cerrarModal()">
                    <i class="fa fa-times"></i> Cerrar
                  </button>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Footer -->
          <?php include 'footer.php'; ?>
          
        </div>
        <!-- main-panel ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    
    <!-- plugins:js -->
    <script src="assets/vendors/js/vendor.bundle.base.js"></script>
    <!-- endinject -->
    
    <!-- DataTables CSS y JS desde CDN como alternativa -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Bootstrap JS para el modal -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Verificar que jQuery esté disponible antes de Bootstrap -->
    <script>
      if (typeof jQuery === 'undefined') {
        console.error('jQuery no está cargado - necesario para Bootstrap modals');
      } else {
        console.log('jQuery cargado correctamente:', jQuery.fn.jquery);
      }
    </script>
    
    <!-- Plugin js for this page -->
    <script src="assets/vendors/chart.js/chart.umd.js"></script>
    <!-- Comentando los archivos locales que pueden estar corruptos
    <script src="assets/vendors/datatables.net/jquery.dataTables.js"></script>
    <script src="assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js"></script>
    <script src="assets/js/dataTables.select.min.js"></script>
    -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="assets/js/off-canvas.js"></script>
    <script src="assets/js/template.js"></script>
    <script src="assets/js/settings.js"></script>
    <script src="assets/js/todolist.js"></script>
    <!-- endinject -->
    <!-- Custom js for this page-->
    <script src="assets/js/jquery.cookie.js" type="text/javascript"></script>
    <script src="assets/js/dashboard.js"></script>
    <!-- End custom js for this page-->
    
    <script>
      // Desactivar alertas molestas de DataTables
      $.fn.dataTable.ext.errMode = 'none';
      
      $(document).ready(function() {
        console.log('DOM ready, verificando jQuery y DataTables...');
        console.log('jQuery version:', $.fn.jquery);
        console.log('DataTable disponible:', typeof $.fn.DataTable);
        
        // Verificar si DataTable está disponible
        if (typeof $.fn.DataTable === 'undefined') {
          console.error('DataTables no está cargado correctamente');
          return;
        }
        
        console.log('Número de filas en la tabla:', $('#productosTable tbody tr').length);
        
        // Esperar un poco más para asegurar que todo esté cargado
        setTimeout(function() {
          try {
            // Destruir cualquier instancia previa de DataTable
            if ($.fn.DataTable.isDataTable('#productosTable')) {
              $('#productosTable').DataTable().destroy();
            }
            
            // Inicializar DataTable
            var table = $('#productosTable').DataTable({
              "paging": false,
              "lengthChange": false,
              "info": false,
              "order": [[ 1, "desc" ]], // Ordenar por fecha descendente
              "searching": true,
              "dom": '<"row"<"col-sm-12"f>>rt',
              "columnDefs": [
                { "orderable": false, "targets": [5] } // Columna Acciones no ordenable
              ],
              "language": {
                "search": "Buscar:",
                "zeroRecords": "No se encontraron registros",
                "emptyTable": "No hay datos disponibles en la tabla"
              },
              "initComplete": function(settings, json) {
                console.log('DataTable inicializada correctamente');
                console.log('Total de registros:', this.api().data().length);
              }
            });
            
            console.log('DataTable configurada exitosamente:', table);
            
          } catch (error) {
            console.error('Error al inicializar DataTable:', error);
          }
        }, 100);
      });
      
      function verDetalle(idComanda) {
        // Mostrar el modal
        $('#detalleVentaModal').modal('show');
        
        // Mostrar loading y ocultar contenido
        $('#loadingDetalle').show();
        $('#tablaProductos').hide();
        $('#errorDetalle').hide();
        $('#productosDetalle').empty();
        $('#folioModal').text('...');
        $('#totalGeneral').html('<span style="margin-right: 1px; color: #333;"><strong>TOTAL GENERAL: </strong></span><span class="text-success">$0.00</span>');
        
        // Realizar petición AJAX para obtener los productos
        $.ajax({
          url: 'obtener_detalle_venta.php',
          type: 'POST',
          data: {
            id_comanda: idComanda
          },
          dataType: 'json',
          success: function(response) {
            $('#loadingDetalle').hide();
            
            if (response.error) {
              $('#errorDetalle').show().find('strong').text('Error: ' + response.error);
              return;
            }
            
            if (response.success && response.productos.length > 0) {
              // Actualizar información del modal
              $('#folioModal').text(response.info_venta.folio);
              $('#totalGeneral').html('<span style="margin-right: 1px; color: #333;"><strong>TOTAL GENERAL: </strong></span><span class="text-success">$' + response.total_general + '</span>');
              
              // Construir tabla de productos
              let html = '';
              response.productos.forEach(function(producto) {
                html += '<tr>';
                html += '<td>';
                html += '<strong>' + escapeHtml(producto.nombre_producto) + '</strong>';
                if (producto.sku) {
                  html += '<br><small class="text-muted">SKU: ' + escapeHtml(producto.sku) + '</small>';
                }
                if (producto.notas_producto && producto.notas_producto.trim() !== '') {
                  html += '<br><small class="text-info"><i class="fa fa-tag"></i> ' + escapeHtml(producto.notas_producto) + '</small>';
                }
                html += '</td>';
                html += '<td class="text-center"><span class="badge badge-primary">' + producto.cantidad + '</span></td>';
                html += '<td class="text-right">$' + producto.precio_unitario + '</td>';
                html += '<td class="text-right">$' + producto.descuento_unitario + '</td>';
                html += '<td class="text-right">$' + producto.subtotal + '</td>';
                html += '<td class="text-right">$' + producto.iva_unitario + '</td>';
                html += '<td class="text-right font-weight-bold text-success">$' + producto.total + '</td>';
                html += '</tr>';
              });
              
              $('#productosDetalle').html(html);
              $('#tablaProductos').show();
              
            } else {
              $('#errorDetalle').show().find('strong').text('Error: No se encontraron productos para esta venta');
            }
          },
          error: function(xhr, status, error) {
            $('#loadingDetalle').hide();
            $('#errorDetalle').show().find('strong').text('Error de conexión: ' + error);
            console.error('Error AJAX:', xhr.responseText);
          }
        });
      }
      
      function formatearPrecio(precio) {
        return parseFloat(precio).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
      }
      
      function escapeHtml(text) {
        var map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
      }
      
      function cerrarModal() {
        $('#detalleVentaModal').modal('hide');
      }
      
      // Asegurar que el modal se pueda cerrar con la X también
      $(document).on('click', '[data-dismiss="modal"]', function() {
        $('#detalleVentaModal').modal('hide');
      });
      
      // Cerrar modal al presionar Escape
      $(document).keyup(function(e) {
        if (e.keyCode === 27) { // Escape key
          $('#detalleVentaModal').modal('hide');
        }
      });
      

    </script>
  </body>
</html>
