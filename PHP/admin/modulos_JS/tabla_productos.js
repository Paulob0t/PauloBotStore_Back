/**
 * Módulo para la gestión de la tabla de productos
 * Archivo: tabla_productos.js
 * Fecha: 15-10-2025
 */

const TablaProductos = (() => {
  // Configuración
  const config = {
    maxFileSize: 2 * 1024 * 1024, // 2MB
    patronUbicacion: /^[A-Z][0-9]$/
  };

  // Cache de elementos DOM
  let elements = {};

  /**
   * Inicialización del módulo
   */
  const init = () => {
    console.log('Inicializando módulo de tabla de productos...');
    initElements();
    initDataTable();
    setupEventListeners();
    setupImagePreviews();
    setupUbicacionValidation();
  };

  /**
   * Inicializar referencias a elementos DOM
   */
  const initElements = () => {
    elements = {
      productosTable: $('#productosTable'),
      modalEditar: $('#modalEditarProducto'),
      formEditar: $('#formEditarProducto'),
      categoriaSelect: $('#edit_id_categoria'),
      subcategoriaSelect: $('#edit_id_subcategoria'),
      ubicacionInput: $('#edit_ubicacion')
    };
  };

  /**
   * Inicializar DataTable
   */
  const initDataTable = () => {
    elements.productosTable.DataTable({
      paging: false,
      lengthChange: false,
      info: false,
      language: {
        url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
      },
      responsive: true,
      dom: '<"row"<"col-sm-12"f>>rt',
      columnDefs: [
        { targets: [0], orderable: false },
        { targets: [7], orderable: false }
      ],
      initComplete: function () {
        const filter = $('.dataTables_filter');
        const slot = $('#tpSearchSlot');
        if (filter.length && slot.length) {
          filter.appendTo(slot);
          const input = filter.find('input');
          input.attr('placeholder', 'Buscar producto, SKU, categoría…');
          input.addClass('form-control form-control-sm');
        }
      }
    });
  };

  /**
   * Configurar event listeners
   */
  const setupEventListeners = () => {
    // Cambiar subcategorías al cambiar categoría
    elements.categoriaSelect.on('change', handleCategoriaChange);

    // Abrir modal de edición
    $(document).on('click', '.btn-editar', handleEditarClick);

    // Submit del formulario de edición
    elements.formEditar.on('submit', handleFormSubmit);
    
    // Toggle del checkbox destacado
    $('#edit_destacado').on('change', handleToggleDestacadoEdit);
  };

  /**
   * Configurar previsualizaciones de imágenes
   */
  const setupImagePreviews = () => {
    setupImagePreview('#edit_imagen_principal', '#edit_imagen_principal_actual');
    setupImagePreview('#edit_imagen_secundaria_1', '#edit_imagen_secundaria_1_actual');
    setupImagePreview('#edit_imagen_secundaria_2', '#edit_imagen_secundaria_2_actual');
    setupImagePreview('#edit_imagen_secundaria_3', '#edit_imagen_secundaria_3_actual');
  };

  /**
   * Configurar validación de ubicación
   */
  const setupUbicacionValidation = () => {
    elements.ubicacionInput.on('input blur', handleUbicacionValidation);
  };

  /**
   * Configurar previsualización para un campo de imagen
   */
  const setupImagePreview = (inputId, previewId) => {
    $(inputId).on('change', function (e) {
      const file = this.files[0];
      if (!file) return;

      // Validar que sea imagen
      if (!file.type.match('image.*')) {
        Swal.fire('Error', 'Por favor selecciona un archivo de imagen (JPEG, PNG, etc.)', 'error');
        $(this).val('');
        $(previewId).attr('src', '').hide();
        return;
      }

      // Validar tamaño
      if (file.size > config.maxFileSize) {
        Swal.fire('Error', 'La imagen es demasiado grande (máximo 2MB)', 'error');
        $(this).val('');
        $(previewId).attr('src', '').hide();
        return;
      }

      // Previsualizar
      const reader = new FileReader();
      reader.onload = function (evt) {
        $(previewId).attr('src', evt.target.result).show();
        $(previewId).siblings('.no-image-placeholder').hide();
      };
      reader.readAsDataURL(file);
    });
  };

  /**
   * Handler: Cambio de categoría
   */
  const handleCategoriaChange = function () {
    const categoriaId = $(this).val();
    elements.subcategoriaSelect.find('option').hide();
    elements.subcategoriaSelect.find('option[value=""]').show();
    elements.subcategoriaSelect.find(`option[data-categoria="${categoriaId}"]`).show();
    elements.subcategoriaSelect.val('');
  };

  /**
   * Handler: Toggle destacado en modal de edición
   */
  const handleToggleDestacadoEdit = function() {
    const isChecked = $(this).is(':checked');
    const grupoOrden = $('#edit_grupoOrdenDestacado');
    const inputOrden = $('#edit_orden_destacado');
    
    if (isChecked) {
      grupoOrden.show();
      // Si no tiene valor, obtener próximo orden disponible
      if (!inputOrden.val()) {
        obtenerProximoOrdenDisponible(inputOrden);
      }
    } else {
      grupoOrden.hide();
      inputOrden.val('').removeClass('is-valid is-invalid');
    }
  };

  /**
   * Obtener próximo orden disponible
   */
  const obtenerProximoOrdenDisponible = async (inputElement) => {
    try {
      const response = await fetch('obtener_proximo_orden_destacado.php');
      if (!response.ok) throw new Error('Error al obtener orden');
      
      const data = await response.json();
      if (data.success && data.proximo_orden) {
        inputElement.val(data.proximo_orden);
        inputElement.attr('placeholder', `Sugerido: ${data.proximo_orden}`);
      }
    } catch (error) {
      console.error('Error:', error);
    }
  };

  /**
   * Validar orden destacado
   */
  const validarOrdenDestacado = async (orden, idProducto = null) => {
    try {
      const params = new URLSearchParams({ orden });
      if (idProducto) params.append('id_producto', idProducto);
      
      const response = await fetch(`validar_orden_destacado.php?${params}`);
      if (!response.ok) throw new Error('Error en validación');
      
      const data = await response.json();
      return data.disponible;
    } catch (error) {
      console.error('Error:', error);
      return false;
    }
  };

  /**
   * Handler: Validación de ubicación en tiempo real
   */
  const handleUbicacionValidation = function () {
    const input = $(this);
    const valor = input.val().toUpperCase();
    input.val(valor);

    if (valor.length === 0) {
      input.removeClass('is-valid is-invalid');
      input.css('border-color', '');
      return;
    }

    if (config.patronUbicacion.test(valor)) {
      input.removeClass('is-invalid');
      input.addClass('is-valid');
      input.css('border-color', '#28a745');
    } else {
      input.removeClass('is-valid');
      input.addClass('is-invalid');
      input.css('border-color', '#dc3545');
    }
  };

  /**
   * Handler: Click en botón editar
   */
  const handleEditarClick = function () {
    const producto = $(this).closest('tr').data('producto');
    console.log('Producto seleccionado:', producto);

    cargarDatosEnModal(producto);
    mostrarModal();
  };

  /**
   * Cargar datos del producto en el modal
   */
  const cargarDatosEnModal = (producto) => {
    // Campos de texto
    $('#edit_id_producto').val(producto.id_producto || '');
    $('#edit_nombre_producto').val(producto.nombre_producto || '');
    $('#edit_id_categoria').val(producto.id_categoria || '');
    $('#edit_precio').val(producto.precio || '');
    $('#edit_descuento').val(producto.descuento || '');
    $('#edit_sku').val(producto.sku || '');
    $('#edit_stock').val(producto.stock || '');
    $('#edit_ubicacion').val(producto.ubicacion || '');
    // La descripción se carga via AJAX junto con las imágenes
    $('#edit_descripcion').val('');

    // Checkboxes
    $('#edit_activo').prop('checked', producto.activo == 1);
    $('#edit_destacado').prop('checked', producto.destacado == 1);
    
    // Orden destacado
    $('#edit_orden_destacado').val(producto.orden_destacado || '');
    if (producto.destacado == 1) {
      $('#edit_grupoOrdenDestacado').show();
    } else {
      $('#edit_grupoOrdenDestacado').hide();
    }

    // Mostrar placeholders mientras se cargan las imágenes
    mostrarImagenActual('edit_imagen_principal_actual', 'edit_no_image_principal', null);
    mostrarImagenActual('edit_imagen_secundaria_1_actual', 'edit_no_image_secundaria_1', null);
    mostrarImagenActual('edit_imagen_secundaria_2_actual', 'edit_no_image_secundaria_2', null);
    mostrarImagenActual('edit_imagen_secundaria_3_actual', 'edit_no_image_secundaria_3', null);

    // Cargar imágenes mediante AJAX (para evitar problemas de memoria)
    if (producto.id_producto) {
      cargarImagenesProducto(producto.id_producto);
    }

    // Filtrar subcategorías
    elements.subcategoriaSelect.find('option').hide();
    elements.subcategoriaSelect.find('option[value=""]').show();
    elements.subcategoriaSelect.find(`option[data-categoria="${producto.id_categoria}"]`).show();
    elements.subcategoriaSelect.val(producto.id_subcategoria || '');
  };

  /**
   * Cargar imágenes y descripción de un producto mediante AJAX (optimización de memoria)
   */
  const cargarImagenesProducto = (idProducto) => {
    $.ajax({
      url: 'get_producto_imagenes.php',
      method: 'GET',
      data: { id_producto: idProducto },
      dataType: 'json',
      success: function(response) {
        if (response.success && response.imagenes) {
          // Cargar descripción
          $('#edit_descripcion').val(response.imagenes.descripcion || '');
          // Cargar imágenes
          mostrarImagenActual('edit_imagen_principal_actual', 'edit_no_image_principal', response.imagenes.imagen_principal);
          mostrarImagenActual('edit_imagen_secundaria_1_actual', 'edit_no_image_secundaria_1', response.imagenes.imagen_secundaria_1);
          mostrarImagenActual('edit_imagen_secundaria_2_actual', 'edit_no_image_secundaria_2', response.imagenes.imagen_secundaria_2);
          mostrarImagenActual('edit_imagen_secundaria_3_actual', 'edit_no_image_secundaria_3', response.imagenes.imagen_secundaria_3);
        }
      },
      error: function(xhr, status, error) {
        console.error('Error al cargar imágenes y descripción:', error);
        // Mantener placeholders en caso de error
      }
    });
  };

  /**
   * Mostrar imagen actual en el modal
   */
  const mostrarImagenActual = (imgId, placeholderId, src) => {
    const img = $(`#${imgId}`);
    const placeholder = $(`#${placeholderId}`);

    if (src && src !== '') {
      img.attr('src', src).show();
      placeholder.hide();
    } else {
      img.hide();
      placeholder.show();
    }
  };

  /**
   * Mostrar modal Bootstrap
   */
  const mostrarModal = () => {
    const modal = new bootstrap.Modal(document.getElementById('modalEditarProducto'));
    modal.show();
  };

  /**
   * Handler: Submit del formulario de edición
   */
  const handleFormSubmit = (e) => {
    e.preventDefault();

    console.log('Iniciando proceso de actualización...');

    // Validar ubicación antes de enviar
    const ubicacion = elements.ubicacionInput.val().toUpperCase();
    if (!ubicacion || !config.patronUbicacion.test(ubicacion)) {
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'La ubicación debe tener el formato: Letra + Número (Ej: A1, B2, C3)'
      });
      return;
    }
    
    // Validar orden destacado si está marcado
    const destacado = $('#edit_destacado').is(':checked');
    if (destacado) {
      const orden = parseInt($('#edit_orden_destacado').val());
      if (!orden || orden < 1) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'Debes ingresar un número de orden válido para productos destacados'
        });
        return;
      }
    }

    console.log('Preparando datos para enviar...');

    // Procesar imágenes y enviar
    procesarImagenesYEnviar();
  };

  /**
   * Procesar imágenes y enviar datos al servidor
   */
  const procesarImagenesYEnviar = () => {
    Promise.all([
      fileToBase64('edit_imagen_principal'),
      fileToBase64('edit_imagen_secundaria_1'),
      fileToBase64('edit_imagen_secundaria_2'),
      fileToBase64('edit_imagen_secundaria_3')
    ]).then(([img_principal, img_secundaria_1, img_secundaria_2, img_secundaria_3]) => {
      const data = prepararDatos(img_principal, img_secundaria_1, img_secundaria_2, img_secundaria_3);
      enviarDatosAlServidor(data);
    }).catch((err) => {
      console.error('Error en el procesamiento de imágenes:', err);
      Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error al procesar imágenes'
      });
    });
  };

  /**
   * Convertir archivo a Base64
   */
  const fileToBase64 = (inputId) => {
    return new Promise(resolve => {
      const fileInput = document.getElementById(inputId);
      if (fileInput && fileInput.files && fileInput.files[0]) {
        console.log(`Procesando nueva imagen para ${inputId}...`);
        const reader = new FileReader();
        reader.onload = function (evt) {
          console.log(`Imagen ${inputId} convertida a Base64`);
          resolve(evt.target.result);
        };
        reader.readAsDataURL(fileInput.files[0]);
      } else {
        const img = document.getElementById(inputId + '_actual');
        console.log(`Usando imagen existente para ${inputId} o dejando vacío`);
        resolve(img && img.src && img.src.startsWith('data:') ? img.src : '');
      }
    });
  };

  /**
   * Preparar datos para envío
   */
  const prepararDatos = (img_principal, img_secundaria_1, img_secundaria_2, img_secundaria_3) => {
    const destacado = $('#edit_destacado').is(':checked');
    const data = {
      id_producto: $('#edit_id_producto').val(),
      nombre_producto: $('#edit_nombre_producto').val(),
      descripcion: $('#edit_descripcion').val(),
      precio: $('#edit_precio').val(),
      stock: $('#edit_stock').val(),
      sku: $('#edit_sku').val(),
      ubicacion: $('#edit_ubicacion').val().toUpperCase(),
      id_categoria: $('#edit_id_categoria').val(),
      id_subcategoria: $('#edit_id_subcategoria').val(),
      imagen_principal: img_principal,
      imagen_secundaria_1: img_secundaria_1,
      imagen_secundaria_2: img_secundaria_2,
      imagen_secundaria_3: img_secundaria_3,
      descuento: $('#edit_descuento').val(),
      activo: $('#edit_activo').is(':checked') ? 1 : 0,
      destacado: destacado ? 1 : 0
    };
    
    // Agregar orden destacado solo si está marcado como destacado
    if (destacado) {
      data.orden_destacado = $('#edit_orden_destacado').val();
    }
    
    return data;
  };

  /**
   * Enviar datos al servidor vía AJAX
   */
  const enviarDatosAlServidor = async (data) => {
    console.log('Datos preparados para enviar:', data);
    
    // Validar orden destacado antes de enviar
    if (data.destacado && data.orden_destacado) {
      const ordenDisponible = await validarOrdenDestacado(data.orden_destacado, data.id_producto);
      if (!ordenDisponible) {
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: `El número de orden ${data.orden_destacado} ya está ocupado. Por favor, elige otro número.`
        });
        return;
      }
    }
    
    console.log('Enviando solicitud AJAX...');

    // Mostrar mensaje de carga
    const loadingAlert = Swal.fire({
      title: 'Actualizando producto',
      html: 'Por favor espera...',
      allowOutsideClick: false,
      didOpen: () => {
        Swal.showLoading();
      }
    });

    $.ajax({
      url: 'guardar_producto.php',
      method: 'POST',
      data: data,
      dataType: 'json',
      success: function (resp) {
        handleSuccessResponse(resp, loadingAlert);
      },
      error: function (xhr) {
        handleErrorResponse(xhr, loadingAlert);
      }
    });
  };

  /**
   * Handler: Respuesta exitosa del servidor
   */
  const handleSuccessResponse = (resp, loadingAlert) => {
    console.log('Respuesta del servidor:', resp);
    console.log('Producto actualizado correctamente');

    loadingAlert.close();

    // Cerrar modal Bootstrap
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEditarProducto'));
    modal.hide();

    setTimeout(() => {
      Swal.fire({
        position: 'center',
        icon: 'success',
        title: '¡Éxito!',
        text: resp.message || 'Producto actualizado correctamente',
        showConfirmButton: false,
        timer: 1500
      }).then(() => {
        console.log('Recargando página...');
        location.reload();
      });
    }, 250);
  };

  /**
   * Handler: Error en la respuesta del servidor
   */
  const handleErrorResponse = (xhr, loadingAlert) => {
    console.error('Error en la solicitud AJAX:', xhr);
    
    let msg = 'Error al guardar';
    try {
      const resp = JSON.parse(xhr.responseText);
      msg = resp.message || msg;
      console.error('Mensaje de error:', msg);
    } catch (e) {
      msg = xhr.responseText || msg;
      console.error('Error al parsear respuesta:', e);
    }

    loadingAlert.close();

    Swal.fire({
      position: 'center',
      icon: 'error',
      title: 'Error',
      text: msg
    });
  };

  // Exponer solo el método de inicialización
  return {
    init
  };
})();

// Inicializar cuando el DOM esté listo
$(document).ready(() => {
  TablaProductos.init();
  
  // Log de productos (si existe la variable global)
  if (typeof productos !== 'undefined') {
    console.log("Productos:", productos);
  }
});
