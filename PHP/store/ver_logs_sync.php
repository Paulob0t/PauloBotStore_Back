<?php
/**
 * Visor simple de logs de sincronizacion (local y nube).
 */

$logsDir = __DIR__ . '/logs';
$defaultLines = 200;
$maxLines = 2000;

$availableLogs = [];
if (is_dir($logsDir)) {
    $items = scandir($logsDir);
    foreach ($items as $item) {
        $fullPath = $logsDir . DIRECTORY_SEPARATOR . $item;
        if (!is_file($fullPath)) {
            continue;
        }

        if (preg_match('/^sync_(local|nube)_.*\.log$/i', $item)) {
            $availableLogs[] = $item;
        }
    }
}

sort($availableLogs);

$selectedLog = $_GET['log'] ?? ($availableLogs[0] ?? '');
if (!in_array($selectedLog, $availableLogs, true)) {
    $selectedLog = $availableLogs[0] ?? '';
}

$linesRequested = isset($_GET['lines']) ? (int)$_GET['lines'] : $defaultLines;
if ($linesRequested < 20) {
    $linesRequested = 20;
}
if ($linesRequested > $maxLines) {
    $linesRequested = $maxLines;
}

$autoRefresh = isset($_GET['refresh']) ? (int)$_GET['refresh'] : 5;
if ($autoRefresh < 0) {
    $autoRefresh = 0;
}
if ($autoRefresh > 60) {
    $autoRefresh = 60;
}

$logContent = '';
$error = '';
$selectedPath = '';

if ($selectedLog !== '') {
    $selectedPath = realpath($logsDir . DIRECTORY_SEPARATOR . $selectedLog);
    $basePath = realpath($logsDir);

    if ($selectedPath === false || $basePath === false || strpos($selectedPath, $basePath) !== 0) {
        $error = 'Ruta de log invalida.';
    } elseif (!is_readable($selectedPath)) {
        $error = 'No se puede leer el archivo seleccionado.';
    } else {
        $lines = @file($selectedPath, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            $error = 'No se pudo abrir el archivo de log.';
        } else {
            $slice = array_slice($lines, -$linesRequested);
            $slice = array_reverse($slice);
            $logContent = implode("\n", $slice);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs de sincronizacion</title>
    <style>
        body {
            margin: 0;
            background: #111827;
            color: #e5e7eb;
            font-family: Consolas, "Courier New", monospace;
        }
        .wrap {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 16px;
        }
        h1 {
            margin: 0 0 12px;
            font-size: 24px;
        }
        .panel {
            background: #1f2937;
            border: 1px solid #374151;
            border-radius: 10px;
            padding: 14px;
            margin-bottom: 14px;
        }
        label {
            display: inline-block;
            margin-right: 8px;
            color: #93c5fd;
        }
        select, input, button {
            background: #111827;
            color: #f3f4f6;
            border: 1px solid #4b5563;
            border-radius: 6px;
            padding: 7px 10px;
            margin: 4px 8px 4px 0;
            font-family: inherit;
        }
        button {
            cursor: pointer;
        }
        .meta {
            color: #9ca3af;
            font-size: 13px;
            margin-top: 8px;
        }
        .error {
            color: #fca5a5;
            font-weight: bold;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word;
            line-height: 1.4;
            font-size: 13px;
            max-height: 68vh;
            overflow: auto;
        }
        .ok {
            color: #86efac;
        }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Visor de logs de sincronizacion</h1>

    <form class="panel" method="get">
        <label for="log">Archivo</label>
        <select id="log" name="log">
            <?php if (empty($availableLogs)): ?>
                <option value="">No hay logs sync_*.log</option>
            <?php else: ?>
                <?php foreach ($availableLogs as $logName): ?>
                    <option value="<?php echo htmlspecialchars($logName, ENT_QUOTES, 'UTF-8'); ?>" <?php echo ($logName === $selectedLog) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($logName, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <label for="lines">Lineas</label>
        <input id="lines" name="lines" type="number" min="20" max="2000" value="<?php echo (int)$linesRequested; ?>">

        <label for="refresh">Auto refresh (seg)</label>
        <input id="refresh" name="refresh" type="number" min="0" max="60" value="<?php echo (int)$autoRefresh; ?>">

        <button type="submit">Actualizar</button>
    </form>

    <div class="panel">
        <?php if ($error !== ''): ?>
            <div class="error">Error: <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php elseif ($selectedLog === ''): ?>
            <div class="error">No se encontraron archivos de sincronizacion en la carpeta logs.</div>
        <?php else: ?>
            <div class="ok">Mostrando: <?php echo htmlspecialchars($selectedLog, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="meta">Ruta: <?php echo htmlspecialchars((string)$selectedPath, ENT_QUOTES, 'UTF-8'); ?></div>
            <div class="meta">Hora servidor: <?php echo date('Y-m-d H:i:s'); ?></div>
            <hr>
            <pre><?php echo htmlspecialchars($logContent, ENT_QUOTES, 'UTF-8'); ?></pre>
        <?php endif; ?>
    </div>
</div>

<?php if ($autoRefresh > 0): ?>
<script>
setTimeout(function () {
    window.location.reload();
}, <?php echo (int)$autoRefresh * 1000; ?>);
</script>
<?php endif; ?>
</body>
</html>
