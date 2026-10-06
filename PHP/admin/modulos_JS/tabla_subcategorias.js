// Módulo para el manejo de tabla de subcategorías
const SubcategoriaTabla = (() => {
  return {
    // Cache de elementos DOM
    elements: {},

    // Configuración
    config: {
      imagenes: {
        maxWidth: 800,
        maxHeight: 800,
        maxFileSize: 2 * 1024 * 1024, // 2MB
        quality: 0.85,
        acceptedTypes: ['image/jpeg', 'image/png', 'image/webp']
      },
      tabla: {
        pageLength: 5,
        lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]]
      }
    },

    // Inicialización
    init() {
      this.initElements();
      this.initDataTable();
      this.setupEventListeners();
    },

    // Inicializar referencias DOM
    initElements() {
      this.elements = {
        tabla: $('#productosTable'),
        btnAgregar: $('#btnAgregarSubcategoria'),
        modalAgregar: $('#modalAgregarSubcategoria'),
        modalEditar: $('#modalEditarSubcategoria'),
        formAgregar: $('#formAgregarSubcategoria'),
        formEditar: $('#formEditarSubcategoria')
      };
    },

    // Inicializar DataTable
    initDataTable() {
      this.dataTable = this.elements.tabla.DataTable({
        paging: false,
        lengthChange: false,
        info: false,
        order: [[0, "asc"]],
        language: {
          url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        responsive: true,
        dom: '<"row"<"col-sm-12"f>>rt',
        columnDefs: [
          { targets: [0], orderable: false },
          { targets: [-1], orderable: false }
        ]
      });
    },

    // Convertir archivo a Base64
    fileToBase64(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
      });
    },

    // Configurar event listeners
    setupEventListeners() {
      // Evento para mostrar modal de agregar
      this.elements.btnAgregar.on('click', () => {
        this.elements.formAgregar[0].reset();
        this.elements.modalAgregar.modal('show');
      });

      // Eventos de cierre de modales
      $('#cerrarModalAgregarSubcategoria, #cerrarModalAgregarSubcategoria2').on('click', () => {
        this.elements.modalAgregar.modal('hide');
      });

      $('#cerrarModalSubcategoria, #cerrarModalSubcategoria2').on('click', () => {
        this.elements.modalEditar.modal('hide');
      });

      // Evento para editar subcategoría
      $(document).on('click', '.btn-editar-subcategoria', this.handleEditar.bind(this));

      // Evento para eliminar subcategoría
      $(document).on('click', '.btn-eliminar-subcategoria', this.handleEliminar.bind(this));

      // Eventos de submit de formularios
      this.elements.formEditar.on('submit', this.handleSubmitEditar.bind(this));
      this.elements.formAgregar.on('submit', this.handleSubmitAgregar.bind(this));

      // Preview de imagen en ambos modales
      $('#add_imagen_subcategoria, #edit_imagen_subcategoria').on('change', this.handleImagenPreview.bind(this));
    },

    // Manejador de preview de imagen
    async handleImagenPreview(e) {
      const file = e.target.files[0];
      if (!file) return;

      try {
        if (file.size > this.config.imagenes.maxFileSize) {
          throw new Error("La imagen no debe exceder 2MB");
        }

        if (!this.config.imagenes.acceptedTypes.includes(file.type)) {
          throw new Error("Formato de imagen no válido");
        }

        const base64 = await this.fileToBase64(file);
        const previewId = e.target.id === 'add_imagen_subcategoria' ? 'add_imagen_preview' : 'edit_imagen_preview';
        const base64InputId = e.target.id === 'add_imagen_subcategoria' ? 'add_imagen_base64' : 'edit_imagen_base64';
        const noImageId = e.target.id === 'add_imagen_subcategoria' ? 'add_no_image_preview' : 'edit_no_image_preview';
        
        $(`#${previewId}`).attr('src', base64).show();
        $(`#${noImageId}`).hide();
        $(`#${base64InputId}`).val(base64);
      } catch (error) {
        Swal.fire('Error', error.message, 'error');
        e.target.value = '';
        $(`#${previewId}`).hide();
        $(`#${noImageId}`).show();
      }
    },

    // Manejador de edición
    handleEditar(e) {
      const btn = $(e.currentTarget);
      const data = {
        id: btn.data('id'),
        nombre: btn.data('nombre'),
        id_categoria: btn.data('id_categoria'),
        imagen: btn.data('imagen')
      };

      $('#edit_id_subcategoria_modal').val(data.id);
      $('#edit_nombre_subcategoria_modal').val(data.nombre);
      $('#add_id_categoria_sub').val(data.id_categoria);
      
      if (data.imagen) {
        $('#edit_imagen_preview').attr('src', data.imagen).show();
        $('#edit_no_image_preview').hide();
        $('#edit_imagen_base64').val(data.imagen);
      } else {
        $('#edit_imagen_preview').hide();
        $('#edit_no_image_preview').show();
        $('#edit_imagen_base64').val('');
      }

      this.elements.modalEditar.modal('show');
    },

    // Manejador de eliminación
    handleEliminar(e) {
      const id = $(e.currentTarget).data('id');

      Swal.fire({
        title: '¿Eliminar subcategoría?',
        text: 'Esta acción no se puede deshacer',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: '<i class="fa fa-trash"></i> Eliminar',
        cancelButtonText: '<i class="fa fa-times"></i> Cancelar',
        reverseButtons: true
      }).then(async (result) => {
        if (result.isConfirmed) {
          try {
            const response = await fetch('eliminar_subcategoria.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({ id_subcategoria: id })
            });

            const data = await response.json();
            if (data.success) {
              await Swal.fire({ title: 'Eliminada', text: 'Subcategoría eliminada correctamente', icon: 'success', confirmButtonColor: '#f2dc00' });
              location.reload();
            } else {
              throw new Error(data.message || 'No se pudo eliminar la subcategoría');
            }
          } catch (error) {
            Swal.fire('Error', error.message, 'error');
          }
        }
      });
    },

    // Manejador de submit del formulario de edición
    async handleSubmitEditar(e) {
      e.preventDefault();

      const data = {
        id_categoria: parseInt($('#add_id_categoria_sub').val()),
        nombre_categoria: $('#add_id_categoria_sub option:selected').text().trim(),
        id_subcategoria: parseInt($('#edit_id_subcategoria_modal').val()),
        nombre_subcategoria: $('#edit_nombre_subcategoria_modal').val().trim(),
        imagen_subcategoria: $('#edit_imagen_base64').val()
      };

      if (!data.id_categoria || !data.nombre_subcategoria) {
        Swal.fire('Error', 'Completa todos los campos obligatorios', 'error');
        return;
      }

      try {
        const response = await fetch('actualizar_categoria_y_subcategoria.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data)
        });

        const result = await response.json();
        this.elements.modalEditar.modal('hide');

        if (result.success) {
          await Swal.fire({ title: 'Actualizado', text: 'Subcategoría actualizada correctamente', icon: 'success', confirmButtonColor: '#f2dc00' });
          location.reload();
        } else {
          throw new Error(result.message || 'Error al actualizar la subcategoría');
        }
      } catch (error) {
        Swal.fire('Error', error.message, 'error');
      }
    },

    // Manejador de submit del formulario de agregar
    async handleSubmitAgregar(e) {
      e.preventDefault();

      const data = {
        id_categoria: parseInt($('#select_categoria_agregar').val()),
        subcategorias: [$('#add_nombre_subcategoria').val().trim()],
        imagen_subcategoria: $('#add_imagen_base64').val()
      };

      if (!data.id_categoria || !data.subcategorias[0]) {
        Swal.fire('Error', 'Completa todos los campos obligatorios', 'error');
        return;
      }

      try {
        const response = await fetch('guardar_categoria_y_subcategorias.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(data)
        });

        const result = await response.json();
        this.elements.modalAgregar.modal('hide');

        if (result.success) {
          await Swal.fire({ title: 'Éxito', text: 'Subcategoría agregada correctamente', icon: 'success', confirmButtonColor: '#f2dc00' });
          location.reload();
        } else {
          throw new Error(result.message || 'Error al agregar la subcategoría');
        }
      } catch (error) {
        Swal.fire('Error', error.message, 'error');
      }
    }
  };
})();

// Inicializar el módulo cuando el DOM esté listo
$(document).ready(() => SubcategoriaTabla.init());
