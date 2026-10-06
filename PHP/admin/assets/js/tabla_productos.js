$(document).ready(function() {
    // 1. Inicializar DataTable
    if ($('#tablaProductos').length) {
        $('#tablaProductos').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'desc']],
            pageLength: 10,
            responsive: true
        });
    }

    // 2. Eliminar Producto con Confirmación SweetAlert2
    $(document).on('click', '.btn-delete-product', function(e) {
        e.preventDefault();
        const productId = $(this).data('id');
        const productName = $(this).data('nombre');

        Swal.fire({
            title: '¿Eliminar Producto?',
            text: `¿Estás seguro de que deseas eliminar "${productName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#475569',
            confirmButtonText: 'Sí, Eliminar',
            cancelButtonText: 'Cancelar',
            background: '#0f172a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'eliminar_producto.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { id_producto: productId },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: '¡Eliminado!',
                                text: response.message,
                                timer: 1500,
                                showConfirmButton: false,
                                background: '#0f172a',
                                color: '#fff'
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                                background: '#0f172a',
                                color: '#fff'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de Servidor',
                            text: 'No se pudo eliminar el producto.',
                            background: '#0f172a',
                            color: '#fff'
                        });
                    }
                });
            }
        });
    });

    // 3. Previsualizar Imagen Ampliada en Modal
    $(document).on('click', '.product-thumb', function() {
        const src = $(this).attr('src');
        const title = $(this).attr('alt') || 'Imagen del producto';

        if (src) {
            Swal.fire({
                title: title,
                imageUrl: src,
                imageAlt: title,
                background: '#0f172a',
                color: '#fff',
                showCloseButton: true,
                showConfirmButton: false
            });
        }
    });
});
