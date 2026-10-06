document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle Mobile & Desktop
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainPanel = document.querySelector('.main-panel');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (window.innerWidth >= 992) {
                // Desktop: Alternar estado colapsado / expandido
                sidebar.classList.toggle('collapsed');
                if (mainPanel) {
                    mainPanel.classList.toggle('expanded');
                }
            } else {
                // Móvil: Alternar visibilidad con superposición
                sidebar.classList.toggle('show');
                if (overlay) {
                    overlay.classList.toggle('show');
                }
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function() {
            if (sidebar) sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // Resetear clases en cambio de tamaño de pantalla
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            if (overlay) overlay.classList.remove('show');
            if (sidebar) sidebar.classList.remove('show');
        }
    });

    // Confirmación de Logout con SweetAlert2
    const logoutBtn = document.getElementById('logoutLink');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            Swal.fire({
                title: '¿Cerrar Sesión?',
                text: '¿Estás seguro de que deseas salir del panel?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#475569',
                confirmButtonText: 'Sí, Salir',
                cancelButtonText: 'Cancelar',
                background: '#0f172a',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'cerrarSesion.php';
                }
            });
        });
    }
});
