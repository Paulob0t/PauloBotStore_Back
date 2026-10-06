$(document).ready(function() {
    // Alternar Visibilidad de la Contraseña
    $('#togglePassword').on('click', function() {
        const passwordInput = $('#contrasena');
        const type = passwordInput.attr('type') === 'password' ? 'text' : 'password';
        passwordInput.attr('type', type);
        $(this).toggleClass('fa-eye fa-eye-slash');
    });

    // Enviar Formulario Vía AJAX
    $('#formLogin').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#btnSubmit');
        const originalHtml = $btn.html();
        
        const correo = $('#correo').val().trim();
        const contrasena = $('#contrasena').val();

        if (!correo || !contrasena) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos requeridos',
                text: 'Por favor, llena todos los campos.',
                background: '#0f172a',
                color: '#fff',
                confirmButtonColor: '#6366f1'
            });
            return;
        }

        // Estado de Carga
        $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Autenticando...');

        $.ajax({
            url: 'iniciarSesion.php',
            type: 'POST',
            dataType: 'json',
            data: {
                correo: correo,
                contrasena: contrasena
            },
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Acceso Concedido!',
                        text: response.message,
                        timer: 1500,
                        showConfirmButton: false,
                        background: '#0f172a',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Autenticación',
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
                    text: 'Ocurrió un error al procesar la solicitud. Por favor intenta más tarde.',
                    background: '#0f172a',
                    color: '#fff',
                    confirmButtonColor: '#6366f1'
                });
                $btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
