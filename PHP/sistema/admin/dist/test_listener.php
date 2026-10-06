<?php
/**
 * Script de prueba para diagnosticar problemas con el listener
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Diagnóstico de Listener</h1>";
echo "<pre>";

// 1. Verificar rutas
echo "=== VERIFICACIÓN DE RUTAS ===\n\n";

$directorioBase = dirname(dirname(__DIR__));
echo "Directorio base: $directorioBase\n";

$phpExe = 'C:\\xampp\\php\\php.exe';
echo "PHP ejecutable: $phpExe\n";
echo "¿Existe PHP? " . (file_exists($phpExe) ? "✓ SÍ" : "✗ NO") . "\n\n";

$listenerScript = $directorioBase . '\\com5_manager.php';
echo "Script listener: $listenerScript\n";
echo "¿Existe script? " . (file_exists($listenerScript) ? "✓ SÍ" : "✗ NO") . "\n\n";

// 2. Verificar procesos actuales
echo "=== PROCESOS PHP ACTUALES ===\n\n";

$comando = 'wmic process where "name=\'php.exe\'" get processid,commandline 2>&1';
exec($comando, $output);
foreach ($output as $linea) {
    if (trim($linea) && stripos($linea, 'commandline') === false) {
        echo $linea . "\n";
    }
}

echo "\n=== VERIFICACIÓN CON TASKLIST ===\n\n";
$comando2 = 'tasklist /FI "IMAGENAME eq php.exe" /V 2>&1';
exec($comando2, $output2);
foreach ($output2 as $linea) {
    echo $linea . "\n";
}

// 3. Intentar iniciar manualmente
echo "\n=== PRUEBA DE INICIO MANUAL ===\n\n";

$comando = 'start /B "" "' . $phpExe . '" "' . $listenerScript . '" 2>&1';
echo "Comando a ejecutar:\n$comando\n\n";

echo "Intentando iniciar...\n";
$output3 = [];
exec($comando, $output3, $returnVar);
echo "Código de retorno: $returnVar\n";
echo "Salida:\n";
print_r($output3);

sleep(2);

echo "\n=== VERIFICACIÓN POST-INICIO ===\n\n";
$comando4 = 'wmic process where "name=\'php.exe\'" get commandline 2>&1';
exec($comando4, $output4);
$encontrado = false;
foreach ($output4 as $linea) {
    if (stripos($linea, 'com5_manager') !== false) {
        echo "✓ ENCONTRADO: $linea\n";
        $encontrado = true;
    }
}

if (!$encontrado) {
    echo "✗ No se encontró el proceso del listener\n";
    echo "\nPosibles causas:\n";
    echo "1. PHP no está en la ruta C:\\xampp\\php\\php.exe\n";
    echo "2. El script com5_manager.php tiene errores\n";
    echo "3. Falta alguna extensión de PHP\n";
    echo "4. Permisos insuficientes\n";
}

// 4. Información del sistema
echo "\n=== INFORMACIÓN DEL SISTEMA ===\n\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS . "\n";
echo "Usuario web: " . get_current_user() . "\n";
echo "Directorio actual: " . getcwd() . "\n";
echo "exec() disponible: " . (function_exists('exec') ? "✓ SÍ" : "✗ NO") . "\n";
echo "popen() disponible: " . (function_exists('popen') ? "✓ SÍ" : "✗ NO") . "\n";

// 5. Ver si hay logs
echo "\n=== LOGS DEL LISTENER ===\n\n";
$logFile = $directorioBase . '\\admin\\dist\\logs\\monedero_listener.log';
echo "Archivo de log: $logFile\n";
if (file_exists($logFile)) {
    echo "Últimas 20 líneas:\n";
    $lines = file($logFile);
    $lastLines = array_slice($lines, -20);
    foreach ($lastLines as $line) {
        echo htmlspecialchars($line);
    }
} else {
    echo "No existe el archivo de log\n";
}

echo "</pre>";

echo "<hr>";
echo "<a href='control_listener.php'>← Volver al Control de Listener</a>";
?>
