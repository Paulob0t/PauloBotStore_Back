$(document).ready(function() {
    $('#formRegistro').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#btnSubmit');
        const originalHtml = $btn.html();

        const nombre = $('#nombre').val().trim();
        const correo = $('#correo').val().trim();
        const contrasena = $('#contrasena').val();
        const confirmar = $('#confirmar_contrasena').val();

        if (!nombre || !correo || !contrasena || !confirmar) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor completa todos los campos del formulario.',
                background: '#0f172a',
                color: '#fff',
                confirmButtonColor: '#6366f1'
            });
            return;
        }

        if (contrasena !== confirmar) {
            Swal.fire({
                icon: 'error',
                title: 'Las contraseñas no coinciden',
                text: 'Asegúrate de escribir la misma contraseña en ambos campos.',
                background: '#0f172a',
                color: '#fff',
                confirmButtonColor: '#6366f1'
            });
            return;
        }

        if (contrasena.length < 6) {
            Swal.fire({
                icon: 'warning',
                title: 'Contraseña muy corta',
                text: 'La contraseña debe incluir al menos 6 caracteres.',
                background: '#0f172a',
                color: '#fff',
                confirmButtonColor: '#6366f1'
            });
            return;
        }

        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Registrando...');

        $.ajax({
            url: 'registrarUsuario.php',
            type: 'POST',
            dataType: 'json',
            data: {
                nombre: nombre,
                correo: correo,
                contrasena: contrasena,
                confirmar_contrasena: confirmar
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Registro Exitoso!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false,
                        background: '#0f172a',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Registro',
                        text: response.message,
                        background: '#0f172a',
                        color: '#fff',
                        confirmButtonColor: '#6366f1'
                    });
                    $btn.prop('disabled', false).html(originalHtml);
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error de Servidor',
                    text: 'Ocurrió un error al procesar la solicitud.',
                    background: '#0f172a',
                    color: '#fff',
                    confirmButtonColor: '#6366f1'
                });
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
