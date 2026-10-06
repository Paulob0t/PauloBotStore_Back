<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
    header('Location: login.php');
    exit();
}

$nombre_usuario = $_SESSION['nombre_usuario'] ?? 'Administrador';
?>
<!-- Top Navbar Component -->
<nav class="navbar-custom d-flex align-items-center justify-content-between">
    <!-- Brand Logo -->
    <div class="d-flex align-items-center gap-3">
        <button class="btn text-white p-0 border-0 fs-5" id="sidebarToggle" aria-label="Abrir o cerrar menú">
            <i class="fas fa-bars"></i>
        </button>
        <a href="index.php" class="brand-wrapper">
            <div class="brand-logo-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="brand-text">PauloBot <span>Store</span></div>
        </a>
    </div>

    <!-- User Profile & Logout -->
    <div class="d-flex align-items-center gap-3">
        <div class="user-badge">
            <i class="fas fa-user-circle text-primary"></i>
            <span><?php echo htmlspecialchars($nombre_usuario); ?></span>
        </div>
        <a href="#" id="logoutLink" class="btn-logout" title="Cerrar sesión">
            <i class="fas fa-power-off"></i>
        </a>
    </div>
</nav>
