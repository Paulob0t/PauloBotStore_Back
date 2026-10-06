document.addEventListener('DOMContentLoaded', function() {
    // 1. Manejo del Select Dinámico de Categorías y Subcategorías
    const catSelect = document.getElementById('categoriaProducto');
    const subcatSelect = document.getElementById('subcategoriaProducto');

    if (catSelect && subcatSelect) {
        catSelect.addEventListener('change', function() {
            const selectedOption = catSelect.options[catSelect.selectedIndex];
            const subcatsJson = selectedOption.getAttribute('data-subcategorias');

            subcatSelect.innerHTML = '<option value="">-- Seleccionar Subcategoría (Opcional) --</option>';

            if (subcatsJson) {
                try {
                    const subcats = JSON.parse(subcatsJson);
                    subcats.forEach(function(sub) {
                        const opt = document.createElement('option');
                        opt.value = sub.id;
                        opt.textContent = sub.nombre;
                        subcatSelect.appendChild(opt);
                    });
                } catch (e) {
                    console.error('Error parseando subcategorías:', e);
                }
            }
        });
    }

    // Compresión ligera de imágenes en canvas (Máximo 800px de ancho/alto)
    function compressImage(file, maxWidth, maxHeight, quality, callback) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = new Image();
            img.onload = function() {
                let width = img.width;
                let height = img.height;

                if (width > maxWidth || height > maxHeight) {
                    if (width > height) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    } else {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                const dataUrl = canvas.toDataURL('image/jpeg', quality);
                callback(dataUrl);
            };
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    }

    // 2. Previsualización y Compresión de Imágenes
    function setupImagePreview(inputId, previewId, base64HiddenId) {
        const input = document.getElementById(inputId);
        const preview = document.getElementById(previewId);
        const hiddenInput = document.getElementById(base64HiddenId);

        if (input && preview) {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    compressImage(file, 800, 800, 0.85, function(compressedBase64) {
                        preview.src = compressedBase64;
                        preview.classList.remove('d-none');
                        if (hiddenInput) {
                            hiddenInput.value = compressedBase64;
                        }
                    });
                }
            });
        }
    }

    setupImagePreview('imgPrincipalFile', 'imgPrincipalPreview', 'imagen_principal');
    setupImagePreview('imgSec1File', 'imgSec1Preview', 'imagen_secundaria_1');
    setupImagePreview('imgSec2File', 'imgSec2Preview', 'imagen_secundaria_2');
    setupImagePreview('imgSec3File', 'imgSec3Preview', 'imagen_secundaria_3');

    // 3. Toggle de Productos Destacados y Orden
    const destacadoSwitch = document.getElementById('destacado');
    const ordenGroup = document.getElementById('groupOrdenDestacado');

    if (destacadoSwitch && ordenGroup) {
        destacadoSwitch.addEventListener('change', function() {
            if (destacadoSwitch.checked) {
                ordenGroup.classList.remove('d-none');
            } else {
                ordenGroup.classList.add('d-none');
            }
        });
    }

    // 4. Envío del Formulario vía AJAX
    const form = document.getElementById('formProducto');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const btnSubmit = document.getElementById('btnSubmitProducto');
            const originalHtml = btnSubmit ? btnSubmit.innerHTML : 'GUARDAR PRODUCTO';

            const formData = new FormData(form);

            if (btnSubmit) {
                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Guardando...';
            }

            $.ajax({
                url: 'guardar_producto.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: '¡Éxito!',
                            text: response.message,
                            background: '#0f172a',
                            color: '#fff',
                            confirmButtonColor: '#6366f1'
                        }).then(() => {
                            window.location.href = 'tabla_productos.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                            background: '#0f172a',
                            color: '#fff',
                            confirmButtonColor: '#6366f1'
                        });
                        if (btnSubmit) {
                            btnSubmit.disabled = false;
                            btnSubmit.innerHTML = originalHtml;
                        }
                    }
                },
                error: function(xhr) {
                    let errMsg = 'Ocurrió un error al guardar el producto.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errMsg = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Servidor',
                        text: errMsg,
                        background: '#0f172a',
                        color: '#fff',
                        confirmButtonColor: '#6366f1'
                    });
                    if (btnSubmit) {
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = originalHtml;
                    }
                }
            });
        });
    }
});
