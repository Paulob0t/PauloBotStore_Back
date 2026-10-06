<!DOCTYPE html>
<html lang="es">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Skydash Admin</title>
    <!-- plugins:css -->
    <link rel="stylesheet" href="./assets/vendors/feather/feather.css">
    <link rel="stylesheet" href="./assets/vendors/ti-icons/css/themify-icons.css">
    <link rel="stylesheet" href="./assets/vendors/css/vendor.bundle.base.css">
    <link rel="stylesheet" href="./assets/vendors/font-awesome/css/font-awesome.min.css">
    <link rel="stylesheet" href="./assets/vendors/mdi/css/materialdesignicons.min.css">
    <!-- endinject -->
    <!-- Plugin css for this page -->
    <!-- End plugin css for this page -->
    <!-- inject:css -->
    <link rel="stylesheet" href="./assets/css/style.css">
    <!-- endinject -->
    <link rel="shortcut icon" href="./assets/images/favicon.png" />
  </head>
  <body>
      <style>
        .btn {
            background-color: #FFD93D !important;
            color:black !important;
        }
        
   

      </style>
    <div class="container-scroller">
      <div class="container-fluid page-body-wrapper full-page-wrapper">
        <div class="content-wrapper d-flex align-items-center auth px-0">
          <div class="row w-100 mx-0">
            <div class="col-lg-4 mx-auto">
              <div class="auth-form-light text-left py-5 px-4 px-sm-5" style="border-radius: 20px; ">
                <div class="brand-logo">
                  <img src="../../images/logo.png" alt="logo">
                </div>
                <h4>Iniciar sesión</h4>
                <h6 class="font-weight-light">Por favor, ingresa tus credenciales para acceder</h6>
                <form class="pt-3" id="formLogin">
                  <div class="form-group">
                    <input type="email" class="form-control form-control-lg" id="correo" name="correo" placeholder="Correo electrónico">
                  </div>
                  <div class="form-group">
                    <input type="password" class="form-control form-control-lg" id="contrasena" name="contrasena" placeholder="Contraseña">
                  </div>
                  <div class="mt-3 d-grid gap-2">
                    <button type="submit" class="btn btn-block btn-primary btn-lg font-weight-medium auth-form-btn">INICIAR SESIÓN</button>
                  </div>
                  <div class="text-center mt-4 font-weight-light"> ¿No tienes una cuenta? <a href="register.php" class="text-primary">Regístrate</a>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
        <!-- content-wrapper ends -->
      </div>
      <!-- page-body-wrapper ends -->
    </div>
    <!-- container-scroller -->
    <!-- plugins:js -->
    <script src="./assets/vendors/js/vendor.bundle.base.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- endinject -->
    <!-- Plugin js for this page -->
    <!-- End plugin js for this page -->
    <!-- inject:js -->
    <script src="./assets/js/off-canvas.js"></script>
    <script src="./assets/js/template.js"></script>
    <script src="./assets/js/settings.js"></script>
    <script src="./assets/js/todolist.js"></script>
    <!-- endinject -->
    <script>
$(document).ready(function() {
  $('#formLogin').on('submit', function(e) {
    e.preventDefault();
    var correo = $('#correo').val();
    var contrasena = $('#contrasena').val();
    $.ajax({
      url: 'iniciarSesion.php',
      type: 'POST',
      dataType: 'json',
      data: {
        correo: correo,
        contrasena: contrasena
      },
      success: function(response) {
        if(response.success) {
          Swal.fire({
            icon: 'success',
            title: '¡Bienvenido!',
            text: response.message
          }).then(() => {
            // Redirigir a dashboard o página principal si es necesario
            window.location.href = 'index.php';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: response.message
          });
        }
      },
      error: function(xhr, status, error) {
        let msg = 'Error al iniciar sesión.';
        if(xhr.responseText) {
          msg += '\nStatus: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText;
        }
        Swal.fire({
          icon: 'error',
          title: 'Error',
          html: '<pre style="text-align:left">' + msg + '</pre>'
        });
      }
    });
  });
});
</script>
  </body>
</html>
