<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago Pendiente - VendingBox</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .status-card {
            background: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 500px;
            animation: slideIn 0.5s ease-out;
        }
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .status-icon {
            font-size: 5rem;
            margin-bottom: 1.5rem;
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="status-card">
        <i class="bi bi-clock-history status-icon"></i>
        <h1 class="mb-3">Pago Pendiente</h1>
        <p class="text-muted mb-4">
            Tu pago está siendo procesado. Te notificaremos cuando se complete.
        </p>
        <div class="alert alert-warning">
            <i class="bi bi-info-circle me-2"></i>
            Puede tardar unos minutos en confirmarse
        </div>
        <a href="index.php" class="btn btn-primary btn-lg mt-3">
            <i class="bi bi-house-door me-2"></i>Volver al Inicio
        </a>
    </div>
</body>
</html>
