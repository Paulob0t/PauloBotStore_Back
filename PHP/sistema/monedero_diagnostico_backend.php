<?php
/**
 * Backend para el diagnóstico del monedero
 * Lee datos raw del puerto serial
 * 
 * Fecha: 21-01-2026
 */

session_start();
header('Content-Type: application/json');

$accion = $_POST['accion'] ?? '';

switch ($accion) {
    case 'leer':
        leerPuertoSerial();
        break;
    case 'cerrar':
        cerrarPuerto();
        break;
    default:
        echo json_encode(['error' => true, 'mensaje' => 'Acción no válida']);
}

function leerPuertoSerial() {
    $puerto = $_POST['puerto'] ?? 'COM5';
    $baudrate = $_POST['baudrate'] ?? 9600;
    
    // Script PowerShell para leer datos raw - versión más agresiva
    $psScript = <<<POWERSHELL
\$ErrorActionPreference = "Continue"
try {
    \$port = New-Object System.IO.Ports.SerialPort
    \$port.PortName = "$puerto"
    \$port.BaudRate = $baudrate
    \$port.DataBits = 8
    \$port.Parity = [System.IO.Ports.Parity]::None
    \$port.StopBits = [System.IO.Ports.StopBits]::One
    \$port.ReadTimeout = 200
    \$port.WriteTimeout = 200
    \$port.DtrEnable = \$true
    \$port.RtsEnable = \$true
    \$port.Handshake = [System.IO.Ports.Handshake]::None
    
    Write-Host "Intentando abrir puerto $puerto @ $baudrate..."
    \$port.Open()
    Write-Host "Puerto abierto exitosamente"
    
    # Leer por 3 segundos de forma más agresiva
    \$endTime = (Get-Date).AddSeconds(3)
    \$allData = @()
    \$buffer = ""
    
    while ((Get-Date) -lt \$endTime) {
        try {
            # Verificar si hay bytes disponibles
            \$bytesAvailable = \$port.BytesToRead
            
            if (\$bytesAvailable -gt 0) {
                Write-Host "Bytes disponibles: \$bytesAvailable"
                
                # Leer todo lo disponible
                \$data = \$port.ReadExisting()
                \$buffer += \$data
                
                # Si tenemos datos en el buffer, procesarlos
                if (\$buffer.Length -gt 0) {
                    # Convertir a bytes
                    \$bytes = [System.Text.Encoding]::ASCII.GetBytes(\$buffer)
                    \$hexString = [System.BitConverter]::ToString(\$bytes) -replace '-',' '
                    
                    # Crear objeto de salida
                    \$output = @{
                        ascii = \$buffer
                        hex = \$hexString
                        bytes = (\$bytes | ForEach-Object { \$_.ToString() }) -join ' '
                        length = \$buffer.Length
                        timestamp = (Get-Date).ToString("HH:mm:ss.fff")
                    }
                    
                    \$allData += \$output
                    \$buffer = ""
                }
            }
            Start-Sleep -Milliseconds 50
        } catch {
            Write-Host "Error en lectura: \$_"
        }
    }
    
    \$port.Close()
    Write-Host "Puerto cerrado"
    
    # Retornar resultados
    if (\$allData.Count -gt 0) {
        \$json = \$allData | ConvertTo-Json -Compress
        Write-Output "SUCCESS:\$json"
    } else {
        Write-Output "SUCCESS:[]"
    }
} catch {
    Write-Output "ERROR:\$_"
}
POWERSHELL;

    $tempScript = sys_get_temp_dir() . '/diag_' . uniqid() . '.ps1';
    file_put_contents($tempScript, $psScript);
    
    $psCommand = sprintf(
        'powershell.exe -ExecutionPolicy Bypass -File "%s" 2>&1',
        $tempScript
    );
    
    $output = [];
    exec($psCommand, $output);
    @unlink($tempScript);
    
    $outputStr = implode("\n", $output);
    
    if (strpos($outputStr, 'SUCCESS:') === 0) {
        $jsonData = substr($outputStr, 8);
        $datos = json_decode($jsonData, true);
        
        if (!is_array($datos)) {
            $datos = [];
        }
        
        echo json_encode([
            'success' => true,
            'datos' => $datos,
            'count' => count($datos)
        ]);
    } else {
        $error = strpos($outputStr, 'ERROR:') === 0 ? substr($outputStr, 6) : $outputStr;
        echo json_encode([
            'success' => false,
            'error' => $error,
            'datos' => []
        ]);
    }
}

function cerrarPuerto() {
    // Solo confirmar cierre
    echo json_encode(['success' => true]);
}
