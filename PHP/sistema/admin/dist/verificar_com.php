<?php
/**
 * Verificador de configuración COM para PHP
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verificar COM en PHP</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; border-radius: 10px; margin: 10px 0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🔧 Verificador de Configuración COM/PHP</h1>
    
    <div class="box">
        <h2>1. ¿Está COM disponible?</h2>
        <?php
        if (class_exists('COM')) {
            echo '<p class="success">✓ La clase COM está disponible en PHP</p>';
            
            // Intentar crear instancia
            try {
                $shell = new COM("WScript.Shell");
                echo '<p class="success">✓ Se puede crear instancia de WScript.Shell</p>';
                
                // Probar ejecución
                $testCommand = 'cmd /c echo Prueba COM';
                $result = $shell->Exec($testCommand);
                echo '<p class="success">✓ Se pueden ejecutar comandos con COM</p>';
                
            } catch (Exception $e) {
                echo '<p class="error">✗ Error al usar COM: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p class="error">✗ La clase COM NO está disponible</p>';
            echo '<p class="warning">Necesitas habilitar COM en php.ini</p>';
        }
        ?>
    </div>
    
    <div class="box">
        <h2>2. Configuración de PHP</h2>
        <pre><?php
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "PHP SAPI: " . php_sapi_name() . "\n";
        echo "OS: " . PHP_OS . "\n";
        echo "Architecture: " . (PHP_INT_SIZE * 8) . " bits\n";
        echo "php.ini: " . php_ini_loaded_file() . "\n";
        
        $extensions = get_loaded_extensions();
        echo "\nExtensiones cargadas relacionadas con COM:\n";
        foreach ($extensions as $ext) {
            if (stripos($ext, 'com') !== false) {
                echo "  - $ext\n";
            }
        }
        ?></pre>
    </div>
    
    <div class="box">
        <h2>3. Verificar extensión COM en php.ini</h2>
        <?php
        $phpini = php_ini_loaded_file();
        if ($phpini && file_exists($phpini)) {
            $content = file_get_contents($phpini);
            $comEnabled = false;
            
            if (preg_match('/^\s*extension\s*=\s*com_dotnet/m', $content, $matches)) {
                echo '<p class="success">✓ Extensión com_dotnet encontrada en php.ini</p>';
                $comEnabled = true;
            } elseif (preg_match('/^\s*;+\s*extension\s*=\s*com_dotnet/m', $content)) {
                echo '<p class="warning">⚠ Extensión com_dotnet está comentada (deshabilitada)</p>';
                echo '<p>Debes descomentar esta línea en php.ini:</p>';
                echo '<pre>;extension=com_dotnet  →  extension=com_dotnet</pre>';
            } else {
                echo '<p class="warning">⚠ No se encontró extensión com_dotnet en php.ini</p>';
                echo '<p>Agrega esta línea en la sección de extensiones:</p>';
                echo '<pre>extension=com_dotnet</pre>';
            }
            
            if (!$comEnabled) {
                echo '<h3>📝 Pasos para habilitar COM:</h3>';
                echo '<ol>';
                echo '<li>Abre: <code>' . $phpini . '</code></li>';
                echo '<li>Busca la línea: <code>;extension=com_dotnet</code></li>';
                echo '<li>Quita el punto y coma al inicio: <code>extension=com_dotnet</code></li>';
                echo '<li>Guarda el archivo</li>';
                echo '<li>Reinicia Apache desde XAMPP Control Panel</li>';
                echo '</ol>';
            }
        } else {
            echo '<p class="warning">No se pudo localizar php.ini</p>';
        }
        ?>
    </div>
    
    <div class="box">
        <h2>4. Métodos alternativos disponibles</h2>
        <?php
        echo '<p><strong>exec():</strong> ' . (function_exists('exec') ? '<span class="success">✓ Disponible</span>' : '<span class="error">✗ No disponible</span>') . '</p>';
        echo '<p><strong>popen():</strong> ' . (function_exists('popen') ? '<span class="success">✓ Disponible</span>' : '<span class="error">✗ No disponible</span>') . '</p>';
        echo '<p><strong>shell_exec():</strong> ' . (function_exists('shell_exec') ? '<span class="success">✓ Disponible</span>' : '<span class="error">✗ No disponible</span>') . '</p>';
        ?>
    </div>
    
    <div class="box">
        <h2>5. Prueba de inicio del listener</h2>
        <p>Si COM no está disponible, el sistema usará métodos alternativos (BAT + PowerShell)</p>
        <a href="control_listener.php" class="btn">Ir al Control de Listener</a>
        <a href="test_listener.php" class="btn">Diagnóstico Completo</a>
    </div>
    
    <div class="box">
        <h2>💡 Resumen</h2>
        <?php
        if (class_exists('COM')) {
            echo '<p class="success">✓ Sistema listo - COM disponible (método óptimo)</p>';
        } elseif (function_exists('exec')) {
            echo '<p class="warning">⚠ COM no disponible, usando métodos alternativos</p>';
            echo '<p>El sistema funcionará pero se recomienda habilitar COM para mejor rendimiento</p>';
        } else {
            echo '<p class="error">✗ No hay métodos de ejecución disponibles</p>';
            echo '<p>Contacta al administrador del servidor</p>';
        }
        ?>
    </div>
</body>
</html>
