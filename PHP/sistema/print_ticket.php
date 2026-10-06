<?php
date_default_timezone_set('America/Mexico_City');
$fecha = date('d/m/Y H:i:s');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Auto Print</title>
    <style>
        body { font-family: monospace; font-size: 12px; }
        .ticket { width: 260px; }
        .line { border-top: 1px dashed #000; margin: 4px 0; }
    </style>
    <script>
        window.onload = function () {
            window.print();
            setTimeout(() => window.close(), 500);
        }
    </script>
</head>
<body>
<div class="ticket">
    <div style="text-align:center;font-weight:bold;">VENDING BOX</div>
    <div style="text-align:center;">AUTO TEST</div>
    <div class="line"></div>

    <p>Fecha/Hora: <?php echo $fecha; ?></p>
    <p>TEST de impresión automática</p>

    <div class="line"></div>
    <p style="text-align:center;">*** OK ***</p>
</div>
</body>
</html>
