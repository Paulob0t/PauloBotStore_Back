<?php
/**
 * Clase para gestionar el sistema de cortes de caja
 */

class CorteCaja {
    private $conn;
    
    public function __construct($conexion) {
        $this->conn = $conexion;
    }
    
    /**
     * Obtener la configuración actual de la caja
     */
    public function getConfig() {
        $sql = "SELECT * FROM config_caja LIMIT 1";
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }
    
    /**
     * Verificar si hay una caja activa
     */
    public function hayCajaActiva() {
        $config = $this->getConfig();
        return $config && $config['caja_activa'] == 1;
    }
    
    /**
     * Obtener el corte actual activo
     */
    public function getCorteActual() {
        $config = $this->getConfig();
        if (!$config || !$config['id_corte_actual']) {
            return null;
        }
        
        $sql = "SELECT * FROM cortes WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $config['id_corte_actual']);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Verificar si es hora de cerrar la caja automáticamente
     * Retorna true si ya pasó la hora programada de cierre
     */
    public function verificarCierreAutomatico() {
        if (!$this->hayCajaActiva()) {
            return false;
        }
        
        $corte = $this->getCorteActual();
        
        if (!$corte || empty($corte['hora_cierre_programada'])) {
            return false;
        }
        
        // Comparar hora actual con hora programada
        $ahora = time();
        $hora_cierre = strtotime($corte['hora_cierre_programada']);
        
        // Si ya pasó la hora de cierre
        if ($ahora >= $hora_cierre) {
            return [
                'debe_cerrar' => true,
                'id_corte' => $corte['id'],
                'hora_programada' => $corte['hora_cierre_programada'],
                'id_usuario' => $corte['id_usuario']
            ];
        }
        
        return false;
    }
    
    /**
     * Ejecutar cierre automático si ya pasó la hora
     */
    public function ejecutarCierreAutomatico() {
        $verificacion = $this->verificarCierreAutomatico();
        
        if (!$verificacion || !$verificacion['debe_cerrar']) {
            return [
                'success' => false,
                'mensaje' => 'No es hora de cerrar automáticamente'
            ];
        }
        
        try {
            // Cerrar la caja automáticamente
            $resultado = $this->cerrarCaja(
                $verificacion['id_usuario'],
                'Cierre automático programado (' . $verificacion['hora_programada'] . ')'
            );
            
            // Log del cierre automático
            $log_msg = "[" . date('Y-m-d H:i:s') . "] Cierre automático ejecutado - Corte ID: " . $verificacion['id_corte'] . "\n";
            @file_put_contents(__DIR__ . '/../../logs/cortes_auto.log', $log_msg, FILE_APPEND);
            
            return [
                'success' => true,
                'mensaje' => 'Caja cerrada automáticamente',
                'corte_id' => $verificacion['id_corte'],
                'resultado' => $resultado
            ];
            
        } catch (Exception $e) {
            $log_error = "[" . date('Y-m-d H:i:s') . "] ERROR en cierre automático: " . $e->getMessage() . "\n";
            @file_put_contents(__DIR__ . '/../../logs/cortes_auto.log', $log_error, FILE_APPEND);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Iniciar una nueva caja/jornada
     */
    public function iniciarCaja($monto_inicial, $id_usuario, $notas = '') {
        try {
            $this->conn->begin_transaction();
            
            // Verificar si ya hay una caja activa
            if ($this->hayCajaActiva()) {
                throw new Exception("Ya existe una caja activa. Debe cerrarla primero.");
            }
            
            $fecha = date('Y-m-d');
            $hora = date('H:i:s');
            
            // Obtener configuración de horas para cierre automático
            $config = $this->getConfig();
            $horas_para_cierre = $config['horas_para_cierre'] ?? 24;
            
            // Calcular hora de cierre programada (fecha_hora_actual + horas configuradas)
            $hora_cierre_programada = date('Y-m-d H:i:s', strtotime("+$horas_para_cierre hours"));
            
            // Crear movimientos JSON iniciales
            $movimientos = [
                'apertura' => [
                    'fecha_hora' => date('Y-m-d H:i:s'),
                    'monto' => $monto_inicial,
                    'usuario' => $id_usuario,
                    'notas' => $notas,
                    'cierre_programado' => $hora_cierre_programada
                ],
                'ingresos' => [],
                'egresos' => []
            ];
            
            $movimientos_json = json_encode($movimientos, JSON_UNESCAPED_UNICODE);
            
            // Insertar nuevo corte de tipo 'inicio' con hora de cierre programada
            $sql = "INSERT INTO cortes (
                fecha, hora, hora_cierre_programada, tipo_movimiento, movimientos_json, comandas_ids,
                monto_inicial, id_usuario, notas
            ) VALUES (?, ?, ?, 'inicio', ?, '', ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ssssdis", $fecha, $hora, $hora_cierre_programada, $movimientos_json, $monto_inicial, $id_usuario, $notas);
            $stmt->execute();
            
            $id_corte = $this->conn->insert_id;
            
            // Registrar movimiento de apertura
            $this->registrarMovimiento(
                $id_corte,
                'apertura',
                'Apertura de caja',
                $monto_inicial,
                null,
                null,
                null,
                $id_usuario,
                $notas
            );
            
            // Actualizar configuración
            $sql_config = "UPDATE config_caja SET 
                caja_activa = TRUE,
                id_corte_actual = ?,
                fecha_ultimo_corte = NOW()
                WHERE id = 1";
            
            $stmt_config = $this->conn->prepare($sql_config);
            $stmt_config->bind_param("i", $id_corte);
            $stmt_config->execute();
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'id_corte' => $id_corte,
                'mensaje' => 'Caja iniciada correctamente'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Cerrar la caja actual
     */
    public function cerrarCaja($id_usuario, $monto_final_declarado, $notas = '') {
        try {
            $this->conn->begin_transaction();
            
            // Verificar que hay una caja activa
            if (!$this->hayCajaActiva()) {
                throw new Exception("No hay una caja activa para cerrar.");
            }
            
            $corte_actual = $this->getCorteActual();
            if (!$corte_actual) {
                throw new Exception("No se encontró el corte actual.");
            }
            
            $id_corte = $corte_actual['id'];
            
            // Calcular totales
            $totales = $this->calcularTotalesCorte($id_corte);
            
            $monto_inicial = $corte_actual['monto_inicial'];
            $total_ingresos = $totales['total_ingresos'];
            $total_egresos = $totales['total_egresos'];
            $monto_esperado = $monto_inicial + $total_ingresos - $total_egresos;
            $diferencia = $monto_final_declarado - $monto_esperado;
            
            // Obtener movimientos actuales
            $movimientos = json_decode($corte_actual['movimientos_json'], true);
            
            // Si no hay movimientos o el JSON es inválido, crear array vacío
            if (!is_array($movimientos)) {
                $movimientos = [
                    'apertura' => [],
                    'ingresos' => [],
                    'egresos' => []
                ];
            }
            
            $movimientos['cierre'] = [
                'fecha_hora' => date('Y-m-d H:i:s'),
                'monto_declarado' => $monto_final_declarado,
                'monto_esperado' => $monto_esperado,
                'diferencia' => $diferencia,
                'usuario' => $id_usuario,
                'notas' => $notas
            ];
            
            $movimientos_json = json_encode($movimientos, JSON_UNESCAPED_UNICODE);
            
            // 🔥 ACTUALIZAR EL CORTE DE INICIO CON TODA LA INFO DEL CIERRE
            // Ya no creamos un registro "fin", solo actualizamos el "inicio"
            $fecha_cierre = date('Y-m-d H:i:s');
            
            $sql_update = "UPDATE cortes SET 
                tipo_movimiento = 'fin',
                monto_final = ?,
                total_ingresos = ?,
                total_egresos = ?,
                diferencia = ?,
                movimientos_json = ?,
                notas = CONCAT(COALESCE(notas, ''), '\n--- CIERRE ---\n', ?)
                WHERE id = ?";
            
            $stmt_update = $this->conn->prepare($sql_update);
            $stmt_update->bind_param(
                "ddddssi",
                $monto_final_declarado, $total_ingresos, $total_egresos,
                $diferencia, $movimientos_json, $notas, $id_corte
            );
            $stmt_update->execute();
            
            // Registrar movimiento de cierre
            $this->registrarMovimiento(
                $id_corte,
                'cierre',
                'Cierre de caja',
                $monto_final_declarado,
                null,
                null,
                null,
                $id_usuario,
                $notas
            );
            
            // Actualizar configuración
            $sql_config = "UPDATE config_caja SET 
                caja_activa = FALSE,
                id_corte_actual = NULL,
                fecha_ultimo_corte = NOW()
                WHERE id = 1";
            
            $this->conn->query($sql_config);
            
            $this->conn->commit();
            
            return [
                'success' => true,
                'monto_esperado' => $monto_esperado,
                'monto_declarado' => $monto_final_declarado,
                'diferencia' => $diferencia,
                'total_ingresos' => $total_ingresos,
                'total_egresos' => $total_egresos,
                'mensaje' => 'Caja cerrada correctamente'
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            return [
                'success' => false,
                'mensaje' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Registrar un movimiento en la caja
     */
    public function registrarMovimiento(
        $id_corte, 
        $tipo, 
        $concepto, 
        $monto, 
        $metodo_pago = null,
        $referencia = null,
        $id_venta = null,
        $id_usuario = null,
        $notas = null
    ) {
        $sql = "INSERT INTO movimientos_caja (
            id_corte, tipo_movimiento, concepto, monto,
            metodo_pago, referencia, id_venta, id_usuario, notas
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "issdssiis",
            $id_corte, $tipo, $concepto, $monto,
            $metodo_pago, $referencia, $id_venta, $id_usuario, $notas
        );
        
        return $stmt->execute();
    }
    
    /**
     * Calcular totales de un corte
     */
    public function calcularTotalesCorte($id_corte) {
        $sql = "SELECT 
            SUM(CASE WHEN tipo_movimiento = 'ingreso' THEN monto ELSE 0 END) as total_ingresos,
            SUM(CASE WHEN tipo_movimiento = 'egreso' THEN monto ELSE 0 END) as total_egresos,
            COUNT(CASE WHEN tipo_movimiento = 'ingreso' THEN 1 END) as num_ingresos,
            COUNT(CASE WHEN tipo_movimiento = 'egreso' THEN 1 END) as num_egresos
        FROM movimientos_caja
        WHERE id_corte = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_corte);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    /**
     * Obtener movimientos de un corte
     */
    public function getMovimientosCorte($id_corte) {
        $sql = "SELECT m.*, u.nombre as nombre_usuario
        FROM movimientos_caja m
        LEFT JOIN usuarios u ON m.id_usuario = u.id
        WHERE m.id_corte = ?
        ORDER BY m.fecha_hora DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id_corte);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $movimientos = [];
        while ($row = $result->fetch_assoc()) {
            $movimientos[] = $row;
        }
        
        return $movimientos;
    }
    
    /**
     * Obtener historial de cortes
     */
    public function getHistorialCortes($fecha_desde = null, $fecha_hasta = null, $limit = 50) {
        $sql = "SELECT c.*, u.nombre as nombre_usuario
        FROM cortes c
        LEFT JOIN usuarios u ON c.id_usuario = u.id
        WHERE c.tipo_movimiento = 'fin'"; // 🔥 Solo mostrar los cortes cerrados
        
        $params = [];
        $types = "";
        
        if ($fecha_desde) {
            $sql .= " AND c.fecha >= ?";
            $params[] = $fecha_desde;
            $types .= "s";
        }
        
        if ($fecha_hasta) {
            $sql .= " AND c.fecha <= ?";
            $params[] = $fecha_hasta;
            $types .= "s";
        }
        
        $sql .= " ORDER BY c.fecha DESC, c.hora DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";
        
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $cortes = [];
        while ($row = $result->fetch_assoc()) {
            $cortes[] = $row;
        }
        
        return $cortes;
    }
    
    /**
     * Verificar si se debe hacer corte automático
     */
    public function verificarCorteAutomatico() {
        $config = $this->getConfig();
        
        if (!$config || !$config['corte_automatico_habilitado']) {
            return false;
        }
        
        if (!$this->hayCajaActiva()) {
            return false;
        }
        
        $hora_actual = date('H:i:s');
        $hora_corte = $config['hora_corte_automatico'];
        
        $fecha_ultimo_corte = $config['fecha_ultimo_corte'];
        $fecha_actual = date('Y-m-d');
        
        // Verificar si ya pasó la hora del corte y no se ha hecho hoy
        if ($hora_actual >= $hora_corte && 
            (!$fecha_ultimo_corte || date('Y-m-d', strtotime($fecha_ultimo_corte)) < $fecha_actual)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Ejecutar corte automático
     */
    public function ejecutarCorteAutomatico() {
        // Usuario del sistema (ID 1 por defecto)
        $id_usuario_sistema = 1;
        
        // Calcular monto final basado en movimientos
        $corte_actual = $this->getCorteActual();
        if (!$corte_actual) {
            return ['success' => false, 'mensaje' => 'No hay corte actual'];
        }
        
        $totales = $this->calcularTotalesCorte($corte_actual['id']);
        $monto_final = $corte_actual['monto_inicial'] + 
                      $totales['total_ingresos'] - 
                      $totales['total_egresos'];
        
        // Cerrar caja actual
        $resultado_cierre = $this->cerrarCaja(
            $id_usuario_sistema,
            $monto_final,
            'Corte automático del sistema'
        );
        
        if (!$resultado_cierre['success']) {
            return $resultado_cierre;
        }
        
        // Abrir nueva caja
        $resultado_apertura = $this->iniciarCaja(
            $monto_final,
            $id_usuario_sistema,
            'Apertura automática del sistema'
        );
        
        return [
            'success' => true,
            'cierre' => $resultado_cierre,
            'apertura' => $resultado_apertura,
            'mensaje' => 'Corte automático ejecutado correctamente'
        ];
    }
    
    /**
     * Registrar una comanda/venta en el corte activo
     * @param int $id_comanda ID de la venta/comanda realizada
     * @return bool
     */
    public function registrarComandaEnCorte($id_comanda) {
        try {
            // Verificar que hay una caja activa
            if (!$this->hayCajaActiva()) {
                return false;
            }
            
            $corte_actual = $this->getCorteActual();
            if (!$corte_actual) {
                return false;
            }
            
            $id_corte = $corte_actual['id'];
            
            // Obtener IDs actuales
            $comandas_actuales = $corte_actual['comandas_ids'];
            $array_comandas = $comandas_actuales ? explode(',', $comandas_actuales) : [];
            
            // Agregar nuevo ID solo si no existe
            if (!in_array($id_comanda, $array_comandas)) {
                $array_comandas[] = $id_comanda;
                $nuevas_comandas = implode(',', $array_comandas);
                
                // Actualizar el corte
                $sql = "UPDATE cortes SET comandas_ids = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $nuevas_comandas, $id_corte);
                return $stmt->execute();
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error al registrar comanda en corte: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener todas las comandas de un corte
     * @param int $id_corte ID del corte
     * @return array Array de comandas con sus detalles
     */
    public function getComandasDelCorte($id_corte) {
        try {
            $sql = "SELECT comandas_ids FROM cortes WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $id_corte);
            $stmt->execute();
            $result = $stmt->get_result();
            $corte = $result->fetch_assoc();
            
            if (!$corte || empty($corte['comandas_ids'])) {
                return [];
            }
            
            $ids = $corte['comandas_ids'];
            
            // Validar que los IDs son válidos
            if (empty(trim($ids))) {
                return [];
            }
            
            // Consultar las comandas
            $sql_comandas = "SELECT 
                v.*,
                GROUP_CONCAT(
                    CONCAT(dv.cantidad, 'x ', p.nombre_producto) 
                    SEPARATOR ', '
                ) as productos
                FROM ventas_comanda v
                LEFT JOIN ventas_detalle dv ON v.id_comanda = dv.id_comandC
                LEFT JOIN productos p ON dv.id_producto = p.id_producto
                WHERE v.id_comanda IN (" . $ids . ")
                GROUP BY v.id_comanda
                ORDER BY v.fecha_venta DESC";
            
            $result = $this->conn->query($sql_comandas);
            
            if (!$result) {
                error_log("Error en query comandas: " . $this->conn->error);
                return [];
            }
            
            $comandas = [];
            
            while ($row = $result->fetch_assoc()) {
                $comandas[] = $row;
            }
            
            return $comandas;
            
        } catch (Exception $e) {
            error_log("Error al obtener comandas del corte: " . $e->getMessage());
            return [];
        }
    }
}
?>
