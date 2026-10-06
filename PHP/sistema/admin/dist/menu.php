<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
    header('Location: login.php');
    exit();
} else {
    $nombre_usuario = $_SESSION['nombre_usuario'];
    $uid = $_SESSION['uid'];
    $tipo_usuario = $_SESSION['tipo_usuario'];
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="styles_css/admin-layout.css">

<div class="vb-sidebar-overlay" id="vbSidebarOverlay" aria-hidden="true"></div>

<nav class="sidebar sidebar-offcanvas vb-sidebar" id="sidebar" aria-label="Menú principal">
  <div class="vb-sidebar-brand">
    <span class="vb-sidebar-brand-text">VendingBox</span>
    <span class="vb-sidebar-brand-short">VB</span>
    <button type="button" class="vb-sidebar-close d-lg-none" id="vbSidebarClose" aria-label="Cerrar menú">
      <i class="fas fa-times"></i>
    </button>
  </div>

  <div class="vb-sidebar-scroll">
    <ul class="nav">
      <li class="nav-item">
        <a class="nav-link" href="index.php" title="Dashboard">
          <i class="fas fa-chart-line"></i>
          <span class="menu-title">Dashboard</span>
        </a>
      </li>

      <li class="vb-nav-section" aria-hidden="true"><span>Productos</span></li>

      <li class="nav-item">
        <a class="nav-link" href="formulario_producto.php" title="Agregar Producto">
          <i class="fas fa-cart-plus"></i>
          <span class="menu-title">Agregar Producto</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="tabla_productos.php" title="Consulta Producto">
          <i class="fas fa-box-open"></i>
          <span class="menu-title">Consulta Producto</span>
        </a>
      </li>

      <li class="menu-divider" aria-hidden="true"></li>
      <li class="vb-nav-section" aria-hidden="true"><span>Categorías</span></li>

      <li class="nav-item">
        <a class="nav-link" href="tabla_categorias.php" title="Lista Categorías">
          <i class="fas fa-tags"></i>
          <span class="menu-title">Lista Categorías</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="tabla_subcategorias.php" title="Lista Subcategorías">
          <i class="fas fa-tag"></i>
          <span class="menu-title">Lista Subcategorías</span>
        </a>
      </li>

      <li class="menu-divider" aria-hidden="true"></li>
      <li class="vb-nav-section" aria-hidden="true"><span>Finanzas</span></li>

      <li class="nav-item">
        <a class="nav-link" href="movimientos.php" title="Movimientos">
          <i class="fas fa-exchange-alt"></i>
          <span class="menu-title">Movimientos</span>
        </a>
      </li>

      <li class="nav-item">
        <a class="nav-link" href="cortes_caja.php" title="Cortes de Caja">
          <i class="fas fa-cash-register"></i>
          <span class="menu-title">Cortes de Caja</span>
        </a>
      </li>

      <li class="menu-divider" aria-hidden="true"></li>
      <li class="vb-nav-section" aria-hidden="true"><span>Configuración</span></li>

      <li class="nav-item">
        <a class="nav-link" href="configurar_empresa.php" title="Configurar Empresa">
          <i class="fas fa-building"></i>
          <span class="menu-title">Configurar Empresa</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="vb-sidebar-footer">
    <strong><?php echo htmlspecialchars($nombre_usuario); ?></strong>
    Administrador
  </div>
</nav>

<script src="js/admin-layout.js"></script>
