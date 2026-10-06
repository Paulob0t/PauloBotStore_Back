<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0); // No mostrar errores en HTML
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_ajax.log');

// Capturar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'mensaje' => 'Error fatal: ' . $error['message'] . ' en línea ' . $error['line']
        ]);
    }
});

header('Content-Type: application/json');

// Función para enviar respuesta JSON
function sendResponse($success, $mensaje, $data = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'mensaje' => $mensaje
    ], $data));
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
    sendResponse(false, 'No autorizado');
}

// Verificar que sea POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Método no permitido');
}

try {
    require_once 'db_config_dual.php';
    require_once 'CorteCaja.class.php';
    
    $corteCaja = new CorteCaja($conn);
    $action = $_POST['action'] ?? '';
    $id_usuario = intval($_SESSION['uid'] ?? 0);
    
    if ($id_usuario <= 0) {
        sendResponse(false, 'Usuario no válido');
    }
    
} catch (Exception $e) {
    sendResponse(false, 'Error al inicializar: ' . $e->getMessage());
}

switch ($action) {
    
    case 'iniciar_caja':
        try {
            $monto_inicial = floatval($_POST['monto_inicial'] ?? 0);
            $notas = trim($_POST['notas'] ?? '');
            
            if ($monto_inicial < 0) {
                sendResponse(false, 'El monto inicial no puede ser negativo');
            }
            
            $resultado = $corteCaja->iniciarCaja($monto_inicial, $id_usuario, $notas);
            
            if ($resultado['success']) {
                sendResponse(true, $resultado['mensaje'], ['id_corte' => $resultado['id_corte']]);
            } else {
                sendResponse(false, $resultado['mensaje']);
            }
            
        } catch (Exception $e) {
            sendResponse(false, 'Error al iniciar caja: ' . $e->getMessage());
        }
        break;
    
    case 'cerrar_caja':
        try {
            $monto_final = floatval($_POST['monto_final'] ?? 0);
            $notas = trim($_POST['notas'] ?? '');
            
            if ($monto_final < 0) {
                sendResponse(false, 'El monto final no puede ser negativo');
            }
            
            $resultado = $corteCaja->cerrarCaja($id_usuario, $monto_final, $notas);
            
            if ($resultado['success']) {
                sendResponse(true, $resultado['mensaje'], $resultado);
            } else {
                sendResponse(false, $resultado['mensaje']);
            }
            
        } catch (Exception $e) {
            sendResponse(false, 'Error al cerrar caja: ' . $e->getMessage());
        }
        break;
    
    case 'actualizar_config':
        $habilitado = intval($_POST['corte_automatico_habilitado'] ?? 0);
        $hora = $_POST['hora_corte_automatico'] ?? '23:59:00';
        $monto_default = floatval($_POST['monto_inicial_default'] ?? 0);
        $horas_para_cierre = intval($_POST['horas_para_cierre'] ?? 24);
        
        $sql = "UPDATE config_caja SET 
                corte_automatico_habilitado = ?,
                hora_corte_automatico = ?,
                monto_inicial_default = ?,
                horas_para_cierre = ?
                WHERE id = 1";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isdi", $habilitado, $hora, $monto_default, $horas_para_cierre);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'mensaje' => 'Configuración actualizada']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al actualizar']);
        }
        break;
    
    case 'ver_detalle_corte':
        $id_corte = intval($_POST['id_corte'] ?? 0);
        
        if ($id_corte <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'ID de corte inválido']);
            exit;
        }
        
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
            echo json_encode(['success' => false, 'mensaje' => 'Corte no encontrado']);
            exit;
        }
        
        // Obtener movimientos
        $movimientos = $corteCaja->getMovimientosCorte($id_corte);
        $totales = $corteCaja->calcularTotalesCorte($id_corte);
        
        // Generar HTML
        $html = '<div class="row mb-3">';
        $html .= '<div class="col-md-12">';
        $html .= '<h5>Corte #' . $corte['id'] . ' - ' . date('d/m/Y H:i', strtotime($corte['fecha'] . ' ' . $corte['hora'])) . '</h5>';
        $html .= '<p><strong>Usuario:</strong> ' . htmlspecialchars($corte['nombre_usuario'] ?? 'Sistema') . '</p>';
        if ($corte['notas']) {
            $html .= '<p><strong>Notas:</strong> ' . htmlspecialchars($corte['notas']) . '</p>';
        }
        $html .= '</div>';
        $html .= '</div>';
        
        // ✨ RESUMEN ULTRA PRO CON GRADIENTES Y ANIMACIONES
        $html .= '<div class="row mb-4" style="gap: 20px 0;">';
        
        // 💜 MONTO INICIAL
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card border-0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-8px)\'; this.style.boxShadow=\'0 15px 40px rgba(102, 126, 234, 0.6)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 10px 30px rgba(102, 126, 234, 0.4)\'">';
        $html .= '<div class="card-body" style="padding: 30px 25px;">';
        $html .= '<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">';
        $html .= '<div>';
        $html .= '<p style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Monto Inicial</p>';
        $html .= '</div>';
        $html .= '<div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">';
        $html .= '<i class="mdi mdi-cash-multiple" style="font-size: 1.8rem; color: white;"></i>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<h2 style="color: white; font-weight: 700; font-size: 2.2rem; margin: 10px 0 5px 0; font-family: \'Courier New\', monospace;">$' . number_format($corte['monto_inicial'], 2) . '</h2>';
        $html .= '<p style="color: rgba(255,255,255,0.8); font-size: 0.8rem; margin: 0;">💰 Capital de apertura</p>';
        $html .= '</div>';
        $html .= '</div></div>';
        
        // 💚 TOTAL INGRESOS
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card border-0" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(56, 239, 125, 0.4); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-8px)\'; this.style.boxShadow=\'0 15px 40px rgba(56, 239, 125, 0.6)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 10px 30px rgba(56, 239, 125, 0.4)\'">';
        $html .= '<div class="card-body" style="padding: 30px 25px;">';
        $html .= '<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">';
        $html .= '<div>';
        $html .= '<p style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Total Ingresos</p>';
        $html .= '</div>';
        $html .= '<div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">';
        $html .= '<i class="mdi mdi-trending-up" style="font-size: 1.8rem; color: white;"></i>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<h2 style="color: white; font-weight: 700; font-size: 2.2rem; margin: 10px 0 5px 0; font-family: \'Courier New\', monospace;">+$' . number_format($totales['total_ingresos'], 2) . '</h2>';
        $html .= '<div style="display: flex; align-items: center; gap: 8px;">';
        $html .= '<span style="background: rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 12px; color: white; font-size: 0.75rem; font-weight: 600;">' . $totales['num_ingresos'] . ' movimientos</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div></div>';
        
        // ❤️ TOTAL EGRESOS
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card border-0" style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(235, 51, 73, 0.4); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-8px)\'; this.style.boxShadow=\'0 15px 40px rgba(235, 51, 73, 0.6)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 10px 30px rgba(235, 51, 73, 0.4)\'">';
        $html .= '<div class="card-body" style="padding: 30px 25px;">';
        $html .= '<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">';
        $html .= '<div>';
        $html .= '<p style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Total Egresos</p>';
        $html .= '</div>';
        $html .= '<div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">';
        $html .= '<i class="mdi mdi-trending-down" style="font-size: 1.8rem; color: white;"></i>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<h2 style="color: white; font-weight: 700; font-size: 2.2rem; margin: 10px 0 5px 0; font-family: \'Courier New\', monospace;">-$' . number_format($totales['total_egresos'], 2) . '</h2>';
        $html .= '<div style="display: flex; align-items: center; gap: 8px;">';
        $html .= '<span style="background: rgba(255,255,255,0.3); padding: 4px 12px; border-radius: 12px; color: white; font-size: 0.75rem; font-weight: 600;">' . $totales['num_egresos'] . ' movimientos</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div></div>';
        
        // 💙 SALDO FINAL
        $diferencia_color = $corte['diferencia'] >= 0 ? 'rgba(56, 239, 125, 0.3)' : 'rgba(235, 51, 73, 0.3)';
        $diferencia_icon = $corte['diferencia'] >= 0 ? 'mdi-arrow-up-bold' : 'mdi-arrow-down-bold';
        
        $html .= '<div class="col-md-3">';
        $html .= '<div class="card border-0" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 20px; overflow: hidden; box-shadow: 0 10px 30px rgba(79, 172, 254, 0.4); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-8px)\'; this.style.boxShadow=\'0 15px 40px rgba(79, 172, 254, 0.6)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 10px 30px rgba(79, 172, 254, 0.4)\'">';
        $html .= '<div class="card-body" style="padding: 30px 25px;">';
        $html .= '<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">';
        $html .= '<div>';
        $html .= '<p style="color: rgba(255,255,255,0.9); font-size: 0.85rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Saldo Final</p>';
        $html .= '</div>';
        $html .= '<div style="background: rgba(255,255,255,0.2); width: 50px; height: 50px; border-radius: 15px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">';
        $html .= '<i class="mdi mdi-wallet" style="font-size: 1.8rem; color: white;"></i>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<h2 style="color: white; font-weight: 700; font-size: 2.2rem; margin: 10px 0 5px 0; font-family: \'Courier New\', monospace;">$' . number_format($corte['monto_final'], 2) . '</h2>';
        if ($corte['diferencia'] != 0) {
            $html .= '<div style="display: flex; align-items: center; gap: 6px;">';
            $html .= '<span style="background: ' . $diferencia_color . '; padding: 4px 12px; border-radius: 12px; color: white; font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 4px;">';
            $html .= '<i class="mdi ' . $diferencia_icon . '"></i>';
            $html .= 'Diferencia: $' . number_format($corte['diferencia'], 2);
            $html .= '</span>';
            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '</div></div>';
        
        $html .= '</div>';
        
        // 🔥 OBTENER COMANDAS DEL CORTE (PRIMERO!)
        $comandas = $corteCaja->getComandasDelCorte($id_corte);
        
        // 📊 ESTADÍSTICAS DE VENTAS
        if (!empty($comandas)) {
            // Calcular estadísticas
            $total_ventas = count($comandas);
            $total_vendido = 0;
            $ventas_efectivo = 0;
            $ventas_tarjeta = 0;
            $total_efectivo = 0;
            $total_tarjeta = 0;
            $total_productos = 0;
            
            foreach ($comandas as $cmd) {
                $total_vendido += $cmd['total'];
                
                // Contar por método de pago
                $metodo = strtolower($cmd['metodo_pago']);
                if (strpos($metodo, 'efectivo') !== false) {
                    $ventas_efectivo++;
                    $total_efectivo += $cmd['total'];
                } else {
                    $ventas_tarjeta++;
                    $total_tarjeta += $cmd['total'];
                }
                
                // Contar productos (del string separado por comas)
                if (!empty($cmd['productos'])) {
                    $productos_array = explode(',', $cmd['productos']);
                    foreach ($productos_array as $prod) {
                        if (preg_match('/(\d+)x/', $prod, $matches)) {
                            $total_productos += intval($matches[1]);
                        }
                    }
                }
            }
            
            // Obtener usuario (del primer registro de comandas)
            $sql_usuario = "SELECT u.nombre FROM usuarios u WHERE u.id = ? LIMIT 1";
            $stmt_user = $conn->prepare($sql_usuario);
            $usuario_id = $corte['id_usuario'];
            $stmt_user->bind_param("i", $usuario_id);
            $stmt_user->execute();
            $result_user = $stmt_user->get_result();
            $usuario_nombre = $result_user->num_rows > 0 ? $result_user->fetch_assoc()['nombre'] : 'N/A';
            
            // 📊 ESTADÍSTICAS DE VENTAS ULTRA PRO
            $html .= '<div class="row mb-4" style="gap: 20px 0;">';
            
            // 👤 TOTAL DE VENTAS POR USUARIO
            $html .= '<div class="col-md-4">';
            $html .= '<div class="card border-0" style="border-radius: 20px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.08); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-5px)\'; this.style.boxShadow=\'0 12px 35px rgba(0,0,0,0.12)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 8px 25px rgba(0,0,0,0.08)\'">';
            $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px 25px;">';
            $html .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
            $html .= '<div>';
            $html .= '<p style="color: rgba(255,255,255,0.9); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Total De Ventas Por Usuario</p>';
            $html .= '</div>';
            $html .= '<div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<i class="mdi mdi-account-cash" style="font-size: 1.5rem; color: white;"></i>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="card-body" style="padding: 30px 25px;">';
            $html .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
            $html .= '<div style="display: flex; align-items: center; gap: 12px;">';
            // Avatar del usuario
            $iniciales_user = '';
            $palabras_user = explode(' ', $usuario_nombre);
            foreach ($palabras_user as $palabra) {
                if (!empty($palabra)) {
                    $iniciales_user .= strtoupper(substr($palabra, 0, 1));
                }
            }
            $iniciales_user = substr($iniciales_user, 0, 2);
            $html .= '<div style="width: 50px; height: 50px; border-radius: 50%; background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);">';
            $html .= $iniciales_user;
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<span style="display: block; color: #2d3748; font-weight: 600; font-size: 1rem; line-height: 1.2;">' . htmlspecialchars($usuario_nombre) . '</span>';
            $html .= '<span style="display: block; color: #718096; font-size: 0.8rem;">Responsable del corte</span>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div style="text-align: right;">';
            $html .= '<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); padding: 12px 20px; border-radius: 15px; display: inline-block;">';
            $html .= '<p style="color: white; font-size: 1.8rem; font-weight: 700; margin: 0; font-family: \'Courier New\', monospace;">$' . number_format($total_vendido, 2) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div></div>';
            
            // 📦 VENTAS PRODUCTOS/SERVICIOS
            $html .= '<div class="col-md-4">';
            $html .= '<div class="card border-0" style="border-radius: 20px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.08); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-5px)\'; this.style.boxShadow=\'0 12px 35px rgba(0,0,0,0.12)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 8px 25px rgba(0,0,0,0.08)\'">';
            $html .= '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 20px 25px;">';
            $html .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
            $html .= '<div>';
            $html .= '<p style="color: rgba(255,255,255,0.9); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Ventas Productos/Servicios</p>';
            $html .= '</div>';
            $html .= '<div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<i class="mdi mdi-package-variant" style="font-size: 1.5rem; color: white;"></i>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="card-body" style="padding: 25px;">';
            $html .= '<table class="table mb-0" style="border: none;">';
            // Productos
            $html .= '<tr style="border-bottom: 1px solid #f0f0f0;">';
            $html .= '<td style="border: none; padding: 15px 10px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
            $html .= '<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<i class="mdi mdi-cube" style="color: white; font-size: 1.2rem;"></i>';
            $html .= '</div>';
            $html .= '<span style="color: #2d3748; font-weight: 600;">Productos</span>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 15px 10px; text-align: center;">';
            $html .= '<span style="background: #e6fffa; color: #11998e; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">' . $total_productos . '</span>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 15px 10px; text-align: right; color: #11998e; font-weight: 700; font-size: 1.1rem;">$' . number_format($total_vendido, 2) . '</td>';
            $html .= '</tr>';
            // Servicios
            $html .= '<tr style="border-bottom: 1px solid #f0f0f0;">';
            $html .= '<td style="border: none; padding: 15px 10px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
            $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<i class="mdi mdi-cog" style="color: white; font-size: 1.2rem;"></i>';
            $html .= '</div>';
            $html .= '<span style="color: #2d3748; font-weight: 600;">Servicios</span>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 15px 10px; text-align: center;">';
            $html .= '<span style="background: #f0f0f0; color: #718096; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">0</span>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 15px 10px; text-align: right; color: #718096; font-weight: 700; font-size: 1.1rem;">$0.00</td>';
            $html .= '</tr>';
            // Total
            $html .= '<tr style="background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%);">';
            $html .= '<td style="border: none; padding: 18px 10px;">';
            $html .= '<span style="color: #2d3748; font-weight: 700; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">TOTAL</span>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 18px 10px; text-align: center;">';
            $html .= '<span style="background: #2d3748; color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.95rem;">' . $total_productos . '</span>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 18px 10px; text-align: right; color: #11998e; font-weight: 700; font-size: 1.3rem;">$' . number_format($total_vendido, 2) . '</td>';
            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';
            $html .= '</div></div>';
            
            // 💳 VENTAS POR MÉTODO DE PAGO
            $html .= '<div class="col-md-4">';
            $html .= '<div class="card border-0" style="border-radius: 20px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.08); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-5px)\'; this.style.boxShadow=\'0 12px 35px rgba(0,0,0,0.12)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 8px 25px rgba(0,0,0,0.08)\'">';
            $html .= '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); padding: 20px 25px;">';
            $html .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
            $html .= '<div>';
            $html .= '<p style="color: rgba(255,255,255,0.9); font-size: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin: 0;">Ventas Por Método De Pago</p>';
            $html .= '</div>';
            $html .= '<div style="background: rgba(255,255,255,0.2); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<i class="mdi mdi-credit-card-multiple" style="font-size: 1.5rem; color: white;"></i>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="card-body" style="padding: 25px;">';
            $html .= '<table class="table mb-0" style="border: none;">';
            // Efectivo
            $html .= '<tr style="border-bottom: 1px solid #f0f0f0;">';
            $html .= '<td style="border: none; padding: 15px 10px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
            $html .= '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<span style="font-size: 1.3rem;">💵</span>';
            $html .= '</div>';
            $html .= '<span style="color: #2d3748; font-weight: 600;">Efectivo</span>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 15px 10px; text-align: center;">';
            $html .= '<span style="background: #fff5f7; color: #f5576c; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">' . $ventas_efectivo . '</span>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 15px 10px; text-align: right; color: #f5576c; font-weight: 700; font-size: 1.1rem;">$' . number_format($total_efectivo, 2) . '</td>';
            $html .= '</tr>';
            // Tarjeta
            $html .= '<tr style="border-bottom: 1px solid #f0f0f0;">';
            $html .= '<td style="border: none; padding: 15px 10px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
            $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<span style="font-size: 1.3rem;">💳</span>';
            $html .= '</div>';
            $html .= '<span style="color: #2d3748; font-weight: 600;">Tarjeta</span>';
            $html .= '</div>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 15px 10px; text-align: center;">';
            $html .= '<span style="background: #f0f0f0; color: #667eea; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 0.9rem;">' . $ventas_tarjeta . '</span>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 15px 10px; text-align: right; color: #667eea; font-weight: 700; font-size: 1.1rem;">$' . number_format($total_tarjeta, 2) . '</td>';
            $html .= '</tr>';
            // Total
            $html .= '<tr style="background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%);">';
            $html .= '<td style="border: none; padding: 18px 10px;">';
            $html .= '<span style="color: #2d3748; font-weight: 700; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">TOTAL</span>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 18px 10px; text-align: center;">';
            $html .= '<span style="background: #2d3748; color: white; padding: 8px 16px; border-radius: 20px; font-weight: 700; font-size: 0.95rem;">' . $total_ventas . '</span>';
            $html .= '</td>';
            $html .= '<td style="border: none; padding: 18px 10px; text-align: right; color: #11998e; font-weight: 700; font-size: 1.3rem;">$' . number_format($total_vendido, 2) . '</td>';
            $html .= '</tr>';
            $html .= '</table>';
            $html .= '</div>';
            $html .= '</div></div>';
            $html .= '</div></div></div>';
            
            $html .= '</div>'; // Cierra row
        }
        
        // 📋 TABLA DE MOVIMIENTOS CON DISEÑO ULTRA PRO
        $html .= '<div class="row mt-4">';
        $html .= '<div class="col-12">';
        $html .= '<div class="card border-0" style="box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 15px; overflow: hidden;">';
        
        // Header con gradiente y contador
        $total_movs = count($movimientos);
        $html .= '<div class="card-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px 30px; border-bottom: 3px solid rgba(255,255,255,0.2);">';
        $html .= '<div class="d-flex justify-content-between align-items-center">';
        $html .= '<div>';
        $html .= '<h4 class="mb-1 text-white" style="font-weight: 600; letter-spacing: -0.5px;">';
        $html .= '<i class="mdi mdi-cash-multiple" style="font-size: 1.3em; vertical-align: middle;"></i> ';
        $html .= 'Movimientos del Corte';
        $html .= '</h4>';
        $html .= '<p class="mb-0 text-white" style="opacity: 0.9; font-size: 0.9rem;">Registro detallado de transacciones</p>';
        $html .= '</div>';
        $html .= '<div class="text-right">';
        $html .= '<span class="badge badge-light" style="font-size: 1.1rem; padding: 10px 20px; border-radius: 25px; font-weight: 600;">';
        $html .= '<i class="mdi mdi-format-list-numbered"></i> ' . $total_movs . ' ' . ($total_movs == 1 ? 'registro' : 'registros');
        $html .= '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<div class="card-body p-0">';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table mb-0" style="border-collapse: separate; border-spacing: 0;">';
        $html .= '<thead>';
        $html .= '<tr style="background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%); border-bottom: 2px solid #dee2e6;">';
        $html .= '<th class="border-0 py-4 px-4" style="font-weight: 700; color: #2d3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">📅 Fecha/Hora</th>';
        $html .= '<th class="border-0 py-4" style="font-weight: 700; color: #2d3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">🏷️ Tipo</th>';
        $html .= '<th class="border-0 py-4" style="font-weight: 700; color: #2d3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">📝 Concepto</th>';
        $html .= '<th class="border-0 py-4" style="font-weight: 700; color: #2d3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">💳 Método</th>';
        $html .= '<th class="border-0 py-4 text-right" style="font-weight: 700; color: #2d3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">💰 Monto</th>';
        $html .= '<th class="border-0 py-4" style="font-weight: 700; color: #2d3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">👤 Usuario</th>';
        $html .= '<th class="border-0 py-4 text-center" style="font-weight: 700; color: #2d3748; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px;">⚙️ Acción</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        
        $index = 0;
        foreach ($movimientos as $mov) {
            $index++;
            $row_bg = $index % 2 == 0 ? '#fafbfc' : '#ffffff';
            
            $html .= '<tr style="background: ' . $row_bg . '; border-bottom: 1px solid #e9ecef; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.background=\'#f0f4ff\'; this.style.transform=\'translateX(4px)\'; this.style.boxShadow=\'0 2px 8px rgba(102,126,234,0.15)\'" onmouseout="this.style.background=\'' . $row_bg . '\'; this.style.transform=\'translateX(0)\'; this.style.boxShadow=\'none\'">';
            
            // Fecha/Hora con icono
            $html .= '<td class="py-4 px-4" style="vertical-align: middle;">';
            $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
            $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">';
            $html .= '<i class="mdi mdi-clock-outline text-white" style="font-size: 1.3rem;"></i>';
            $html .= '</div>';
            $html .= '<div style="line-height: 1.3;">';
            $html .= '<div style="color: #2d3748; font-weight: 600; font-size: 0.95rem;">' . date('d/m/Y', strtotime($mov['fecha_hora'])) . '</div>';
            $html .= '<div style="color: #718096; font-size: 0.85rem;">' . date('H:i:s', strtotime($mov['fecha_hora'])) . '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</td>';
            
            // Tipo con badge mejorado
            $badge_config = [
                'apertura' => ['color' => '#3182ce', 'bg' => '#ebf8ff', 'icon' => 'lock-open-variant', 'text' => 'Apertura'],
                'ingreso' => ['color' => '#38a169', 'bg' => '#f0fff4', 'icon' => 'arrow-down-circle', 'text' => 'Ingreso'],
                'egreso' => ['color' => '#e53e3e', 'bg' => '#fff5f5', 'icon' => 'arrow-up-circle', 'text' => 'Egreso'],
                'cierre' => ['color' => '#2d3748', 'bg' => '#edf2f7', 'icon' => 'lock', 'text' => 'Cierre']
            ];
            $config = $badge_config[$mov['tipo_movimiento']] ?? $badge_config['ingreso'];
            
            $html .= '<td class="py-4" style="vertical-align: middle;">';
            $html .= '<span style="display: inline-flex; align-items: center; gap: 8px; background: ' . $config['bg'] . '; color: ' . $config['color'] . '; padding: 8px 16px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; border: 2px solid ' . $config['color'] . '20;">';
            $html .= '<i class="mdi mdi-' . $config['icon'] . '" style="font-size: 1.2rem;"></i>';
            $html .= '<span>' . $config['text'] . '</span>';
            $html .= '</span>';
            $html .= '</td>';
            
            // Concepto
            $html .= '<td class="py-4" style="color: #4a5568; font-weight: 500; font-size: 0.95rem; vertical-align: middle; max-width: 250px;">';
            $html .= '<div style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="' . htmlspecialchars($mov['concepto']) . '">';
            $html .= htmlspecialchars($mov['concepto']);
            $html .= '</div>';
            $html .= '</td>';
            
            // Método de pago
            $metodo_icons = [
                'efectivo' => '💵',
                'tarjeta' => '💳',
                'transferencia' => '🏦'
            ];
            $metodo_lower = strtolower($mov['metodo_pago'] ?? '');
            $metodo_icon = $metodo_icons[$metodo_lower] ?? '💰';
            
            $html .= '<td class="py-4" style="vertical-align: middle;">';
            if ($mov['metodo_pago']) {
                $html .= '<span style="display: inline-flex; align-items: center; gap: 6px; color: #4a5568; font-size: 0.9rem;">';
                $html .= '<span style="font-size: 1.2rem;">' . $metodo_icon . '</span>';
                $html .= '<span>' . htmlspecialchars($mov['metodo_pago']) . '</span>';
                $html .= '</span>';
            } else {
                $html .= '<span style="color: #a0aec0; font-style: italic; font-size: 0.85rem;">—</span>';
            }
            $html .= '</td>';
            
            // Monto con animación
            $is_egreso = $mov['tipo_movimiento'] == 'egreso';
            $monto_color = $is_egreso ? '#e53e3e' : '#38a169';
            $monto_bg = $is_egreso ? '#fff5f5' : '#f0fff4';
            $signo = $is_egreso ? '-' : '+';
            
            $html .= '<td class="py-4 text-right" style="vertical-align: middle;">';
            $html .= '<div style="display: inline-block; background: ' . $monto_bg . '; padding: 10px 20px; border-radius: 10px; border-left: 4px solid ' . $monto_color . ';">';
            $html .= '<span style="color: ' . $monto_color . '; font-weight: 700; font-size: 1.1rem; font-family: \'Courier New\', monospace;">';
            $html .= $signo . '$' . number_format($mov['monto'], 2);
            $html .= '</span>';
            $html .= '</div>';
            $html .= '</td>';
            
            // Usuario con avatar
            $iniciales = '';
            $nombre_usuario = $mov['nombre_usuario'] ?? 'Sistema';
            $palabras = explode(' ', $nombre_usuario);
            foreach ($palabras as $palabra) {
                if (!empty($palabra)) {
                    $iniciales .= strtoupper(substr($palabra, 0, 1));
                }
            }
            $iniciales = substr($iniciales, 0, 2);
            
            $html .= '<td class="py-4" style="vertical-align: middle;">';
            $html .= '<div style="display: flex; align-items: center; gap: 10px;">';
            $html .= '<div style="width: 38px; height: 38px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; box-shadow: 0 2px 8px rgba(102,126,234,0.4);">';
            $html .= $iniciales;
            $html .= '</div>';
            $html .= '<span style="color: #2d3748; font-weight: 500; font-size: 0.9rem;">' . htmlspecialchars($nombre_usuario) . '</span>';
            $html .= '</div>';
            $html .= '</td>';
            
            // Botón acción mejorado
            $html .= '<td class="py-4 text-center" style="vertical-align: middle;">';
            $html .= '<button class="btn btn-sm" onclick="verDetalleMovimiento(' . $mov['id'] . ')" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: white; padding: 8px 20px; border-radius: 20px; font-weight: 600; font-size: 0.85rem; box-shadow: 0 4px 12px rgba(102,126,234,0.4); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);" onmouseover="this.style.transform=\'translateY(-2px)\'; this.style.boxShadow=\'0 6px 20px rgba(102,126,234,0.6)\'" onmouseout="this.style.transform=\'translateY(0)\'; this.style.boxShadow=\'0 4px 12px rgba(102,126,234,0.4)\'">';
            $html .= '<i class="mdi mdi-eye" style="margin-right: 4px;"></i> Ver';
            $html .= '</button>';
            $html .= '</td>';
            
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        $html .= '</div>'; // table-responsive
        
        // Footer con resumen
        $html .= '<div style="background: linear-gradient(to top, #f8f9fa 0%, #ffffff 100%); padding: 20px 30px; border-top: 2px solid #e9ecef;">';
        $html .= '<div class="d-flex justify-content-between align-items-center">';
        $html .= '<div style="color: #718096; font-size: 0.9rem;">';
        $html .= '<i class="mdi mdi-information"></i> Total de movimientos registrados: <strong>' . $total_movs . '</strong>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<button class="btn btn-sm btn-outline-secondary" onclick="imprimirMovimientos()" style="border-radius: 20px; padding: 6px 18px;">';
        $html .= '<i class="mdi mdi-printer"></i> Imprimir';
        $html .= '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>'; // card-body
        $html .= '</div>'; // card
        $html .= '</div>'; // col
        $html .= '</div>'; // row
        
        echo json_encode(['success' => true, 'html' => $html]);
        break;
    
    case 'ver_detalle_movimiento':
        $id_movimiento = intval($_POST['id_movimiento'] ?? 0);
        
        if ($id_movimiento <= 0) {
            sendResponse(false, 'ID de movimiento inválido');
        }
        
        // Obtener información detallada del movimiento
        $sql = "SELECT m.*, u.nombre as nombre_usuario,
                v.folio, v.total as total_venta, v.metodo_pago as metodo_venta,
                v.fecha_venta, v.notas as notas_venta
                FROM movimientos_caja m
                LEFT JOIN usuarios u ON m.id_usuario = u.id
                LEFT JOIN ventas_comanda v ON m.id_venta = v.id_comanda
                WHERE m.id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_movimiento);
        $stmt->execute();
        $mov = $stmt->get_result()->fetch_assoc();
        
        if (!$mov) {
            sendResponse(false, 'Movimiento no encontrado');
        }
        
        // Si está vinculado a una venta, obtener los productos
        $productos_venta = [];
        if ($mov['id_venta']) {
            $sql_productos = "SELECT vd.*, p.nombre_producto, p.sku, p.imagen_principal
                             FROM ventas_detalle vd
                             LEFT JOIN productos p ON vd.id_producto = p.id_producto
                             WHERE vd.id_comandC = ?
                             ORDER BY vd.id_detalle";
            
            $stmt_prod = $conn->prepare($sql_productos);
            $stmt_prod->bind_param("i", $mov['id_venta']);
            $stmt_prod->execute();
            $result_prod = $stmt_prod->get_result();
            
            while ($prod = $result_prod->fetch_assoc()) {
                $productos_venta[] = $prod;
            }
        }
        
        // Generar HTML del detalle ULTRA PRO
        $html = '<div class="container-fluid">';
        
        // 🎯 HEADER PRINCIPAL CON BADGE DE TIPO
        $badge_gradient = '';
        $icon = '';
        $tipo_texto = '';
        switch ($mov['tipo_movimiento']) {
            case 'apertura': 
                $badge_gradient = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
                $icon = 'mdi-cash-plus';
                $tipo_texto = 'APERTURA DE CAJA';
                break;
            case 'ingreso': 
                $badge_gradient = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)';
                $icon = 'mdi-arrow-down-bold-circle';
                $tipo_texto = 'INGRESO';
                break;
            case 'egreso': 
                $badge_gradient = 'linear-gradient(135deg, #eb3349 0%, #f45c43 100%)';
                $icon = 'mdi-arrow-up-bold-circle';
                $tipo_texto = 'EGRESO';
                break;
            case 'cierre': 
                $badge_gradient = 'linear-gradient(135deg, #2d3748 0%, #1a202c 100%)';
                $icon = 'mdi-lock-check';
                $tipo_texto = 'CIERRE DE CAJA';
                break;
        }
        
        $html .= '<div class="row mb-4">';
        $html .= '<div class="col-12">';
        $html .= '<div style="background: ' . $badge_gradient . '; padding: 30px; border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.12);">';
        $html .= '<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">';
        
        // Tipo e ID
        $html .= '<div style="display: flex; align-items: center; gap: 15px;">';
        $html .= '<div style="background: rgba(255,255,255,0.25); width: 70px; height: 70px; border-radius: 18px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(10px);">';
        $html .= '<i class="mdi ' . $icon . '" style="font-size: 2.5rem; color: white;"></i>';
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<p style="color: rgba(255,255,255,0.85); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; margin: 0;">Movimiento</p>';
        $html .= '<h3 style="color: white; font-weight: 800; margin: 5px 0 0 0; font-size: 1.8rem;">' . $tipo_texto . '</h3>';
        $html .= '<p style="color: rgba(255,255,255,0.75); font-size: 0.85rem; margin: 5px 0 0 0;">ID #' . $mov['id'] . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Monto destacado
        $signo = $mov['tipo_movimiento'] == 'egreso' ? '-' : '+';
        $html .= '<div style="text-align: right;">';
        $html .= '<p style="color: rgba(255,255,255,0.85); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; margin: 0;">Monto</p>';
        $html .= '<h2 style="color: white; font-weight: 900; margin: 5px 0 0 0; font-size: 3rem; font-family: \'Courier New\', monospace;">' . $signo . '$' . number_format($mov['monto'], 2) . '</h2>';
        $html .= '</div>';
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // 📋 INFORMACIÓN PRINCIPAL
        $html .= '<div class="row mb-4">';
        
        // Card Información General
        $html .= '<div class="col-md-6 mb-3">';
        $html .= '<div class="card border-0" style="border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); height: 100%;">';
        $html .= '<div class="card-body" style="padding: 30px;">';
        $html .= '<h6 style="color: #2d3748; font-weight: 700; font-size: 1.1rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">';
        $html .= '<i class="mdi mdi-information-outline" style="font-size: 1.4rem; color: #667eea;"></i> Información General';
        $html .= '</h6>';
        
        // Fecha/Hora
        $html .= '<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0;">';
        $html .= '<div style="display: flex; align-items: center; gap: 12px;">';
        $html .= '<div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">';
        $html .= '<i class="mdi mdi-calendar-clock" style="color: white; font-size: 1.3rem;"></i>';
        $html .= '</div>';
        $html .= '<div style="flex: 1;">';
        $html .= '<p style="color: #718096; font-size: 0.8rem; margin: 0; font-weight: 600;">FECHA Y HORA</p>';
        $html .= '<p style="color: #2d3748; font-size: 1.05rem; font-weight: 700; margin: 3px 0 0 0;">' . date('d/m/Y H:i:s', strtotime($mov['fecha_hora'])) . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Usuario
        $html .= '<div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #f0f0f0;">';
        $html .= '<div style="display: flex; align-items: center; gap: 12px;">';
        
        // Avatar del usuario
        $nombre_usuario = $mov['nombre_usuario'] ?? 'Sistema';
        $iniciales_user = '';
        $palabras_user = explode(' ', $nombre_usuario);
        foreach ($palabras_user as $palabra) {
            if (!empty($palabra)) {
                $iniciales_user .= strtoupper(substr($palabra, 0, 1));
            }
        }
        $iniciales_user = substr($iniciales_user, 0, 2);
        
        $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 1.1rem;">';
        $html .= $iniciales_user;
        $html .= '</div>';
        $html .= '<div style="flex: 1;">';
        $html .= '<p style="color: #718096; font-size: 0.8rem; margin: 0; font-weight: 600;">USUARIO RESPONSABLE</p>';
        $html .= '<p style="color: #2d3748; font-size: 1.05rem; font-weight: 700; margin: 3px 0 0 0;">' . htmlspecialchars($nombre_usuario) . '</p>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Método de pago
        if ($mov['metodo_pago']) {
            $metodo_icon = $mov['metodo_pago'] == 'efectivo' ? 'cash' : 'credit-card';
            $metodo_gradient = $mov['metodo_pago'] == 'efectivo' ? 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)' : 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)';
            
            $html .= '<div style="margin-bottom: 0;">';
            $html .= '<div style="display: flex; align-items: center; gap: 12px;">';
            $html .= '<div style="background: ' . $metodo_gradient . '; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<i class="mdi mdi-' . $metodo_icon . '" style="color: white; font-size: 1.3rem;"></i>';
            $html .= '</div>';
            $html .= '<div style="flex: 1;">';
            $html .= '<p style="color: #718096; font-size: 0.8rem; margin: 0; font-weight: 600;">MÉTODO DE PAGO</p>';
            $html .= '<p style="color: #2d3748; font-size: 1.05rem; font-weight: 700; margin: 3px 0 0 0; text-transform: uppercase;">' . htmlspecialchars($mov['metodo_pago']) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Card Concepto
        $html .= '<div class="col-md-6 mb-3">';
        $html .= '<div class="card border-0" style="border-radius: 20px; box-shadow: 0 8px 25px rgba(0,0,0,0.08); height: 100%;">';
        $html .= '<div class="card-body" style="padding: 30px;">';
        $html .= '<h6 style="color: #2d3748; font-weight: 700; font-size: 1.1rem; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">';
        $html .= '<i class="mdi mdi-text-box-outline" style="font-size: 1.4rem; color: #11998e;"></i> Concepto y Detalles';
        $html .= '</h6>';
        
        $html .= '<div style="background: linear-gradient(to right, #f8f9fa 0%, #ffffff 100%); padding: 20px; border-radius: 15px; border-left: 4px solid #11998e;">';
        $html .= '<p style="color: #2d3748; font-size: 1.05rem; line-height: 1.6; margin: 0; font-weight: 600;">' . htmlspecialchars($mov['concepto']) . '</p>';
        $html .= '</div>';
        
        // Si tiene notas
        if ($mov['notas']) {
            $html .= '<div style="margin-top: 20px; background: #fff3cd; padding: 20px; border-radius: 15px; border-left: 4px solid #ffc107;">';
            $html .= '<p style="color: #856404; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin: 0 0 10px 0;">📝 NOTAS ADICIONALES</p>';
            $html .= '<p style="color: #856404; font-size: 0.95rem; line-height: 1.5; margin: 0;">' . nl2br(htmlspecialchars($mov['notas'])) . '</p>';
            $html .= '</div>';
        }
        
        // Si tiene referencia
        if ($mov['referencia']) {
            $html .= '<div style="margin-top: 15px; display: flex; align-items: center; gap: 10px;">';
            $html .= '<span style="background: #e6fffa; color: #11998e; padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 0.85rem;">🔗 Referencia: ' . htmlspecialchars($mov['referencia']) . '</span>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Si está vinculado a una venta
        if ($mov['id_venta'] && $mov['folio']) {
            $html .= '<div class="row mb-4">';
            $html .= '<div class="col-12">';
            $html .= '<div class="card border-0" style="border-radius: 20px; overflow: hidden; box-shadow: 0 8px 25px rgba(0,0,0,0.08);">';
            
            // Header de venta con gradiente
            $html .= '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 25px 30px;">';
            $html .= '<div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">';
            $html .= '<div style="display: flex; align-items: center; gap: 15px;">';
            $html .= '<div style="background: rgba(255,255,255,0.25); width: 60px; height: 60px; border-radius: 15px; display: flex; align-items: center; justify-content: center;">';
            $html .= '<i class="mdi mdi-cart" style="font-size: 2rem; color: white;"></i>';
            $html .= '</div>';
            $html .= '<div>';
            $html .= '<p style="color: rgba(255,255,255,0.85); font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 1.5px; margin: 0;">Venta Vinculada</p>';
            $html .= '<h4 style="color: white; font-weight: 800; margin: 5px 0 0 0;">Folio: ' . htmlspecialchars($mov['folio']) . '</h4>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div style="text-align: right;">';
            $html .= '<p style="color: rgba(255,255,255,0.85); font-size: 0.75rem; margin: 0;">Total Venta</p>';
            $html .= '<h3 style="color: white; font-weight: 800; margin: 5px 0 0 0; font-family: \'Courier New\', monospace;">$' . number_format($mov['total_venta'], 2) . '</h3>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Info de venta
            $html .= '<div class="card-body" style="padding: 30px;">';
            $html .= '<div class="row mb-3">';
            $html .= '<div class="col-md-6">';
            $html .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 15px;">';
            $html .= '<p style="color: #718096; font-size: 0.8rem; font-weight: 600; margin: 0;">💳 MÉTODO DE PAGO</p>';
            $html .= '<p style="color: #2d3748; font-size: 1.2rem; font-weight: 700; margin: 5px 0 0 0; text-transform: uppercase;">' . htmlspecialchars($mov['metodo_venta']) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '<div class="col-md-6">';
            $html .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 15px;">';
            $html .= '<p style="color: #718096; font-size: 0.8rem; font-weight: 600; margin: 0;">📅 FECHA DE VENTA</p>';
            $html .= '<p style="color: #2d3748; font-size: 1.2rem; font-weight: 700; margin: 5px 0 0 0;">' . date('d/m/Y H:i:s', strtotime($mov['fecha_venta'])) . '</p>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Tabla de productos si hay
            if (!empty($productos_venta)) {
                $html .= '<hr style="margin: 30px 0; border-color: #e0e0e0;">';
                $html .= '<h6 style="color: #2d3748; font-weight: 700; font-size: 1.1rem; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">';
                $html .= '<i class="mdi mdi-package-variant" style="font-size: 1.4rem; color: #f5576c;"></i> Productos Vendidos';
                $html .= '</h6>';
                
                $html .= '<div class="table-responsive" style="border-radius: 15px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">';
                $html .= '<table class="table mb-0" style="margin: 0;">';
                
                // Header con gradiente
                $html .= '<thead>';
                $html .= '<tr style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">';
                $html .= '<th style="color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; border: none;">Imagen</th>';
                $html .= '<th style="color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; border: none;">Producto</th>';
                $html .= '<th style="color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; border: none;">SKU</th>';
                $html .= '<th style="color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; border: none; text-align: center;">Cantidad</th>';
                $html .= '<th style="color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; border: none; text-align: right;">Precio Unit.</th>';
                $html .= '<th style="color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; border: none; text-align: right;">Descuento</th>';
                $html .= '<th style="color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; border: none; text-align: right;">Subtotal</th>';
                $html .= '<th style="color: white; font-weight: 600; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; padding: 18px 15px; border: none; text-align: right;">Total</th>';
                $html .= '</tr>';
                $html .= '</thead>';
                $html .= '<tbody>';
                
                foreach ($productos_venta as $prod) {
                    $html .= '<tr style="border-bottom: 1px solid #f0f0f0; transition: all 0.3s;" onmouseover="this.style.background=\'#f8f9fa\'" onmouseout="this.style.background=\'white\'">';
                    
                    // Imagen del producto
                    $html .= '<td style="border: none; padding: 15px; width: 80px;">';
                    if ($prod['imagen_principal']) {
                        // Verificar si es base64 o ruta de archivo
                        if (strpos($prod['imagen_principal'], 'data:image') === 0 || 
                            strpos($prod['imagen_principal'], 'iVBOR') === 0 || 
                            strpos($prod['imagen_principal'], '/9j/') === 0) {
                            $img_src = $prod['imagen_principal'];
                            if (strpos($img_src, 'data:image') !== 0) {
                                $img_src = 'data:image/png;base64,' . $img_src;
                            }
                        } else {
                            $img_src = '../../images/' . htmlspecialchars($prod['imagen_principal']);
                        }
                        
                        $html .= '<img src="' . $img_src . '" ';
                        $html .= 'style="width: 60px; height: 60px; object-fit: cover; border-radius: 12px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);" ';
                        $html .= 'alt="' . htmlspecialchars($prod['nombre_producto']) . '" ';
                        $html .= 'onerror="this.style.display=\'none\'; this.parentNode.innerHTML=\'<div style=\\\'width: 60px; height: 60px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;\\\'><i class=\\\'mdi mdi-image-off\\\' style=\\\'font-size: 1.8rem; color: white;\\\'></i></div>\';">';
                    } else {
                        $html .= '<div style="width: 60px; height: 60px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center;">';
                        $html .= '<i class="mdi mdi-image-off" style="font-size: 1.8rem; color: white;"></i>';
                        $html .= '</div>';
                    }
                    $html .= '</td>';
                    
                    // Nombre del producto
                    $html .= '<td style="border: none; padding: 15px;">';
                    $html .= '<span style="color: #2d3748; font-weight: 600; font-size: 0.95rem;">' . htmlspecialchars($prod['nombre_producto'] ?? 'Producto #' . $prod['id_producto']) . '</span>';
                    $html .= '</td>';
                    
                    // SKU
                    $html .= '<td style="border: none; padding: 15px;">';
                    $html .= '<code style="background: #f0f0f0; padding: 6px 12px; border-radius: 8px; font-size: 0.85rem; color: #667eea;">' . htmlspecialchars($prod['sku'] ?? 'N/A') . '</code>';
                    $html .= '</td>';
                    
                    // Cantidad
                    $html .= '<td style="border: none; padding: 15px; text-align: center;">';
                    $html .= '<span style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; padding: 8px 16px; border-radius: 10px; font-weight: 700; font-size: 0.95rem;">' . $prod['cantidad'] . '</span>';
                    $html .= '</td>';
                    
                    // Precio unitario
                    $html .= '<td style="border: none; padding: 15px; text-align: right; color: #2d3748; font-weight: 600; font-size: 0.95rem;">$' . number_format($prod['precio_unitario'], 2) . '</td>';
                    
                    // Descuento
                    if ($prod['descuento_unitario'] > 0) {
                        $html .= '<td style="border: none; padding: 15px; text-align: right;">';
                        $html .= '<span style="color: #eb3349; font-weight: 700; font-size: 0.95rem;">-$' . number_format($prod['descuento_unitario'], 2) . '</span>';
                        $html .= '</td>';
                    } else {
                        $html .= '<td style="border: none; padding: 15px; text-align: center; color: #cbd5e0;">—</td>';
                    }
                    
                    // Subtotal
                    $html .= '<td style="border: none; padding: 15px; text-align: right; color: #4a5568; font-weight: 600; font-size: 0.95rem;">$' . number_format($prod['subtotal'], 2) . '</td>';
                    
                    // Total
                    $html .= '<td style="border: none; padding: 15px; text-align: right;">';
                    $html .= '<span style="color: #11998e; font-weight: 800; font-size: 1.05rem;">$' . number_format($prod['total'], 2) . '</span>';
                    $html .= '</td>';
                    
                    $html .= '</tr>';
                }
                
                $html .= '</tbody>';
                $html .= '</table>';
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        sendResponse(true, 'Detalle cargado', ['html' => $html]);
        break;
    
    case 'registrar_movimiento_manual':
        // Para futura implementación de movimientos manuales
        $id_corte_actual = $corteCaja->getCorteActual()['id'] ?? null;
        
        if (!$id_corte_actual) {
            echo json_encode(['success' => false, 'mensaje' => 'No hay caja activa']);
            exit;
        }
        
        $tipo = $_POST['tipo'] ?? '';
        $concepto = $_POST['concepto'] ?? '';
        $monto = floatval($_POST['monto'] ?? 0);
        $metodo_pago = $_POST['metodo_pago'] ?? null;
        $notas = $_POST['notas'] ?? '';
        
        if (!in_array($tipo, ['ingreso', 'egreso'])) {
            echo json_encode(['success' => false, 'mensaje' => 'Tipo de movimiento inválido']);
            exit;
        }
        
        if ($monto <= 0) {
            echo json_encode(['success' => false, 'mensaje' => 'El monto debe ser mayor a cero']);
            exit;
        }
        
        $resultado = $corteCaja->registrarMovimiento(
            $id_corte_actual,
            $tipo,
            $concepto,
            $monto,
            $metodo_pago,
            null,
            null,
            $id_usuario,
            $notas
        );
        
        if ($resultado) {
            echo json_encode(['success' => true, 'mensaje' => 'Movimiento registrado correctamente']);
        } else {
            echo json_encode(['success' => false, 'mensaje' => 'Error al registrar el movimiento']);
        }
        break;
    
    case 'verificar_corte_automatico':
        $debe_hacer_corte = $corteCaja->verificarCorteAutomatico();
        echo json_encode([
            'success' => true,
            'debe_hacer_corte' => $debe_hacer_corte
        ]);
        break;
    
    case 'ejecutar_corte_automatico':
        try {
            $resultado = $corteCaja->ejecutarCorteAutomatico();
            
            if ($resultado['success']) {
                sendResponse(true, $resultado['mensaje']);
            } else {
                sendResponse(false, $resultado['mensaje']);
            }
            
        } catch (Exception $e) {
            sendResponse(false, 'Error en corte automático: ' . $e->getMessage());
        }
        break;
    
    default:
        sendResponse(false, 'Acción no válida: ' . htmlspecialchars($action));
        break;
}

if (isset($conn)) {
    $conn->close();
}
?>
