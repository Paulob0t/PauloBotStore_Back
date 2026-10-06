// ===================================================================
// MÓDULO FORMULARIO DE PRODUCTO - VERSIÓN MODERNA Y MEJORADA
// ===================================================================

const ProductoFormulario = (() => {
  // Función auxiliar para convertir File a Base64
  const fileToBase64 = (file) => {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(file);
    });
  };

  // Función auxiliar para mostrar mensajes con SweetAlert2
  const mostrarMensaje = (tipo, titulo, mensaje) => {
    Swal.fire({
      icon: tipo,
      title: titulo,
      text: mensaje,
      confirmButtonColor: tipo === 'success' ? '#48bb78' : '#667eea'
    });
  };

  return {
    // Configuración
    config: {
      imagenes: {
        maxFileSize: 2 * 1024 * 1024, // 2MB
        acceptedTypes: ['image/jpeg', 'image/png', 'image/webp']
      }
    },

    // Cache de elementos DOM
    elements: {},

    // Inicialización
    init() {
      this.initElements();
      this.setupEventListeners();
      this.cargarCategoriasDesdeBackend();
      if (this.elements.productPreview) {
        this.elements.productPreview.classList.add('is-empty');
      }
    },

    // Inicializar referencias DOM
    initElements() {
      this.elements = {
        // Formulario y selectores
        form: document.getElementById('formProducto'),
        categoriaSelect: document.getElementById('categoriaProducto'),
        subcategoriaSelect: document.getElementById('subcategoriaProducto'),
        subcategoriaContainer: document.getElementById('subcategoriaContainer'),
        btnAddCategoria: document.getElementById('btnAddCategoria'),
        btnAddSubcatModal: document.getElementById('btnAddSubcatModal'),
        
        // Modales
        modalCategoria: document.getElementById('modalCategoria'),
        modalSubcat: document.getElementById('modalSubcat'),
        
        // Campos del formulario
        nombreProducto: document.getElementById('nombreProducto'),
        descripcionProducto: document.getElementById('descripcionProducto'),
        precioProducto: document.getElementById('precioProducto'),
        stockDisponible: document.getElementById('stockDisponible'),
        ubicacion: document.getElementById('ubicacion'),
        
        // Imagen
        imgInput: document.getElementById('imgInput'),
        imgPreview: document.getElementById('imgPreview'),
        imgBase64: document.getElementById('imgBase64'),
        
        // Descuento
        checkDescuento: document.getElementById('checkDescuento'),
        precioDescuento: document.getElementById('precioDescuento'),
        grupoPrecioDescuento: document.getElementById('grupoPrecioDescuento'),
        
        // Destacado
        checkDestacado: document.getElementById('destacado'),
        ordenDestacado: document.getElementById('ordenDestacado'),
        grupoOrdenDestacado: document.getElementById('grupoOrdenDestacado'),
        
        // Activo
        checkActivo: document.getElementById('activo'),
        
        // Preview del producto
        productPreview: document.getElementById('productPreview'),
        previewImg: document.getElementById('previewImg'),
        previewNombre: document.getElementById('previewNombre'),
        previewPrecio: document.getElementById('previewPrecio'),
        previewStock: document.getElementById('previewStock'),
        previewUbicacion: document.getElementById('previewUbicacion'),
        previewActivoBadge: document.getElementById('previewActivoBadge'),
        previewDestacadoBadge: document.getElementById('previewDestacadoBadge'),
        previewDescuentoBadge: document.getElementById('previewDescuentoBadge')
      };
    },

    // Cargar categorías desde el backend (sin caché del navegador)
    async cargarCategoriasDesdeBackend(idCategoriaASeleccionar = null, idSubcategoriaASeleccionar = null) {
      try {
        const response = await fetch(`obtener_categorias.php?_=${Date.now()}`, {
          cache: 'no-store',
          headers: { 'Cache-Control': 'no-cache' }
        });
        if (!response.ok) throw new Error('Error en la respuesta del servidor');
        
        const data = await response.json();
        if (data.success && data.categorias) {
          this.renderCategorias(data.categorias, idCategoriaASeleccionar, idSubcategoriaASeleccionar);
        } else {
          mostrarMensaje('error', 'Error', data.message || 'Error al cargar categorías');
        }
      } catch (error) {
        console.error('Error:', error);
        mostrarMensaje('error', 'Error', 'Error de conexión al cargar categorías');
      }
    },

    // Actualizar el select al instante con datos del servidor (sin esperar refetch)
    aplicarCategoriaEnSelect({ id_categoria, nombre, subcategorias = [] }) {
      const select = this.elements.categoriaSelect;
      const id = String(id_categoria);
      const subs = subcategorias || [];

      let option = Array.from(select.options).find(opt => opt.value === id);
      if (!option) {
        option = document.createElement('option');
        option.value = id;
        select.appendChild(option);
      }

      option.textContent = nombre;
      option.setAttribute('data-subcats', JSON.stringify(subs));

      const placeholder = select.querySelector('option[value=""]');
      if (placeholder) placeholder.selected = false;

      select.value = id;
      select.dispatchEvent(new Event('change', { bubbles: true }));

      return option;
    },

    // Renderizar categorías en el select (sin recargar página)
    renderCategorias(categorias, idCategoriaASeleccionar = null, idSubcategoriaASeleccionar = null) {
      const select = this.elements.categoriaSelect;
      const categoriaId = idCategoriaASeleccionar != null
        ? String(idCategoriaASeleccionar)
        : (select.value || '');
      
      select.innerHTML = '<option value="" disabled>Selecciona una categoría</option>';
      categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = String(cat.id);
        option.textContent = cat.nombre;
        option.setAttribute('data-subcats', JSON.stringify(cat.subcategorias || []));
        select.appendChild(option);
      });
      
      if (categoriaId) {
        select.value = categoriaId;
        if (select.value === categoriaId) {
          select.dispatchEvent(new Event('change', { bubbles: true }));
          if (idSubcategoriaASeleccionar != null) {
            this.elements.subcategoriaSelect.value = String(idSubcategoriaASeleccionar);
          }
        }
      }
    },

    // Configurar todos los event listeners
    setupEventListeners() {
      // Eventos de categoría/subcategoría
      this.elements.categoriaSelect.addEventListener('change', this.handleCategoriaChange.bind(this));
      this.elements.btnAddCategoria.addEventListener('click', this.handleShowCategoriaModal.bind(this));
      this.elements.btnAddSubcatModal.addEventListener('click', this.handleShowSubcategoriaModal.bind(this));
      
      // Eventos de modales
      document.getElementById('checkSubcategorias')?.addEventListener('change', this.handleToggleSubcategoriaInput.bind(this));
      document.getElementById('btnGuardarCategoria')?.addEventListener('click', this.handleGuardarCategoria.bind(this));
      document.getElementById('btnGuardarSubcatModal')?.addEventListener('click', this.handleGuardarSubcategoria.bind(this));
      
      // Eventos de imagen
      this.elements.imgInput.addEventListener('change', this.handleImagenPrincipal.bind(this));
      document.querySelector('.file-upload-browse')?.addEventListener('click', () => {
        this.elements.imgInput.click();
      });
      
      // Eventos del formulario
      this.elements.form.addEventListener('submit', this.handleSubmitForm.bind(this));
      this.elements.checkDescuento.addEventListener('change', this.handleToggleDescuento.bind(this));
      this.elements.checkDestacado.addEventListener('change', this.handleToggleDestacado.bind(this));
      this.elements.precioDescuento.addEventListener('input', this.handleValidacionPrecioDescuento.bind(this));
      this.elements.precioProducto.addEventListener('input', () => {
        if (this.elements.precioDescuento.value) {
          this.handleValidacionPrecioDescuento();
        }
        this.updatePreview();
      });
      
      // Validación de ubicación
      this.elements.ubicacion.addEventListener('input', this.handleValidacionUbicacion.bind(this));
      this.elements.ubicacion.addEventListener('blur', this.handleValidacionUbicacion.bind(this));
      
      // EVENTOS PARA ACTUALIZAR PREVIEW EN VIVO
      this.elements.nombreProducto.addEventListener('input', this.updatePreview.bind(this));
      this.elements.stockDisponible.addEventListener('input', this.updatePreview.bind(this));
      this.elements.ubicacion.addEventListener('input', this.updatePreview.bind(this));
      this.elements.checkActivo.addEventListener('change', this.updatePreview.bind(this));
      this.elements.checkDestacado.addEventListener('change', this.updatePreview.bind(this));
      this.elements.checkDescuento.addEventListener('change', this.updatePreview.bind(this));
      
      // Validación en tiempo real
      document.getElementById('inputNuevaCategoria')?.addEventListener('input', this.handleValidacionCategoria.bind(this));
      document.getElementById('inputSubcatModal')?.addEventListener('input', this.handleValidacionSubcategoria.bind(this));
      
      // Eventos de Enter en inputs de modal
      document.getElementById('inputNuevaCategoria')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          const btn = document.getElementById('btnGuardarCategoria');
          if (!btn.disabled) btn.click();
        }
      });
      
      document.getElementById('inputSubcatModal')?.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
          const btn = document.getElementById('btnGuardarSubcatModal');
          if (!btn.disabled) btn.click();
        }
      });

      // Modal focus events
      this.elements.modalCategoria?.addEventListener('shown.bs.modal', () => {
        document.getElementById('inputNuevaCategoria')?.focus();
      });
      
      this.elements.modalSubcat?.addEventListener('shown.bs.modal', () => {
        document.getElementById('inputSubcatModal')?.focus();
      });
    },

    // ===================================================================
    // PREVIEW DEL PRODUCTO EN VIVO
    // ===================================================================
    updatePreview() {
      const nombre = this.elements.nombreProducto.value.trim() || '—';
      const precio = parseFloat(this.elements.precioProducto.value) || 0;
      const stock = parseInt(this.elements.stockDisponible.value) || 0;
      const ubicacion = this.elements.ubicacion.value.trim() || '—';
      
      // Actualizar valores
      this.elements.previewNombre.textContent = nombre;
      this.elements.previewPrecio.textContent = precio.toFixed(2);
      this.elements.previewStock.textContent = stock;
      this.elements.previewUbicacion.textContent = ubicacion;
      
      // Actualizar badges
      this.elements.previewActivoBadge.style.display = 
        this.elements.checkActivo.checked ? 'inline-block' : 'none';
      
      this.elements.previewDestacadoBadge.style.display = 
        this.elements.checkDestacado.checked ? 'inline-block' : 'none';
      
      this.elements.previewDescuentoBadge.style.display = 
        this.elements.checkDescuento.checked ? 'inline-block' : 'none';
      
      // Mostrar preview con contenido o estado vacío
      const tieneDatos = nombre !== '—' || precio > 0;
      this.elements.productPreview.classList.toggle('is-empty', !tieneDatos);
    },

    // Event Handlers
    handleCategoriaChange(e) {
      // Usar siempre el elemento del select, no el event target (por si viene de dispatchEvent)
      const select = this.elements.categoriaSelect;
      const selectedOption = select.options[select.selectedIndex];
      
      // Verificar que hay una opción seleccionada válida
      if (!selectedOption || !selectedOption.value) {
        this.elements.subcategoriaContainer.style.display = 'none';
        this.elements.btnAddSubcatModal.style.display = 'none';
        return;
      }
      
      const subcategorias = JSON.parse(selectedOption.getAttribute('data-subcats') || '[]');

      this.elements.subcategoriaSelect.innerHTML = '<option value="">Sin subcategoría</option>';
      subcategorias.forEach(sub => {
        const option = document.createElement('option');
        option.value = sub.id;
        option.textContent = sub.nombre;
        this.elements.subcategoriaSelect.appendChild(option);
      });

      this.elements.subcategoriaContainer.style.display = subcategorias.length > 0 ? 'block' : 'none';
      this.elements.btnAddSubcatModal.style.display = 'inline-block';
    },

    handleShowCategoriaModal() {
      document.getElementById('inputNuevaCategoria').value = '';
      document.getElementById('checkSubcategorias').checked = false;
      document.getElementById('subcatInputSection').style.display = 'none';
      document.getElementById('inputNuevaSubcategoria').value = '';
      
      const modalCategoria = new bootstrap.Modal(this.elements.modalCategoria);
      modalCategoria.show();
    },

    handleShowSubcategoriaModal() {
      const selectedCat = this.elements.categoriaSelect.options[this.elements.categoriaSelect.selectedIndex];
      if (!selectedCat || !selectedCat.value) {
        mostrarMensaje('warning', 'Advertencia', 'Primero selecciona una categoría');
        return;
      }

      document.getElementById('nombreCategoriaSeleccionada').textContent = selectedCat.textContent;
      document.getElementById('inputSubcatModal').value = '';
      
      const modalSubcat = new bootstrap.Modal(this.elements.modalSubcat);
      modalSubcat.show();
    },

    handleToggleSubcategoriaInput(e) {
      document.getElementById('subcatInputSection').style.display = e.target.checked ? 'block' : 'none';
    },

    async handleGuardarCategoria() {
      const nombre = document.getElementById('inputNuevaCategoria').value.trim();
      const agregarSubcat = document.getElementById('checkSubcategorias').checked;
      const subcat = document.getElementById('inputNuevaSubcategoria').value.trim();

      if (!nombre) {
        mostrarMensaje('error', 'Error', 'Ingresa un nombre para la categoría');
        return;
      }

      const data = {
        categoria: nombre,
        subcategorias: agregarSubcat && subcat ? [subcat] : []
      };

      const btnGuardar = document.getElementById('btnGuardarCategoria');
      const originalText = btnGuardar.innerHTML;

      try {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Guardando...';

        const response = await fetch('guardar_categoria_y_subcategorias.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });

        const responseData = await response.json();
        if (responseData.success) {
          const modalCategoria = bootstrap.Modal.getInstance(this.elements.modalCategoria);
          modalCategoria?.hide();

          // Actualizar dropdown al instante y seleccionar la nueva categoría
          this.aplicarCategoriaEnSelect(responseData);

          const nuevaSub = responseData.subcategorias_nuevas?.[0];
          if (nuevaSub) {
            this.elements.subcategoriaSelect.value = String(nuevaSub.id);
          }

          // Sincronizar lista completa en segundo plano
          this.cargarCategoriasDesdeBackend(
            responseData.id_categoria,
            nuevaSub?.id ?? null
          ).catch(() => {});

          mostrarMensaje('success', '¡Éxito!', 'Categoría guardada correctamente');
        } else {
          throw new Error(responseData.message || 'Error al guardar');
        }
      } catch (error) {
        console.error('Error:', error);
        mostrarMensaje('error', 'Error', error.message);
      } finally {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = originalText;
      }
    },

    async handleGuardarSubcategoria() {
      const selectedCat = this.elements.categoriaSelect.options[this.elements.categoriaSelect.selectedIndex];
      const subcat = document.getElementById('inputSubcatModal').value.trim();

      if (!selectedCat || !selectedCat.value || !subcat) {
        mostrarMensaje('error', 'Error', 'Completa todos los campos');
        return;
      }

      const catNombre = document.getElementById('nombreCategoriaSeleccionada').textContent;
      const data = {
        categoria: catNombre,
        subcategorias: [subcat]
      };

      const btnGuardar = document.getElementById('btnGuardarSubcatModal');
      const originalText = btnGuardar.innerHTML;

      try {
        btnGuardar.disabled = true;
        btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Agregando...';

        const response = await fetch('guardar_categoria_y_subcategorias.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        });

        const responseData = await response.json();
        if (responseData.success) {
          const modalSubcat = bootstrap.Modal.getInstance(this.elements.modalSubcat);
          modalSubcat?.hide();

          const categoriaIdActual = selectedCat.value;
          const nuevaSub = responseData.subcategorias_nuevas?.[0];

          // Actualizar subcategorías de la categoría actual al instante
          const option = Array.from(this.elements.categoriaSelect.options)
            .find(opt => opt.value === categoriaIdActual);
          if (option && responseData.subcategorias) {
            option.setAttribute('data-subcats', JSON.stringify(responseData.subcategorias));
            this.elements.categoriaSelect.dispatchEvent(new Event('change', { bubbles: true }));
            if (nuevaSub) {
              this.elements.subcategoriaSelect.value = String(nuevaSub.id);
            }
          }

          this.cargarCategoriasDesdeBackend(categoriaIdActual, nuevaSub?.id ?? null).catch(() => {});

          mostrarMensaje('success', '¡Éxito!', 'Subcategoría agregada correctamente');
        } else {
          throw new Error(responseData.message || 'Error al guardar');
        }
      } catch (error) {
        console.error('Error:', error);
        mostrarMensaje('error', 'Error', error.message);
      } finally {
        btnGuardar.disabled = false;
        btnGuardar.innerHTML = originalText;
      }
    },

    handleValidacionCategoria(e) {
      const btnGuardar = document.getElementById('btnGuardarCategoria');
      const input = e.target;
      if (input.value.trim().length >= 2) {
        btnGuardar.disabled = false;
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
      } else {
        btnGuardar.disabled = true;
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
      }
    },

    handleValidacionSubcategoria(e) {
      const btnGuardar = document.getElementById('btnGuardarSubcatModal');
      const input = e.target;
      if (input.value.trim().length >= 2) {
        btnGuardar.disabled = false;
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
      } else {
        btnGuardar.disabled = true;
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
      }
    },

    handleValidacionUbicacion(e) {
      const input = e.target;
      const valor = input.value.toUpperCase();
      input.value = valor;
      
      // Patrón: Una letra (A-Z) seguida de un número (0-9)
      const patron = /^[A-Z][0-9]$/;
      
      if (valor.length === 0) {
        input.classList.remove('is-valid', 'is-invalid');
        input.setCustomValidity('');
        return;
      }
      
      if (patron.test(valor)) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        input.setCustomValidity('');
      } else {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        input.setCustomValidity('Formato inválido. Use letra + número (Ej: A1, B2)');
      }
    },

    async handleImagenPrincipal(e) {
      const file = e.target.files[0];
      const fileUploadInfo = document.querySelector('.file-upload-info');
      
      if (!file) {
        this.elements.imgPreview.style.display = 'none';
        fileUploadInfo.value = 'Ningún archivo seleccionado';
        return;
      }

      try {
        if (file.size > this.config.imagenes.maxFileSize) {
          throw new Error("La imagen no debe exceder 2MB");
        }

        const base64 = await fileToBase64(file);
        this.elements.imgBase64.value = base64;
        this.elements.imgPreview.src = base64;
        this.elements.imgPreview.style.display = 'block';
        fileUploadInfo.value = file.name;
        
        // Actualizar imagen en el preview
        this.elements.previewImg.src = base64;
        this.updatePreview();
        
      } catch (error) {
        console.error("Error al procesar imagen:", error);
        mostrarMensaje('error', 'Error', error.message);
        e.target.value = '';
        this.elements.imgPreview.style.display = 'none';
        fileUploadInfo.value = 'Ningún archivo seleccionado';
      }
    },

    async handleSubmitForm(e) {
      e.preventDefault();

      const form = e.target;
      const btnSubmit = form.querySelector('button[type="submit"]');
      const originalBtnText = btnSubmit.innerHTML;

      try {
        if (!this.elements.imgBase64.value) {
          throw new Error("La imagen principal es requerida");
        }

        // Validar ubicación
        if (this.elements.ubicacion && this.elements.ubicacion.value) {
          const patron = /^[A-Z][0-9]$/;
          if (!patron.test(this.elements.ubicacion.value.toUpperCase())) {
            throw new Error("Formato de ubicación inválido. Use letra + número (Ej: A1, B2)");
          }
        } else {
          throw new Error("La ubicación es requerida");
        }

        // Validar orden de destacado si está marcado como destacado
        if (this.elements.checkDestacado.checked) {
          const orden = parseInt(this.elements.ordenDestacado.value);
          if (!orden || orden < 1) {
            throw new Error("Debes ingresar un número de orden válido para productos destacados");
          }

          // Validar que el orden no esté ocupado
          const ordenDisponible = await this.validarOrdenDestacado(orden);
          if (!ordenDisponible) {
            throw new Error(`El número de orden ${orden} ya está ocupado. Por favor, elige otro número.`);
          }
        }

        const usaDescuento = this.elements.checkDescuento.checked;
        const precioOriginal = parseFloat(this.elements.precioProducto.value);
        const precioDescuento = parseFloat(this.elements.precioDescuento.value);

        let descuentoField = document.getElementById('descuento_calculado');
        if (descuentoField) {
          descuentoField.remove();
        }

        if (usaDescuento && precioDescuento && !isNaN(precioDescuento) && precioDescuento > 0) {
          if (precioDescuento >= precioOriginal) {
            throw new Error("El precio con descuento debe ser menor al precio original");
          }

          const porcentajeDescuento = ((precioOriginal - precioDescuento) / precioOriginal) * 100;
          
          descuentoField = document.createElement('input');
          descuentoField.type = 'hidden';
          descuentoField.name = 'descuento';
          descuentoField.id = 'descuento_calculado';
          descuentoField.value = porcentajeDescuento.toFixed(2);
          form.appendChild(descuentoField);
        }

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = `
          <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
          Guardando...
        `;

        const response = await fetch('guardar_producto.php', {
          method: 'POST',
          body: new FormData(form)
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || 'Error al guardar el producto');
        }

        await mostrarMensaje('success', '¡Éxito!', data.message);
        form.reset();
        this.limpiarFormularioCompleto();
        
      } catch (error) {
        console.error('Error:', error);
        await mostrarMensaje('error', 'Error', error.message);
      } finally {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = originalBtnText;
      }
    },

    handleToggleDescuento(e) {
      this.elements.grupoPrecioDescuento.style.display = e.target.checked ? 'block' : 'none';
      if (!e.target.checked) {
        this.elements.precioDescuento.value = '';
        const descuentoField = document.getElementById('descuento_calculado');
        if (descuentoField) {
          descuentoField.remove();
        }
        const label = document.querySelector('label[for="precioDescuento"]');
        if (label) {
          label.innerHTML = 'Precio de descuento';
        }
      }
    },

    handleToggleDestacado(e) {
      this.elements.grupoOrdenDestacado.style.display = e.target.checked ? 'block' : 'none';
      if (!e.target.checked) {
        this.elements.ordenDestacado.value = '';
        this.elements.ordenDestacado.classList.remove('is-valid', 'is-invalid');
      } else {
        // Solicitar al servidor el próximo orden disponible
        this.obtenerProximoOrdenDisponible();
      }
    },

    async obtenerProximoOrdenDisponible() {
      try {
        const response = await fetch('obtener_proximo_orden_destacado.php');
        if (!response.ok) throw new Error('Error al obtener orden');
        
        const data = await response.json();
        if (data.success && data.proximo_orden) {
          this.elements.ordenDestacado.value = data.proximo_orden;
          this.elements.ordenDestacado.placeholder = `Sugerido: ${data.proximo_orden}`;
        }
      } catch (error) {
        console.error('Error:', error);
      }
    },

    async validarOrdenDestacado(orden, idProducto = null) {
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
    },

    handleValidacionPrecioDescuento() {
      const precioOriginal = parseFloat(this.elements.precioProducto.value);
      const precioDescuento = parseFloat(this.elements.precioDescuento.value);
      const input = this.elements.precioDescuento;
      const label = document.querySelector('label[for="precioDescuento"]');
      
      if (precioOriginal && precioDescuento) {
        if (precioDescuento >= precioOriginal) {
          input.setCustomValidity('El precio con descuento debe ser menor al precio original');
          input.style.borderColor = '#dc3545';
        } else {
          input.setCustomValidity('');
          input.style.borderColor = '#28a745';
          
          const porcentaje = ((precioOriginal - precioDescuento) / precioOriginal) * 100;
          label.innerHTML = `Precio de descuento <small class="text-success">(${porcentaje.toFixed(1)}% de descuento)</small>`;
        }
      } else {
        input.setCustomValidity('');
        input.style.borderColor = '';
        label.innerHTML = 'Precio de descuento';
      }
    },

    limpiarFormularioCompleto() {
      // Limpiar campos de descuento
      const camposDescuento = document.querySelectorAll('input[name="descuento"], input[id="descuento_calculado"]');
      camposDescuento.forEach(campo => campo.remove());
      
      this.elements.checkDescuento.checked = false;
      this.elements.grupoPrecioDescuento.style.display = 'none';
      this.elements.precioDescuento.value = '';
      
      // Limpiar campos de destacado
      this.elements.checkDestacado.checked = false;
      this.elements.grupoOrdenDestacado.style.display = 'none';
      this.elements.ordenDestacado.value = '';
      
      // Restaurar label de precio descuento
      const label = document.querySelector('label[for="precioDescuento"]');
      if (label) {
        label.innerHTML = '<i class="fas fa-tag"></i> Precio con descuento';
      }

      // Limpiar imagen
      this.elements.imgPreview.style.display = 'none';
      this.elements.imgBase64.value = '';
      const fileUploadInfo = document.querySelector('.file-upload-info');
      if (fileUploadInfo) {
        fileUploadInfo.value = 'Ningún archivo seleccionado';
      }
      
      // Reset preview
      this.elements.productPreview.classList.add('is-empty');
      this.elements.previewNombre.textContent = '—';
      this.elements.previewPrecio.textContent = '0.00';
      this.elements.previewStock.textContent = '0';
      this.elements.previewUbicacion.textContent = '—';
      this.elements.previewImg.src = 'https://via.placeholder.com/150/1a1a1a/f2dc00?text=VB';
    }
  };
})();

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => ProductoFormulario.init());
