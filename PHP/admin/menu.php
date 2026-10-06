<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
    header('Location: login.php');
    exit();
}

$currentScript = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar Navigation Component -->
<aside class="sidebar-custom" id="sidebar">
    <div class="nav-section-title">Principal</div>
    <a href="index.php" class="nav-link-custom <?php echo ($currentScript === 'index.php') ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i>
        <span>Dashboard</span>
    </a>

    <div class="nav-section-title">Productos</div>
    <a href="formulario_producto.php" class="nav-link-custom <?php echo ($currentScript === 'formulario_producto.php') ? 'active' : ''; ?>">
        <i class="fas fa-plus-circle"></i>
        <span>Agregar Producto</span>
    </a>
    <a href="tabla_productos.php" class="nav-link-custom <?php echo ($currentScript === 'tabla_productos.php') ? 'active' : ''; ?>">
        <i class="fas fa-box-open"></i>
        <span>Consulta Productos</span>
    </a>

    <div class="nav-section-title">Categorías</div>
    <a href="tabla_categorias.php" class="nav-link-custom <?php echo ($currentScript === 'tabla_categorias.php') ? 'active' : ''; ?>">
        <i class="fas fa-tags"></i>
        <span>Categorías</span>
    </a>
    <a href="tabla_subcategorias.php" class="nav-link-custom <?php echo ($currentScript === 'tabla_subcategorias.php') ? 'active' : ''; ?>">
        <i class="fas fa-folder-tree"></i>
        <span>Subcategorías</span>
    </a>

    <div class="nav-section-title">Finanzas</div>
    <a href="movimientos.php" class="nav-link-custom <?php echo ($currentScript === 'movimientos.php') ? 'active' : ''; ?>">
        <i class="fas fa-exchange-alt"></i>
        <span>Movimientos</span>
    </a>
    <a href="cortes_caja.php" class="nav-link-custom <?php echo ($currentScript === 'cortes_caja.php') ? 'active' : ''; ?>">
        <i class="fas fa-cash-register"></i>
        <span>Cortes de Caja</span>
    </a>

    <div class="nav-section-title">Configuración</div>
    <a href="configurar_empresa.php" class="nav-link-custom <?php echo ($currentScript === 'configurar_empresa.php') ? 'active' : ''; ?>">
        <i class="fas fa-building"></i>
        <span>Configurar Empresa</span>
    </a>
    <a href="register.php" class="nav-link-custom <?php echo ($currentScript === 'register.php') ? 'active' : ''; ?>">
        <i class="fas fa-user-plus"></i>
        <span>Agregar Usuarios</span>
    </a>
</aside>
