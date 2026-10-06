<?php
/**
 * Archivo para enviar comandos al Arduino
 * Este archivo simula el envío de datos al Arduino
 * Cuando se conecte el Arduino real, se debe implementar la comunicación serial o HTTP
 * 
 * Fecha: 16-10-2025
 */

header('Content-Type: application/json');

/**
 * Envía datos de despacho al Arduino
 * 
 * @param array $despacho Datos del despacho (SKU, cantidad, ubicación, id_pago)
 * @return array Resultado del envío
 */
function enviarAlArduino($despacho) {
    // TODO: Implementar comunicación real con Arduino
    // Opciones:
    // 1. Puerto Serial (PHP Serial Extension)
    // 2. HTTP Request (si Arduino tiene servidor web)
    // 3. MQTT (protocolo de mensajería)
    // 4. WebSocket
    
    // Por ahora, simulamos el envío
    $comando = [
        'action' => 'DISPENSAR',
        'ubicacion' => $despacho['ubicacion'],
        'cantidad' => $despacho['cantidad'],
        'sku' => $despacho['sku'],
        'id_pago' => $despacho['id_pago'],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Log del comando para debugging
    error_log("ARDUINO CMD: " . json_encode($comando));
    
    // Simulación de respuesta del Arduino
    // En producción, aquí se enviaría el comando real y se esperaría la respuesta
    
    // Simular 90% de éxito
    $exito = (rand(1, 10) <= 9);
    
    if ($exito) {
        return [
            'success' => true,
            'mensaje' => 'Comando enviado al Arduino exitosamente',
            'respuesta_arduino' => [
                'status' => 'OK',
                'ubicacion' => $despacho['ubicacion'],
                'dispensado' => true,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
    } else {
        return [
            'success' => false,
            'mensaje' => 'Error al comunicarse con Arduino',
            'respuesta_arduino' => [
                'status' => 'ERROR',
                'error_code' => 'COMM_ERROR',
                'mensaje' => 'Timeout de comunicación'
            ]
        ];
    }
}

/**
 * Ejemplo de implementación con puerto serial (comentado)
 */
/*
function enviarAlArduinoPuertoSerial($despacho) {
    // Requiere: php-serial library o exec() con comandos del sistema
    
    $puerto = "COM3"; // Windows: COM3, Linux: /dev/ttyUSB0
    $baudRate = 9600;
    
    // Formato del comando: UBICACION:CANTIDAD
    // Ejemplo: A1:2 (despachar 2 productos de la ubicación A1)
    $comando = $despacho['ubicacion'] . ':' . $despacho['cantidad'] . "\n";
    
    try {
        // En Windows
        // exec("mode $puerto BAUD=$baudRate PARITY=N data=8 stop=1");
        // $fp = fopen($puerto, "w+");
        // fwrite($fp, $comando);
        // $respuesta = fread($fp, 100);
        // fclose($fp);
        
        // En Linux
        // exec("stty -F $puerto $baudRate");
        // $fp = fopen($puerto, "w+");
        // fwrite($fp, $comando);
        // $respuesta = fread($fp, 100);
        // fclose($fp);
        
        return [
            'success' => true,
            'respuesta_arduino' => $respuesta
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'mensaje' => $e->getMessage()
        ];
    }
}
*/

/**
 * Ejemplo de implementación con HTTP (si Arduino tiene WiFi/Ethernet)
 */
/*
function enviarAlArduinoHTTP($despacho) {
    $arduino_ip = "192.168.1.100"; // IP del Arduino
    $arduino_port = 80;
    
    $url = "http://$arduino_ip:$arduino_port/dispensar";
    
    $data = [
        'ubicacion' => $despacho['ubicacion'],
        'cantidad' => $despacho['cantidad'],
        'sku' => $despacho['sku']
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $respuesta = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return [
            'success' => true,
            'respuesta_arduino' => json_decode($respuesta, true)
        ];
    } else {
        return [
            'success' => false,
            'mensaje' => 'Error HTTP: ' . $httpCode
        ];
    }
}
*/

// Si se llama directamente este archivo (para pruebas)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !defined('INCLUDED_FROM_PROCESAR_VENTA')) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['ubicacion']) || !isset($input['cantidad'])) {
        echo json_encode([
            'success' => false,
            'error' => true,
            'mensaje' => 'Datos incompletos: se requiere ubicacion y cantidad'
        ]);
        exit;
    }
    
    $resultado = enviarAlArduino($input);
    echo json_encode($resultado);
}
?>
