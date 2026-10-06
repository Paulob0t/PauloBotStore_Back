<?php
session_start();

if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    header("Location: login.php");
    exit;
}

require_once 'db_config_dual.php';
require_once 'CorteCaja.class.php';

$id_corte = intval($_GET['id'] ?? 0);

if ($id_corte <= 0) {
    die('ID de corte inválido');
}

$corteCaja = new CorteCaja($conn);

// Obtener información del corte
$sql_corte = "SELECT c.*, u.nombre as nombre_usuario 
             FROM cortes c
             LEFT JOIN usuarios u ON c.id_usuario = u.id
             WHERE c.id = ?";
$stmt = $conn->prepare($sql_corte);
$stmt->bind_param("i", $id_corte);
$stmt->execute();
$corte = $stmt->get_result()->fetch_assoc();

if (!$corte) {
    die('Corte no encontrado');
}

// Obtener movimientos
$movimientos = $corteCaja->getMovimientosCorte($id_corte);
$totales = $corteCaja->calcularTotalesCorte($id_corte);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corte de Caja #<?php echo $corte['id']; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            font-size: 12px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .info-section {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .summary-table th,
        .summary-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        
        .summary-table th {
            background-color: #f0f0f0;
        }
        
        .movements-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }
        
        .movements-table th,
        .movements-table td {
            border: 1px solid #000;
            padding: 5px;
        }
        
        .movements-table th {
            background-color: #f0f0f0;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        
        .signature-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-around;
        }
        
        .signature-box {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }
        
        @media print {
            body {
                margin: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 14px; cursor: pointer;">
            Imprimir
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; font-size: 14px; cursor: pointer; margin-left: 10px;">
            Cerrar
        </button>
    </div>
    
    <div class="header">
        <h1>VENDIGBOX</h1>
        <h2>Corte de Caja #<?php echo $corte['id']; ?></h2>
        <p>Tipo: <?php echo strtoupper($corte['tipo_movimiento']); ?> DE JORNADA</p>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span><?php echo date('d/m/Y', strtotime($corte['fecha'])); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Hora:</span>
            <span><?php echo date('H:i:s', strtotime($corte['hora'])); ?></span>
        </div>
        <div class="info-row">
            <span class="info-label">Usuario:</span>
            <span><?php echo htmlspecialchars($corte['nombre_usuario'] ?? 'Sistema'); ?></span>
        </div>
        <?php if ($corte['notas']): ?>
        <div class="info-row">
            <span class="info-label">Notas:</span>
            <span><?php echo htmlspecialchars($corte['notas']); ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <h3>Resumen Financiero</h3>
    <table class="summary-table">
        <tr>
            <th>Concepto</th>
            <th class="text-right">Monto</th>
        </tr>
        <tr>
            <td>Monto Inicial</td>
            <td class="text-right">$<?php echo number_format($corte['monto_inicial'], 2); ?></td>
        </tr>
        <tr>
            <td>Total Ingresos (<?php echo $totales['num_ingresos']; ?> movimientos)</td>
            <td class="text-right">+ $<?php echo number_format($totales['total_ingresos'], 2); ?></td>
        </tr>
        <tr>
            <td>Total Egresos (<?php echo $totales['num_egresos']; ?> movimientos)</td>
            <td class="text-right">- $<?php echo number_format($totales['total_egresos'], 2); ?></td>
        </tr>
        <tr class="total-row">
            <td>Saldo Esperado</td>
            <td class="text-right">
                $<?php 
                    $saldo_esperado = $corte['monto_inicial'] + $totales['total_ingresos'] - $totales['total_egresos'];
                    echo number_format($saldo_esperado, 2); 
                ?>
            </td>
        </tr>
        <?php if ($corte['monto_final'] > 0): ?>
        <tr>
            <td>Monto Final Declarado</td>
            <td class="text-right">$<?php echo number_format($corte['monto_final'], 2); ?></td>
        </tr>
        <tr class="<?php echo $corte['diferencia'] >= 0 ? 'text-success' : 'text-danger'; ?>">
            <td><strong>Diferencia</strong></td>
            <td class="text-right">
                <strong>$<?php echo number_format($corte['diferencia'], 2); ?></strong>
                <?php if ($corte['diferencia'] > 0): ?>
                    (Sobrante)
                <?php elseif ($corte['diferencia'] < 0): ?>
                    (Faltante)
                <?php endif; ?>
            </td>
        </tr>
        <?php endif; ?>
    </table>
    
    <h3>Detalle de Movimientos</h3>
    <table class="movements-table">
        <thead>
            <tr>
                <th>Fecha/Hora</th>
                <th>Tipo</th>
                <th>Concepto</th>
                <th>Método</th>
                <th class="text-right">Monto</th>
                <th>Usuario</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($movimientos as $mov): ?>
            <tr>
                <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha_hora'])); ?></td>
                <td><?php echo strtoupper($mov['tipo_movimiento']); ?></td>
                <td><?php echo htmlspecialchars($mov['concepto']); ?></td>
                <td><?php echo $mov['metodo_pago'] ? htmlspecialchars($mov['metodo_pago']) : '-'; ?></td>
                <td class="text-right">
                    <?php 
                        $signo = $mov['tipo_movimiento'] == 'egreso' ? '-' : '+';
                        echo $signo . ' $' . number_format($mov['monto'], 2);
                    ?>
                </td>
                <td><?php echo htmlspecialchars($mov['nombre_usuario'] ?? 'Sistema'); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php if ($corte['tipo_movimiento'] == 'fin'): ?>
    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line">
                Responsable de Caja
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                Supervisor
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
        <p>Documento generado automáticamente por Vendigbox</p>
        <p><?php echo date('d/m/Y H:i:s'); ?></p>
    </div>
</body>
</html>
