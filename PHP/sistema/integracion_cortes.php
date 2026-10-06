<?php
/**
 * Archivo para integrar las ventas con el sistema de cortes de caja
 * Debe ser incluido en procesar_venta.php después de registrar una venta
 */

require_once __DIR__ . '/admin/dist/db_config_dual.php';
require_once __DIR__ . '/admin/dist/CorteCaja.class.php';

/**
 * Registra una venta en el corte de caja actual
 * 
 * @param int $id_venta ID de la venta en ventas_comanda
 * @param float $monto_total Monto total de la venta
 * @param string $metodo_pago Método de pago utilizado
 * @param int $id_usuario ID del usuario que realizó la venta
 * @return bool True si se registró correctamente
 */
function registrarVentaEnCorte($id_venta, $monto_total, $metodo_pago, $id_usuario = null) {
    global $conn;
    
    try {
        $corteCaja = new CorteCaja($conn);
        
        // Verificar si hay una caja activa
        if (!$corteCaja->hayCajaActiva()) {
            // Log del error pero no interrumpir la venta
            error_log("ADVERTENCIA: Venta registrada sin caja activa - ID Venta: $id_venta");
            return false;
        }
        
        $corte_actual = $corteCaja->getCorteActual();
        if (!$corte_actual) {
            error_log("ERROR: No se pudo obtener el corte actual - ID Venta: $id_venta");
            return false;
        }
        
        // Registrar el movimiento
        $resultado = $corteCaja->registrarMovimiento(
            $corte_actual['id'],
            'ingreso',
            "Venta #$id_venta",
            $monto_total,
            $metodo_pago,
            "VENTA-$id_venta",
            $id_venta,
            $id_usuario,
            "Ingreso por venta de productos"
        );
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("ERROR al registrar venta en corte: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra un movimiento manual de egreso
 * 
 * @param float $monto Monto del egreso
 * @param string $concepto Concepto del egreso
 * @param string $metodo_pago Método de pago
 * @param int $id_usuario ID del usuario
 * @param string $notas Notas adicionales
 * @return array Resultado de la operación
 */
function registrarEgreso($monto, $concepto, $metodo_pago = 'efectivo', $id_usuario = null, $notas = '') {
    global $conn;
    
    try {
        $corteCaja = new CorteCaja($conn);
        
        if (!$corteCaja->hayCajaActiva()) {
            return [
                'success' => false,
                'mensaje' => 'No hay una caja activa. Debe iniciar una caja primero.'
            ];
        }
        
        $corte_actual = $corteCaja->getCorteActual();
        
        $resultado = $corteCaja->registrarMovimiento(
            $corte_actual['id'],
            'egreso',
            $concepto,
            $monto,
            $metodo_pago,
            null,
            null,
            $id_usuario,
            $notas
        );
        
        if ($resultado) {
            return [
                'success' => true,
                'mensaje' => 'Egreso registrado correctamente'
            ];
        } else {
            return [
                'success' => false,
                'mensaje' => 'Error al registrar el egreso'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'mensaje' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Registra un movimiento manual de ingreso
 * 
 * @param float $monto Monto del ingreso
 * @param string $concepto Concepto del ingreso
 * @param string $metodo_pago Método de pago
 * @param int $id_usuario ID del usuario
 * @param string $notas Notas adicionales
 * @return array Resultado de la operación
 */
function registrarIngreso($monto, $concepto, $metodo_pago = 'efectivo', $id_usuario = null, $notas = '') {
    global $conn;
    
    try {
        $corteCaja = new CorteCaja($conn);
        
        if (!$corteCaja->hayCajaActiva()) {
            return [
                'success' => false,
                'mensaje' => 'No hay una caja activa. Debe iniciar una caja primero.'
            ];
        }
        
        $corte_actual = $corteCaja->getCorteActual();
        
        $resultado = $corteCaja->registrarMovimiento(
            $corte_actual['id'],
            'ingreso',
            $concepto,
            $monto,
            $metodo_pago,
            null,
            null,
            $id_usuario,
            $notas
        );
        
        if ($resultado) {
            return [
                'success' => true,
                'mensaje' => 'Ingreso registrado correctamente'
            ];
        } else {
            return [
                'success' => false,
                'mensaje' => 'Error al registrar el ingreso'
            ];
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'mensaje' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Verificar si hay una caja activa
 * 
 * @return bool
 */
function hayCajaActiva() {
    global $conn;
    
    try {
        $corteCaja = new CorteCaja($conn);
        return $corteCaja->hayCajaActiva();
    } catch (Exception $e) {
        return false;
    }
}
?>
