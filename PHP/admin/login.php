<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Acceso Administrador | PauloBot Store</title>
    
    <!-- Google Fonts & FontAwesome 6 -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Vendor Styles (HTTPS CDN) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">

    <!-- CSS Separado -->
    <link rel="stylesheet" href="./assets/css/login.css">
</head>
<body>

    <div class="login-card">
        <!-- Logo & Header -->
        <div class="brand-header">
            <a href="../" class="brand-logo">
                <div class="logo-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="brand-name">PauloBot <span>Store</span></div>
            </a>
            <h1 class="login-title">Panel Administrador</h1>
            <p class="login-subtitle">Ingresa tus credenciales para gestionar el sistema automatizado</p>
        </div>

        <!-- Formulario Autenticación -->
        <form id="formLogin" autocomplete="off">
            <!-- Correo Electrónico -->
            <div class="form-group">
                <label for="correo" class="form-label">Correo Electrónico</label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope input-icon"></i>
                    <input type="email" class="form-control" id="correo" name="correo" placeholder="admin@paulobot.com" required autofocus>
                </div>
            </div>

            <!-- Contraseña -->
            <div class="form-group">
                <label for="contrasena" class="form-label">Contraseña</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="••••••••••••" required>
                    <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                </div>
            </div>

            <!-- Botón Submit -->
            <button type="submit" class="btn-submit" id="btnSubmit">
                <span>INICIAR SESIÓN</span>
                <i class="fas fa-arrow-right"></i>
            </button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            <a href="../" class="back-link">
                <i class="fas fa-arrow-left"></i> Volver al Portal Principal
            </a>
        </div>
    </div>

    <!-- Scripts JS Externos & JS Separado del Proyecto -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./assets/js/login.js" defer></script>

</body>
</html>
