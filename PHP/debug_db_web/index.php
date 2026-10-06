<?php
require_once __DIR__ . '/conn.php';

header('Content-Type: text/html; charset=utf-8');

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function is_valid_identifier(string $value): bool
{
    return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value);
}

function qid(string $value): string
{
    return '`' . str_replace('`', '``', $value) . '`';
}

function get_table_list(mysqli $conn): array
{
    $tables = [];
    $result = $conn->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');

    if ($result) {
        while ($row = $result->fetch_array(MYSQLI_NUM)) {
            $tables[] = $row[0];
        }
        $result->free();
    }

    return $tables;
}

function parse_column_lines(string $raw): array
{
    $lines = preg_split('/\R/', $raw) ?: [];
    $columns = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || substr($line, 0, 1) === '#' || substr($line, 0, 2) === '--') {
            continue;
        }
        $columns[] = $line;
    }

    return $columns;
}

function run_sql_script(mysqli $conn, string $sql): array
{
    $sql = trim($sql);

    if ($sql === '') {
        return ['ok' => false, 'message' => 'Pega un script SQL.'];
    }

    if ($conn->multi_query($sql)) {
        do {
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        if ($conn->errno) {
            return ['ok' => false, 'message' => 'No se pudo ejecutar el script: ' . $conn->error];
        }

        return ['ok' => true, 'message' => 'Script ejecutado correctamente.'];
    }

    return ['ok' => false, 'message' => 'No se pudo ejecutar el script: ' . $conn->error];
}

$flash = null;
$selectedTable = $_GET['table'] ?? '';
$sqlDefaults = "CREATE TABLE IF NOT EXISTS Dim_Tiempo (\n    ID_Tiempo INTEGER PRIMARY KEY,\n    Anio INTEGER NOT NULL,\n    Mes INTEGER NOT NULL,\n    Dia INTEGER NOT NULL,\n    Hora INTEGER NOT NULL,\n    Minuto INTEGER NOT NULL,\n    Trimestre TEXT NOT NULL,\n    Es_Fin_Semana INTEGER NOT NULL\n);";

if (!isset($conn) || !$conn) {
    $flash = ['type' => 'error', 'text' => 'No se pudo cargar la conexión de debug_bd_web.'];
}

if ($conn && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'run_sql') {
        $result = run_sql_script($conn, (string) ($_POST['sql_script'] ?? ''));
        $flash = ['type' => $result['ok'] ? 'success' : 'error', 'text' => $result['message']];
    }

    if ($action === 'drop_table') {
        $tableName = trim((string) ($_POST['table_name'] ?? ''));

        if (!is_valid_identifier($tableName) || !in_array($tableName, get_table_list($conn), true)) {
            $flash = ['type' => 'error', 'text' => 'Selecciona una tabla válida para eliminar.'];
        } elseif ($conn->query('DROP TABLE ' . qid($tableName))) {
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?dropped=1');
            exit;
        } else {
            $flash = ['type' => 'error', 'text' => 'No se pudo eliminar la tabla: ' . $conn->error];
        }
    }
}

$tables = [];
$tableColumns = [];
$tableRows = [];
$tableCount = 0;
$currentTable = '';

if ($conn) {
    $tables = get_table_list($conn);

    if ($selectedTable !== '' && in_array($selectedTable, $tables, true)) {
        $currentTable = $selectedTable;
    } elseif ($tables) {
        $currentTable = $tables[0];
    }

    if (isset($_GET['dropped']) && $_GET['dropped'] === '1') {
        $flash = ['type' => 'success', 'text' => 'Tabla eliminada correctamente.'];
    }

    if ($currentTable !== '') {
        $columnsResult = $conn->query('SHOW COLUMNS FROM ' . qid($currentTable));
        if ($columnsResult) {
            while ($row = $columnsResult->fetch_assoc()) {
                $tableColumns[] = $row;
            }
            $columnsResult->free();
        }

        $countResult = $conn->query('SELECT COUNT(*) AS total FROM ' . qid($currentTable));
        if ($countResult && ($row = $countResult->fetch_assoc())) {
            $tableCount = (int) $row['total'];
            $countResult->free();
        }

        $rowsResult = $conn->query('SELECT * FROM ' . qid($currentTable));
        if ($rowsResult) {
            while ($row = $rowsResult->fetch_assoc()) {
                $tableRows[] = $row;
            }
            $rowsResult->free();
        }
    }
}

if ($conn && isset($_GET['ajax']) && $_GET['ajax'] === '1' && $currentTable !== '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['count' => $tableCount, 'rows' => $tableRows]);
    exit;
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Depurador BD WEB</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #0f172a;
            --panel: #111827;
            --panel-soft: #1f2937;
            --text: #e5e7eb;
            --muted: #94a3b8;
            --line: rgba(148, 163, 184, 0.2);
            --accent: #22c55e;
            --accent-2: #38bdf8;
            --danger: #fb7185;
            --warning: #f59e0b;
            --ok-bg: rgba(34, 197, 94, 0.12);
            --danger-bg: rgba(251, 113, 133, 0.12);
            --warning-bg: rgba(245, 158, 11, 0.12);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(56, 189, 248, 0.2), transparent 30%),
                radial-gradient(circle at top right, rgba(34, 197, 94, 0.18), transparent 25%),
                linear-gradient(180deg, #020617 0%, #0f172a 100%);
            color: var(--text);
        }

        .wrap {
            max-width: 1400px;
            margin: 0 auto;
            padding: 28px 18px 40px;
        }

        .hero {
            display: grid;
            grid-template-columns: 1.3fr 0.7fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .card {
            background: rgba(15, 23, 42, 0.85);
            border: 1px solid var(--line);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(2, 6, 23, 0.35);
            backdrop-filter: blur(10px);
        }

        .card-body { padding: 18px; }
        h1, h2, h3 { margin: 0 0 12px; }
        h1 { font-size: 30px; }
        h2 { font-size: 20px; }
        p { color: var(--muted); line-height: 1.5; }
        .muted { color: var(--muted); }
        .grid { display: grid; gap: 16px; }
        .grid-2 { grid-template-columns: 1fr 1fr; }
        .grid-3 { grid-template-columns: 1.2fr 0.8fr 1fr; }

        .meta {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            margin-top: 14px;
        }

        .pill {
            padding: 12px 14px;
            border-radius: 14px;
            background: var(--panel-soft);
            border: 1px solid var(--line);
        }

        .pill strong { display: block; margin-top: 4px; color: #fff; }
        .alert {
            border-radius: 14px;
            padding: 12px 14px;
            margin: 0 0 16px;
            border: 1px solid var(--line);
        }
        .alert.success { background: var(--ok-bg); color: #bbf7d0; }
        .alert.error { background: var(--danger-bg); color: #fecdd3; }
        .alert.warning { background: var(--warning-bg); color: #fde68a; }

        label { display: block; margin: 0 0 8px; color: #fff; font-weight: 700; }
        input, select, textarea, button {
            width: 100%;
            border-radius: 12px;
            border: 1px solid var(--line);
            background: #0b1220;
            color: var(--text);
            padding: 12px 14px;
            font-size: 14px;
        }
        textarea { min-height: 170px; resize: vertical; font-family: Consolas, Monaco, monospace; }
        button {
            background: linear-gradient(135deg, #16a34a, #0ea5e9);
            border: none;
            color: white;
            font-weight: 700;
            cursor: pointer;
        }
        button:hover { filter: brightness(1.05); }

        .table-wrap { overflow: auto; border-radius: 14px; border: 1px solid var(--line); }
        table { width: 100%; border-collapse: collapse; min-width: 700px; }
        th, td { padding: 10px 12px; border-bottom: 1px solid var(--line); text-align: left; vertical-align: top; }
        th { background: rgba(15, 23, 42, 0.95); color: #fff; position: sticky; top: 0; z-index: 1; }
        tr:hover td { background: rgba(56, 189, 248, 0.06); }
        code { background: rgba(148, 163, 184, 0.12); padding: 2px 6px; border-radius: 6px; color: #fff; }
        .small { font-size: 13px; }
        .divider { height: 1px; background: var(--line); margin: 16px 0; }
        .btn-link {
            display: inline-block;
            width: auto;
            text-decoration: none;
            text-align: center;
            padding: 10px 14px;
            background: #1e293b;
            border: 1px solid var(--line);
            color: #fff;
            border-radius: 12px;
        }

        @media (max-width: 980px) {
            .hero, .grid-2, .grid-3, .meta { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="hero">
            <div class="card">
                <div class="card-body">
                    <h1>Depurador de base de datos</h1>
                    <p>Panel para ejecutar scripts SQL, ver tablas y datos, y eliminar tablas innecesarias.</p>
                    <div class="meta">
                        <div class="pill">Base de datos<strong>colegos_Ecosystem_web</strong></div>
                        <div class="pill">Usuario<strong>colegos_admin</strong></div>
                        <div class="pill">Charset<strong>utf8mb4</strong></div>
                        <div class="pill">Ruta<strong>/debug_bd_web/</strong></div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <h2>Estado</h2>
                    <?php if ($conn): ?>
                        <div class="alert success">Conexión activa.</div>
                        <p class="small">Servidor: <code><?php echo h($conn->host_info); ?></code></p>
                        <p class="small">Tablas detectadas: <code><?php echo count($tables); ?></code></p>
                    <?php else: ?>
                        <div class="alert error">No se pudo abrir la conexión.</div>
                    <?php endif; ?>
                    <p class="small muted">Si la BD no responde, revisa host, permisos del usuario y acceso MySQL remoto.</p>
                </div>
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="alert <?php echo h($flash['type']); ?>"><?php echo h($flash['text']); ?></div>
        <?php endif; ?>

        <div class="card" style="margin-bottom: 16px;">
            <div class="card-body">
                <h2>Ejecutar script SQL</h2>
                <form method="post">
                    <input type="hidden" name="action" value="run_sql">
                    <label for="sql_script">Script SQL</label>
                    <textarea id="sql_script" name="sql_script" placeholder="<?php echo h($sqlDefaults); ?>"><?php echo h($sqlDefaults); ?></textarea>
                    <div style="margin-top: 12px;">
                        <button type="submit">Ejecutar script</button>
                    </div>
                </form>
                <p class="small muted" style="margin-top: 10px;">Si tu script viene de SQLite, cambia <code>AUTOINCREMENT</code> por <code>AUTO_INCREMENT</code> cuando lo uses en MySQL.</p>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card">
                <div class="card-body">
                    <h2>Tablas existentes</h2>
                    <?php if ($tables): ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Tabla</th>
                                        <th>Ver</th>
                                        <th>Eliminar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tables as $table): ?>
                                        <tr>
                                            <td><code><?php echo h($table); ?></code></td>
                                            <td><a class="btn-link" href="?table=<?php echo rawurlencode($table); ?>">Abrir</a></td>
                                            <td>
                                                <form method="post" onsubmit="return confirm('¿Seguro que quieres eliminar esta tabla?');" style="margin: 0;">
                                                    <input type="hidden" name="action" value="drop_table">
                                                    <input type="hidden" name="table_name" value="<?php echo h($table); ?>">
                                                    <button type="submit" style="background: linear-gradient(135deg, #ef4444, #f59e0b);">Borrar</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert warning">No hay tablas en esta base de datos todavía.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2>Tabla activa</h2>
                    <form method="get">
                        <label for="table">Seleccionar tabla</label>
                        <select id="table" name="table" onchange="this.form.submit()">
                            <option value="">-- Elige una tabla --</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo h($table); ?>" <?php echo $table === $currentTable ? 'selected' : ''; ?>><?php echo h($table); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php if ($currentTable !== ''): ?>
                        <p class="small muted" style="margin-top: 10px;">Seleccionada: <code><?php echo h($currentTable); ?></code></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 16px;">
            <div class="card-body">
                <div class="grid grid-3">
                    <div>
                        <h2>Tabla activa</h2>
                        <p style="margin: 0; color: #fff; font-weight: 700;"><?php echo $currentTable !== '' ? h($currentTable) : 'Ninguna'; ?></p>
                    </div>
                    <div>
                        <h2>Registros</h2>
                        <p id="record-count" style="font-size: 28px; margin: 0; color: #fff; font-weight: 700;"><?php echo $currentTable !== '' ? number_format($tableCount) : '0'; ?></p>
                    </div>
                    <div>
                        <h2>Columnas</h2>
                        <p style="font-size: 28px; margin: 0; color: #fff; font-weight: 700;"><?php echo $currentTable !== '' ? number_format(count($tableColumns)) : '0'; ?></p>
                    </div>
                </div>

                <div class="divider"></div>

                <?php if ($currentTable !== ''): ?>
                    <h3>Estructura de <?php echo h($currentTable); ?></h3>
                    <div class="table-wrap" style="margin-bottom: 16px;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Campo</th>
                                    <th>Tipo</th>
                                    <th>Nulo</th>
                                    <th>Llave</th>
                                    <th>Predeterminado</th>
                                    <th>Extra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tableColumns as $column): ?>
                                    <tr>
                                        <td><code><?php echo h($column['Field']); ?></code></td>
                                        <td><?php echo h($column['Type']); ?></td>
                                        <td><?php echo h($column['Null']); ?></td>
                                        <td><?php echo h($column['Key']); ?></td>
                                        <td><?php echo h((string) ($column['Default'] ?? 'NULL')); ?></td>
                                        <td><?php echo h($column['Extra']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <h3>Registros de <?php echo h($currentTable); ?> <span class="muted">(máx. 100)</span></h3>
                    <?php if ($tableRows): ?>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <?php foreach (array_keys($tableRows[0]) as $field): ?>
                                            <th><?php echo h($field); ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody id="record-table-body">
                                    <?php foreach ($tableRows as $row): ?>
                                        <tr>
                                            <?php foreach ($row as $value): ?>
                                                <td><?php echo h((string) ($value ?? 'NULL')); ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert warning">Esta tabla no tiene registros todavía.</div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert warning">Selecciona una tabla para ver su estructura y sus registros.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const currentTable = <?php echo json_encode($currentTable); ?>;
        if (currentTable) {
            function escapeHtml(unsafe) {
                if (unsafe === null) return 'NULL';
                return (unsafe || "").toString()
                     .replace(/&/g, "&amp;")
                     .replace(/</g, "&lt;")
                     .replace(/>/g, "&gt;")
                     .replace(/"/g, "&quot;")
                     .replace(/'/g, "&#039;");
            }

            setInterval(() => {
                fetch('?table=' + encodeURIComponent(currentTable) + '&ajax=1')
                    .then(r => r.json())
                    .then(data => {
                        const countEl = document.getElementById('record-count');
                        if (countEl && data.count !== undefined) {
                            countEl.innerText = new Intl.NumberFormat('en-US').format(data.count);
                        }

                        const tbody = document.getElementById('record-table-body');
                        if (tbody && data.rows) {
                            let html = '';
                            data.rows.forEach(row => {
                                html += '<tr>';
                                Object.values(row).forEach(val => {
                                    html += '<td>' + escapeHtml(val) + '</td>';
                                });
                                html += '</tr>';
                            });
                            tbody.innerHTML = html;
                        }
                    })
                    .catch(e => console.error('Error actualizando datos:', e));
            }, 1000); // Actualiza cada segundo
        }
    </script>
</body>
</html>
