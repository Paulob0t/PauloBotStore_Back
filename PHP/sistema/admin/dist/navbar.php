<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['login']) || $_SESSION['login'] === false) {
    header('Location: login.php');
    exit();
}else{
    $nombre_usuario = $_SESSION['nombre_usuario'];
    $uid = $_SESSION['uid'];
    $tipo_usuario = $_SESSION['tipo_usuario'];

}


?>
<style>
/* Tema contraste VendingBox: negro + amarillo */
:root {
  --vb-yellow: #f2dc00;
  --vb-yellow-soft: #fff59d;
  --vb-black: #0a0a0a;
  --vb-black-soft: #171717;
}

.navbar {
  z-index: 1030;
  background: linear-gradient(90deg, var(--vb-yellow) 0%, #ffe400 50%, #f2dc00 100%);
  border-bottom: 2px solid #000;
  box-shadow: 0 6px 18px rgba(0, 0, 0, 0.25);
}

.navbar .navbar-brand-wrapper {
  background: transparent;
}

.navbar .navbar-menu-wrapper {
  background: transparent;
}

.navbar .nav-link,
.navbar .navbar-toggler,
.navbar .icon-menu,
.navbar i {
  color: #000 !important;
}

.navbar .nav-link:hover {
  opacity: 0.7;
  transition: opacity 0.3s ease;
}

</style>
<nav class="navbar col-lg-12 col-12 p-0 fixed-top d-flex flex-row">
  <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
    <a class="navbar-brand brand-logo me-5" href="index.php" style="font-size: 24px; font-weight: 900; color: #000; text-decoration: none; letter-spacing: 1px;">VendingBox</a>
    <a class="navbar-brand brand-logo-mini" href="index.php" style="font-size: 20px; font-weight: 900; color: #000; text-decoration: none;">VB</a>
  </div>
  <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
    <button class="navbar-toggler navbar-toggler align-self-center" type="button" id="vbSidebarToggle" aria-label="Abrir o cerrar menú">
      <span class="icon-menu"></span>
    </button>
    <ul class="navbar-nav navbar-nav-right">
      <li class="nav-item d-flex align-items-center" style="margin-right: 15px;">
        <span style="color: #000; font-weight: 600; font-size: 15px;">
          <i class="ti-user" style="margin-right: 5px;"></i>
          <?php echo htmlspecialchars($nombre_usuario); ?>
        </span>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="logout-link" href="#" title="Cerrar sesión" style="padding: 8px 15px;">
          <i class="ti-power-off" style="font-size: 20px;"></i>
        </a>
      </li>
    </ul>
    <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" id="vbSidebarToggleMobile" aria-label="Abrir menú">
      <span class="icon-menu"></span>
    </button>
  </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  var logoutLink = document.getElementById('logout-link');
  if (logoutLink) {
    logoutLink.addEventListener('click', function(e) {
      e.preventDefault();
      Swal.fire({
        title: '¿Estás seguro?',
        text: '¿Deseas cerrar sesión?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, cerrar sesión',
        cancelButtonText: 'Cancelar'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'cerrarSesion.php';
        }
      });
    });
  }
});
</script>
