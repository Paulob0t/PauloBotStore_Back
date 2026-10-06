// Módulo para el manejo de tabla de categorías
const CategoriaTabla = (() => {
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
        btnAgregarCategoria: $('#btnAgregarCategoria'),
        modalEditar: $('#modalEditarCategoria'),
        formEditar: $('#formEditarCategoria'),
        btnGuardar: $('#btnGuardarCategoria')
      };
    },

    // Inicializar DataTable
    initDataTable() {
      this.dataTable = this.elements.tabla.DataTable({
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
      // Evento editar categoría
      this.elements.tabla.on('click', '.btn-editar', this.handleEditar.bind(this));
      
      // Evento eliminar categoría
      this.elements.tabla.on('click', '.btn-eliminar-categoria', this.handleEliminar.bind(this));
      
      // Evento guardar cambios
      this.elements.btnGuardar.on('click', this.handleGuardar.bind(this));
      
      // Evento agregar nueva categoría
      this.elements.btnAgregarCategoria.on('click', this.handleMostrarModalAgregar.bind(this));

      // Preview de imagen en el modal de edición
      $('#edit_imagen_categoria').on('change', this.handleImagenPreview.bind(this));
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
        $('#imagen_preview').attr('src', base64).show();
        $('#no_image_preview').hide();
        $('#imagen_categoria_base64').val(base64);
      } catch (error) {
        Swal.fire('Error', error.message, 'error');
        e.target.value = '';
        $('#imagen_preview').hide();
        $('#no_image_preview').show();
      }
    },

    // Manejador de edición
    handleEditar(e) {
      const data = $(e.currentTarget).closest('tr').data('producto');
      if (typeof data === 'string') {
        try {
          const parsedData = JSON.parse(data);
          this.populateEditModal(parsedData);
        } catch (error) {
          console.error('Error parsing data:', error);
        }
      } else {
        this.populateEditModal(data);
      }
    },

    // Poblar modal de edición
    populateEditModal(data) {
      $('#edit_id_categoria_modal').val(data.id_categoria || '');
      $('#edit_nombre_categoria_modal').val(data.nombre_categoria || '');
      if (data.imagen_categoria) {
        $('#imagen_preview').attr('src', data.imagen_categoria).show();
        $('#no_image_preview').hide();
      } else {
        $('#imagen_preview').hide();
        $('#no_image_preview').show();
      }
      
      const modalBootstrap = new bootstrap.Modal(this.elements.modalEditar);
      modalBootstrap.show();
    },

    // Manejador para mostrar modal de agregar
    handleMostrarModalAgregar() {
      Swal.fire({
        title: '<i class="mdi mdi-tag-plus-outline"></i> Nueva Categoría',
        html: `
          <div class="form-group">
            <label class="form-label">Nombre de la categoría</label>
            <input type="text" id="nombre_categoria" class="swal2-input" placeholder="Ej: Bebidas, Comidas, etc.">
          </div>
          <div class="form-group mt-3">
            <label class="form-label">Imagen de la categoría</label>
            <input type="file" id="imagen_categoria" class="swal2-file" accept="image/*">
          </div>
          <div class="mt-3">
            <img id="preview_imagen" style="max-width: 200px; display: none; border: 2px solid rgba(242, 220, 0, 0.3); border-radius: 8px;" class="img-fluid">
          </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#f2dc00',
        cancelButtonColor: '#95a5a6',
        confirmButtonText: '<i class="mdi mdi-plus"></i> Agregar',
        cancelButtonText: '<i class="mdi mdi-close"></i> Cancelar',
        didOpen: () => {
          $('#imagen_categoria').on('change', async (e) => {
            const file = e.target.files[0];
            if (file) {
              try {
                const base64 = await this.fileToBase64(file);
                $('#preview_imagen').attr('src', base64).show();
              } catch (error) {
                console.error('Error:', error);
              }
            }
          });
        },
        preConfirm: async () => {
          const nombre = $('#nombre_categoria').val();
          const imagen = $('#imagen_categoria')[0].files[0];

          if (!nombre) {
            Swal.showValidationMessage('El nombre es obligatorio');
            return false;
          }

          if (!imagen) {
            Swal.showValidationMessage('La imagen es obligatoria');
            return false;
          }

          try {
            const base64 = await this.fileToBase64(imagen);
            return {
              nombre: nombre,
              imagen: base64
            };
          } catch (error) {
            Swal.showValidationMessage('Error al procesar la imagen');
            return false;
          }
        }
      }).then((result) => {
        if (result.isConfirmed) {
          this.guardarNuevaCategoria(result.value);
        }
      });
    },

    // Guardar nueva categoría
    async guardarNuevaCategoria(data) {
      try {
        const response = await fetch('guardar_categoria_y_subcategorias.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            categoria: data.nombre,
            imagen_categoria: data.imagen,
            subcategorias: []
          })
        });

        const result = await response.json();
        if (result.success) {
          Swal.fire({
            title: 'Éxito',
            text: 'Categoría agregada correctamente',
            icon: 'success',
            confirmButtonColor: '#f2dc00'
          }).then(() => {
            location.reload();
          });
        } else {
          throw new Error(result.message || 'Error al guardar la categoría');
        }
      } catch (error) {
        Swal.fire('Error', error.message, 'error');
      }
    },

    // Manejador de eliminación
    handleEliminar(e) {
      const id_categoria = $(e.currentTarget).data('id');
      
      Swal.fire({
        title: '¿Eliminar categoría?',
        text: 'Se eliminará la categoría y todas sus subcategorías. Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#e74c3c',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="mdi mdi-delete"></i> Sí, eliminar',
        cancelButtonText: '<i class="mdi mdi-close"></i> Cancelar',
        reverseButtons: true
      }).then((result) => {
        if (result.isConfirmed) {
          this.eliminarCategoria(id_categoria);
        }
      });
    },

    // Eliminar categoría
    async eliminarCategoria(id_categoria) {
      try {
        const response = await fetch('eliminar_categoria.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ id_categoria: id_categoria })
        });

        const result = await response.json();
        if (result.success) {
          Swal.fire({
            title: 'Eliminado',
            text: 'Categoría eliminada correctamente',
            icon: 'success',
            confirmButtonColor: '#f2dc00'
          }).then(() => {
            location.reload();
          });
        } else {
          throw new Error(result.message || 'Error al eliminar la categoría');
        }
      } catch (error) {
        Swal.fire('Error', error.message, 'error');
      }
    },

    // Manejador de guardado
    async handleGuardar() {
      const id_categoria = $('#edit_id_categoria_modal').val();
      const nombre_categoria = $('#edit_nombre_categoria_modal').val();
      const imagen_base64 = $('#imagen_categoria_base64').val();

      if (!nombre_categoria.trim()) {
        Swal.fire('Error', 'El nombre de la categoría es obligatorio', 'error');
        return;
      }

      try {
        const response = await fetch('actualizar_categoria_y_subcategoria.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            id_categoria: id_categoria,
            nombre_categoria: nombre_categoria,
            imagen_categoria: imagen_base64
          })
        });

        const result = await response.json();
        if (result.success) {
          const modalBootstrap = bootstrap.Modal.getInstance(this.elements.modalEditar);
          modalBootstrap.hide();
          
          Swal.fire({
            title: 'Actualizado',
            text: 'Categoría actualizada correctamente',
            icon: 'success',
            confirmButtonColor: '#f2dc00'
          }).then(() => {
            location.reload();
          });
        } else {
          throw new Error(result.message || 'Error al actualizar la categoría');
        }
      } catch (error) {
        Swal.fire('Error', error.message, 'error');
      }
    }
  };
})();

// Inicializar el módulo cuando el DOM esté listo
$(document).ready(() => CategoriaTabla.init());
