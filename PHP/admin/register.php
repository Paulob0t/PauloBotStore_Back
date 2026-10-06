<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Registro de Usuarios | PauloBot Store</title>
    
    <!-- Google Fonts & FontAwesome 6 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Vendor Styles (HTTPS CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- CSS Separado -->
    <link rel="stylesheet" href="./assets/css/register.css">
</head>
<body>

    <div class="register-card">
        <!-- Header -->
        <div class="brand-header">
            <a href="../" class="brand-logo">
                <div class="logo-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="brand-name">PauloBot <span>Store</span></div>
            </a>
            <h1 class="register-title">Crear Cuenta</h1>
            <p class="register-subtitle">Completa los datos para registrar un usuario en el sistema</p>
        </div>

        <!-- Formulario Registro -->
        <form id="formRegistro" autocomplete="off">
            <!-- Nombre Completo -->
            <div class="form-group">
                <label for="nombre" class="form-label">Nombre Completo</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Juan Pérez" required autofocus>
                </div>
            </div>

            <!-- Correo Electrónico -->
            <div class="form-group">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="usuario@paulobot.com" required>
                </div>
            </div>

            <!-- Contraseña -->
            <div class="form-group">
                <label for="contrasena" class="form-label">Contraseña</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="••••••••••••" required>
                </div>
            </div>

            <!-- Confirmar Contraseña -->
            <div class="form-group">
                <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña</label>
                <div class="input-wrapper">
                    <i class="fas fa-shield-alt input-icon"></i>
                    <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" placeholder="••••••••••••" required>
                </div>
            </div>

            <!-- Botón Submit -->
            <button type="submit" class="btn-submit" id="btnSubmit">
                <span>REGISTRAR USUARIO</span>
                <i class="fas fa-user-plus"></i>
            </button>
        </form>

        <!-- Footer -->
        <div class="register-footer">
            <a href="login.php" class="back-link">
                <i class="fas fa-sign-in-alt"></i> ¿Ya tienes cuenta? Iniciar Sesión
            </a>
        </div>
    </div>

    <!-- Scripts JS Externos & JS Separado -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./assets/js/register.js" defer></script>

</body>
</html>
