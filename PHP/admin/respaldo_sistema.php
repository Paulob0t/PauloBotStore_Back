<?php
/**
 * Script de respaldo automático de base de datos y archivos
 * 
 * Para ejecutar desde línea de comandos:
 * php respaldo_sistema.php
 * 
 * Para ejecutar desde navegador (con autenticación):
 * http://tu-servidor/admin/dist/respaldo_sistema.php
 */

// Configuración
$BACKUP_DIR = __DIR__ . '/../../backups';
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'vending';
$MYSQL_PATH = 'C:/xampp/mysql/bin/mysqldump.exe'; // Ajustar según instalación

// Verificar autenticación si se ejecuta desde navegador
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        die('No autorizado');
    }
}

// Crear directorio de respaldos si no existe
if (!file_exists($BACKUP_DIR)) {
    mkdir($BACKUP_DIR, 0755, true);
}

/**
 * Función para mostrar mensajes
 */
function mensaje($texto, $tipo = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $mensaje = "[$timestamp] [$tipo] $texto";
    
    if (php_sapi_name() === 'cli') {
        echo $mensaje . PHP_EOL;
    } else {
        $class = $tipo === 'error' ? 'danger' : $tipo;
        echo "<div class='alert alert-$class'>$texto</div>";
    }
    
    // Registrar en log
    $log_file = $BACKUP_DIR . '/respaldos.log';
    file_put_contents($log_file, $mensaje . PHP_EOL, FILE_APPEND);
}

/**
 * Realizar respaldo de la base de datos
 */
function respaldarBaseDatos() {
    global $BACKUP_DIR, $DB_HOST, $DB_USER, $DB_PASS, $DB_NAME, $MYSQL_PATH;
    
    $fecha = date('Y-m-d_His');
    $archivo_sql = "$BACKUP_DIR/db_backup_$fecha.sql";
    
    mensaje("Iniciando respaldo de base de datos...");
    
    // Comando mysqldump
    $comando = "\"$MYSQL_PATH\" --host=$DB_HOST --user=$DB_USER";
    if ($DB_PASS) {
        $comando .= " --password=$DB_PASS";
    }
    $comando .= " --add-drop-table --complete-insert --extended-insert";
    $comando .= " $DB_NAME > \"$archivo_sql\"";
    
    exec($comando, $output, $return_var);
    
    if ($return_var === 0 && file_exists($archivo_sql)) {
        $tamano = filesize($archivo_sql);
        $tamano_mb = round($tamano / 1024 / 1024, 2);
        
        // Comprimir el archivo SQL
        $archivo_zip = "$BACKUP_DIR/db_backup_$fecha.zip";
        $zip = new ZipArchive();
        
        if ($zip->open($archivo_zip, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($archivo_sql, basename($archivo_sql));
            $zip->close();
            
            // Eliminar el archivo SQL sin comprimir
            unlink($archivo_sql);
            
            $tamano_zip = filesize($archivo_zip);
            $tamano_zip_mb = round($tamano_zip / 1024 / 1024, 2);
            
            mensaje("✓ Base de datos respaldada: " . basename($archivo_zip) . " ($tamano_zip_mb MB)", 'success');
            return $archivo_zip;
        } else {
            mensaje("✓ Base de datos respaldada: " . basename($archivo_sql) . " ($tamano_mb MB)", 'success');
            return $archivo_sql;
        }
    } else {
        mensaje("✗ Error al respaldar la base de datos", 'error');
        return false;
    }
}

/**
 * Realizar respaldo de archivos del sistema
 */
function respaldarArchivos() {
    global $BACKUP_DIR;
    
    $fecha = date('Y-m-d_His');
    $archivo_zip = "$BACKUP_DIR/files_backup_$fecha.zip";
    $root_dir = realpath(__DIR__ . '/../..');
    
    mensaje("Iniciando respaldo de archivos...");
    
    $zip = new ZipArchive();
    if ($zip->open($archivo_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        mensaje("✗ Error al crear archivo de respaldo", 'error');
        return false;
    }
    
    // Directorios a excluir
    $excluir = [
        'backups',
        'logs',
        'vendor',
        'node_modules',
        '.git',
        '__MACOSX'
    ];
    
    $archivos_agregados = 0;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root_dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        $path = $item->getPathname();
        $relative_path = substr($path, strlen($root_dir) + 1);
        
        // Verificar si el archivo está en un directorio excluido
        $excluir_archivo = false;
        foreach ($excluir as $dir_excluido) {
            if (strpos($relative_path, $dir_excluido) === 0) {
                $excluir_archivo = true;
                break;
            }
        }
        
        if ($excluir_archivo) {
            continue;
        }
        
        if ($item->isFile()) {
            // Excluir archivos grandes (mayores a 10 MB)
            if ($item->getSize() > 10 * 1024 * 1024) {
                continue;
            }
            
            $zip->addFile($path, $relative_path);
            $archivos_agregados++;
        }
    }
    
    $zip->close();
    
    $tamano = filesize($archivo_zip);
    $tamano_mb = round($tamano / 1024 / 1024, 2);
    
    mensaje("✓ Archivos respaldados: " . basename($archivo_zip) . " ($tamano_mb MB, $archivos_agregados archivos)", 'success');
    return $archivo_zip;
}

/**
 * Limpiar respaldos antiguos (más de 30 días)
 */
function limpiarRespaldosAntiguos($dias = 30) {
    global $BACKUP_DIR;
    
    mensaje("Limpiando respaldos antiguos (más de $dias días)...");
    
    $archivos_eliminados = 0;
    $fecha_limite = time() - ($dias * 24 * 60 * 60);
    
    $archivos = glob("$BACKUP_DIR/*.{sql,zip}", GLOB_BRACE);
    
    foreach ($archivos as $archivo) {
        if (filemtime($archivo) < $fecha_limite) {
            unlink($archivo);
            $archivos_eliminados++;
        }
    }
    
    if ($archivos_eliminados > 0) {
        mensaje("✓ Se eliminaron $archivos_eliminados respaldos antiguos", 'success');
    } else {
        mensaje("No hay respaldos antiguos para eliminar", 'info');
    }
}

/**
 * Función principal
 */
function ejecutarRespaldo() {
    mensaje("=== INICIO DE RESPALDO DEL SISTEMA ===", 'info');
    mensaje("Fecha: " . date('Y-m-d H:i:s'));
    
    // Respaldo de base de datos
    $db_backup = respaldarBaseDatos();
    
    // Respaldo de archivos
    $files_backup = respaldarArchivos();
    
    // Limpiar respaldos antiguos
    limpiarRespaldosAntiguos(30);
    
    mensaje("=== FIN DE RESPALDO DEL SISTEMA ===", 'info');
    
    return [
        'db_backup' => $db_backup,
        'files_backup' => $files_backup
    ];
}

// HTML header si se ejecuta desde navegador
if (php_sapi_name() !== 'cli') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Respaldo del Sistema</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    </head>
    <body>
    <div class="container mt-5">
        <h1 class="mb-4">Respaldo del Sistema</h1>
        <div id="resultados">
    <?php
}

// Ejecutar respaldo
$resultado = ejecutarRespaldo();

// HTML footer si se ejecuta desde navegador
if (php_sapi_name() !== 'cli') {
    ?>
        </div>
        <div class="mt-4">
            <a href="cortes_caja.php" class="btn btn-primary">Volver a Cortes de Caja</a>
            <a href="<?php echo basename($BACKUP_DIR); ?>" class="btn btn-success">Ver Respaldos</a>
        </div>
    </div>
    </body>
    </html>
    <?php
}
?>
