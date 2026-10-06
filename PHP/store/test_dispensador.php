<?php
// Datos del inventario para mostrar estado inicial
$inventoryFile = __DIR__ . '/admin/dist/logs/coin_inventory.log';
$saldoFile     = __DIR__ . '/admin/dist/logs/saldo_actual.json';
$logFile       = __DIR__ . '/admin/dist/logs/monedero_listener.log';
$queueFile     = __DIR__ . '/admin/dist/logs/monedero_dispense_queue.json';
$responseFile  = __DIR__ . '/admin/dist/logs/monedero_dispense_response.json';

$inventory = file_exists($inventoryFile) ? json_decode(file_get_contents($inventoryFile), true) : null;
$saldoData = file_exists($saldoFile)     ? json_decode(file_get_contents($saldoFile), true)     : null;
$saldo     = $saldoData['saldo'] ?? 0;
$listenerActivo = file_exists($logFile) && (time() - filemtime($logFile)) < 120;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test Dispensador - VendingBox</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #0d0d0d; color: #e0e0e0; font-family: 'Courier New', monospace; min-height: 100vh; }

  header { background: #1a1a2e; border-bottom: 2px solid #f0c040; padding: 18px 30px; display: flex; align-items: center; gap: 15px; }
  header h1 { font-size: 1.4rem; color: #f0c040; letter-spacing: 2px; }
  .badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; }
  .badge-on  { background: #1a4a1a; color: #4caf50; border: 1px solid #4caf50; }
  .badge-off { background: #4a1a1a; color: #e53935; border: 1px solid #e53935; }

  .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 25px; max-width: 1100px; margin: 0 auto; }
  @media(max-width:750px){ .grid { grid-template-columns: 1fr; } }

  .card { background: #1a1a2e; border: 1px solid #2a2a4a; border-radius: 10px; padding: 20px; }
  .card h2 { font-size: 0.85rem; color: #888; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 16px; border-bottom: 1px solid #2a2a4a; padding-bottom: 10px; }

  .saldo-display { font-size: 3rem; color: #f0c040; text-align: center; padding: 15px 0; font-weight: bold; }

  .inv-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #1f1f3a; }
  .inv-row:last-child { border-bottom: none; }
  .inv-denom { color: #f0c040; font-size: 1.1rem; font-weight: bold; min-width: 60px; }
  .inv-bar-wrap { flex: 1; margin: 0 12px; background: #0d0d1a; border-radius: 4px; height: 8px; overflow: hidden; }
  .inv-bar { height: 100%; background: #3a7bd5; border-radius: 4px; transition: width .4s; }
  .inv-cant { color: #aaa; font-size: 0.9rem; min-width: 55px; text-align: right; }

  .form-group { margin-bottom: 14px; }
  label { display: block; font-size: 0.8rem; color: #888; margin-bottom: 5px; }
  input[type=number] { width: 100%; padding: 10px 14px; background: #0d0d1a; border: 1px solid #3a3a6a; border-radius: 6px; color: #e0e0e0; font-size: 1.1rem; font-family: inherit; }
  input[type=number]:focus { outline: none; border-color: #f0c040; }

  .btn { width: 100%; padding: 12px; border: none; border-radius: 6px; font-size: 1rem; font-weight: bold; cursor: pointer; font-family: inherit; transition: opacity .2s; }
  .btn:hover { opacity: 0.85; }
  .btn:disabled { opacity: 0.4; cursor: not-allowed; }
  .btn-dispense { background: #e65100; color: #fff; }
  .btn-calc    { background: #1565c0; color: #fff; }
  .btn-reload  { background: #2e7d32; color: #fff; margin-top: 8px; }

  .result-box { margin-top: 14px; padding: 14px; border-radius: 6px; font-size: 0.9rem; line-height: 1.7; display: none; white-space: pre-wrap; }
  .result-ok   { background: #0a2a0a; border: 1px solid #4caf50; color: #81c784; }
  .result-err  { background: #2a0a0a; border: 1px solid #e53935; color: #ef9a9a; }
  .result-info { background: #0a1a2a; border: 1px solid #3a7bd5; color: #90caf9; }
  .result-wait { background: #1a1a0a; border: 1px solid #f0c040; color: #fff176; }

  .desglose { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
  .chip { padding: 5px 12px; border-radius: 20px; background: #0d0d1a; border: 1px solid #3a7bd5; font-size: 0.8rem; color: #90caf9; }

  #logContainer { font-size: 0.72rem; background: #0a0a14; border-radius: 6px; padding: 12px; height: 220px; overflow-y: auto; line-height: 1.6; color: #64b5f6; border: 1px solid #1a1a3a; }
  #logContainer .log-ok  { color: #81c784; }
  #logContainer .log-err { color: #ef9a9a; }
  #logContainer .log-warn{ color: #fff176; }
  #logContainer .log-coin{ color: #f0c040; }

  .spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid #f0c040; border-top-color: transparent; border-radius: 50%; animation: spin .7s linear infinite; vertical-align: middle; margin-right: 6px; }
  @keyframes spin { to { transform: rotate(360deg); } }

  .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; margin-right: 6px; }
  .dot-on  { background: #4caf50; box-shadow: 0 0 6px #4caf50; animation: pulse 2s infinite; }
  .dot-off { background: #e53935; }
  @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }

  .coins-quick { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; margin-top: 8px; }
  .btn-quick { padding: 8px 4px; border: 1px solid #3a3a6a; border-radius: 6px; background: #0d0d1a; color: #f0c040; cursor: pointer; font-size: 0.85rem; font-weight: bold; transition: all .2s; }
  .btn-quick:hover { background: #1a1a3a; border-color: #f0c040; }
</style>
</head>
<body>

<header>
  <span class="status-dot <?= $listenerActivo ? 'dot-on' : 'dot-off' ?>"></span>
  <h1>💰 TEST DISPENSADOR</h1>
  <span class="badge <?= $listenerActivo ? 'badge-on' : 'badge-off' ?>">
    <?= $listenerActivo ? 'LISTENER ACTIVO' : 'LISTENER INACTIVO' ?>
  </span>
  <span style="margin-left:auto;font-size:.75rem;color:#555">
    <?= date('H:i:s') ?>
  </span>
</header>

<div class="grid">

  <!-- SALDO CLIENTE -->
  <div class="card">
    <h2>👤 Saldo del Cliente</h2>
    <div class="saldo-display" id="saldoDisplay">$<?= number_format($saldo, 2) ?></div>
    <div style="text-align:center;font-size:.75rem;color:#888;margin-top:4px">
      Dinero insertado · disponible para comprar
    </div>
  </div>

  <!-- INVENTARIO -->
  <div class="card">
    <h2>🪙 Inventario de Monedas</h2>
    <?php
    $denoms = [20, 10, 5, 2, 1];
    $denominaciones = $inventory['denominaciones'] ?? [];
    $maxCant = max(array_values($denominaciones) ?: [1]);
    foreach ($denoms as $d):
      $cant = (int)($denominaciones[(string)$d] ?? 0);
      $pct  = $maxCant > 0 ? round($cant / $maxCant * 100) : 0;
    ?>
    <div class="inv-row">
      <span class="inv-denom">$<?= $d ?></span>
      <div class="inv-bar-wrap"><div class="inv-bar" style="width:<?= $pct ?>%" id="bar-<?= $d ?>"></div></div>
      <span class="inv-cant" id="cant-<?= $d ?>"><?= $cant ?> monedas</span>
    </div>
    <?php endforeach; ?>
    <div style="margin-top:12px;font-size:.85rem;color:#f0c040;text-align:right;font-weight:bold">
      Saldo máquina (para cambio): $<span id="totalInventario"><?= $inventory['total_pesos'] ?? 0 ?></span>
    </div>
  </div>

  <!-- DISPENSAR -->
  <div class="card">
    <h2>⚡ Dispensar Cambio</h2>
    <div class="form-group">
      <label>MONTO A DISPENSAR ($)</label>
      <input type="number" id="montoInput" value="10" min="1" max="500" step="1">
    </div>
    <div style="margin-bottom:14px">
      <div style="font-size:.75rem;color:#555;margin-bottom:6px">MONTOS RÁPIDOS</div>
      <div class="coins-quick">
        <?php foreach ([1, 2, 5, 10, 20, 50, 100, 200] as $m): ?>
        <button class="btn-quick" onclick="document.getElementById('montoInput').value=<?= $m ?>"><?= $m ?></button>
        <?php endforeach; ?>
      </div>
    </div>
    <button class="btn btn-calc" onclick="calcularDesglose()">🧮 VER DESGLOSE</button>
    <br><br>
    <button class="btn btn-dispense" onclick="dispensar()" id="btnDispense" <?= !$listenerActivo ? 'disabled' : '' ?>>
      💸 DISPENSAR AHORA
    </button>
    <div id="resultBox" class="result-box"></div>
  </div>

  <!-- LOGS EN TIEMPO REAL -->
  <div class="card" style="display:flex;flex-direction:column">
    <h2>📋 Logs en Tiempo Real</h2>
    <div id="logContainer">Cargando logs...</div>
    <button class="btn btn-reload" onclick="refreshLogs()">↺ Actualizar Logs</button>
  </div>

</div>

<!-- Sección cargar inventario -->
<div style="max-width:1100px;margin:0 auto;padding:0 25px 20px">
  <div class="card">
    <h2>📦 Cargar Inventario Inicial</h2>
    <p style="font-size:.8rem;color:#888;margin-bottom:16px">
      Ingresa cuántas monedas de cada denominación hay físicamente en la máquina.<br>
      Esto permite al sistema calcular el cambio correcto.
    </p>
    <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px" id="invInputs">
      <?php foreach ([1,2,5,10,20] as $d):
        $cant = (int)($inventory['denominaciones'][(string)$d] ?? 0);
      ?>
      <div>
        <label style="color:#f0c040;font-size:.85rem;font-weight:bold">$<?= $d ?></label>
        <input type="number" id="inv_<?= $d ?>" value="<?= $cant ?>" min="0"
          style="width:100%;padding:8px;background:#0d0d1a;border:1px solid #3a3a6a;border-radius:6px;color:#e0e0e0;font-size:1rem;margin-top:4px">
      </div>
      <?php endforeach; ?>
    </div>
    <button class="btn btn-calc" style="margin-top:14px" onclick="cargarInventario()">💾 GUARDAR INVENTARIO</button>
    <div id="invResult" class="result-box"></div>
  </div>
</div>

<!-- Sección cola/respuesta -->
<!-- Sección diagnóstico de tubos -->
<div style="max-width:1100px;margin:0 auto;padding:0 25px 20px">
  <div class="card">
    <h2>🔧 Diagnóstico de Tubos del Hardware</h2>
    <p style="font-size:.8rem;color:#888;margin-bottom:16px">
      Comandos por DENOMINACIÓN (hex del valor, no número de tubo).<br>
      $10 = INT000<strong>A</strong>003 (no INT0003003, eso son 3 pesos).
    </p>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:16px">
      <?php
      $testCmds = [
        'INT0001003'=>'Dispensar $1',
        'INT0002003'=>'Dispensar $2',
        'INT0005003'=>'Dispensar $5',
        'INT000A003'=>'Dispensar $10',
      ];
      foreach ($testCmds as $cmd => $label): ?>
      <button onclick="testTubo('<?= $cmd ?>')"
        style="padding:10px 6px;background:#1a1a2e;border:1px solid #3a3a6a;border-radius:6px;color:#f0c040;
               cursor:pointer;font-size:.75rem;font-family:monospace;transition:all .2s"
        onmouseover="this.style.borderColor='#f0c040'" onmouseout="this.style.borderColor='#3a3a6a'">
        <div style="font-weight:bold"><?= $cmd ?></div>
        <div style="color:#888;font-size:.7rem;margin-top:3px"><?= $label ?></div>
      </button>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px">
      <input type="text" id="rawCmdInput" value="INT0002003"
        style="flex:1;padding:8px 12px;background:#0d0d1a;border:1px solid #3a3a6a;border-radius:6px;color:#f0c040;font-family:monospace;font-size:1rem">
      <button onclick="testTubo(document.getElementById('rawCmdInput').value)"
        style="padding:8px 18px;background:#e65100;border:none;border-radius:6px;color:#fff;font-weight:bold;cursor:pointer">
        ENVIAR
      </button>
    </div>
    <div id="rawResult" class="result-box"></div>
  </div>
</div>

<div style="max-width:1100px;margin:0 auto;padding:0 25px 30px">
  <div class="card">
    <h2>🔍 Estado de la Cola de Dispensado</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
      <div>
        <div style="font-size:.75rem;color:#555;margin-bottom:6px">ARCHIVO COLA (monedero_dispense_queue.json)</div>
        <pre id="queueContent" style="background:#0a0a14;padding:10px;border-radius:6px;font-size:.75rem;color:#90caf9;min-height:60px;overflow:auto;border:1px solid #1a1a3a"><?php
          echo file_exists($queueFile) ? htmlspecialchars(file_get_contents($queueFile)) : '(vacío)';
        ?></pre>
      </div>
      <div>
        <div style="font-size:.75rem;color:#555;margin-bottom:6px">ARCHIVO RESPUESTA (monedero_dispense_response.json)</div>
        <pre id="responseContent" style="background:#0a0a14;padding:10px;border-radius:6px;font-size:.75rem;color:#81c784;min-height:60px;overflow:auto;border:1px solid #1a1a3a"><?php
          echo file_exists($responseFile) ? htmlspecialchars(file_get_contents($responseFile)) : '(vacío)';
        ?></pre>
      </div>
    </div>
  </div>
</div>

<script>
const API = 'monedero_api.php';

// ─── Calcular desglose sin dispensar ───────────────────────────────────────
async function calcularDesglose() {
  const monto = parseFloat(document.getElementById('montoInput').value);
  if (!monto || monto <= 0) return showResult('Ingresa un monto válido', 'err');

  showResult('<span class="spinner"></span> Calculando...', 'wait');

  const fd = new FormData();
  fd.append('action', 'check_change_availability');
  fd.append('monto', monto);

  try {
    const r = await fetch(API, { method: 'POST', body: fd });
    const data = await r.json();

    if (data.posible || data.disponible) {
      const desglose = data.desglose || {};
      let html = `✅ Se puede dar cambio de $${monto}\n\nDesglose:\n`;
      let chips = '<div class="desglose">';
      let total = 0;
      for (const [d, c] of Object.entries(desglose)) {
        if (c > 0) {
          html += `  $${d} × ${c} = $${d*c}\n`;
          chips += `<span class="chip">$${d} × ${c}</span>`;
          total += d * c;
        }
      }
      chips += '</div>';
      html += `\nTotal: $${total}`;
      showResult(html + '\n' + chips, 'info');
    } else {
      showResult(`❌ No hay cambio suficiente para $${monto}\n${data.mensaje || ''}`, 'err');
    }
  } catch(e) {
    showResult('Error al conectar con la API: ' + e.message, 'err');
  }
}

// ─── Dispensar cambio ──────────────────────────────────────────────────────
async function dispensar() {
  const monto = parseFloat(document.getElementById('montoInput').value);
  if (!monto || monto <= 0) return showResult('Ingresa un monto válido', 'err');

  const btn = document.getElementById('btnDispense');
  btn.disabled = true;

  showResult('<span class="spinner"></span> Enviando orden al dispensador... (espera hasta 30s)', 'wait');

  const fd = new FormData();
  fd.append('action', 'dispensar_cambio');
  fd.append('monto', monto);

  try {
    const r = await fetch(API, { method: 'POST', body: fd });
    const data = await r.json();

    if (data.success) {
      const desglose = data.desglose || {};
      let html = `✅ ¡DISPENSADO! $${data.monto}\n\nMonedas dispensadas:\n`;
      let chips = '<div class="desglose">';
      for (const [d, c] of Object.entries(desglose)) {
        if (c > 0) {
          html += `  $${d} × ${c}\n`;
          chips += `<span class="chip">$${d} × ${c}</span>`;
        }
      }
      chips += '</div>';
      if (data.total_dispensado !== undefined) html += `\nTotal físico: $${data.total_dispensado}`;
      showResult(html + '\n' + chips, 'ok');
      refreshInventario();
    } else {
      showResult(`❌ Error: ${data.mensaje || 'Error desconocido'}\n${JSON.stringify(data, null, 2)}`, 'err');
    }
  } catch(e) {
    showResult('Error de red: ' + e.message, 'err');
  } finally {
    btn.disabled = false;
    refreshQueue();
  }
}

// ─── Mostrar resultado ─────────────────────────────────────────────────────
function showResult(html, type) {
  const box = document.getElementById('resultBox');
  box.className = 'result-box result-' + type;
  box.style.display = 'block';
  box.innerHTML = html;
}

// ─── Cargar inventario inicial ─────────────────────────────────────────────
async function cargarInventario() {
  const fd = new FormData();
  fd.append('action', 'set_inventory');
  [1,2,5,10,20].forEach(d => {
    fd.append(String(d), document.getElementById('inv_' + d).value || 0);
  });
  try {
    const r = await fetch(API, { method: 'POST', body: fd });
    const data = await r.json();
    const box = document.getElementById('invResult');
    if (data.success) {
      box.className = 'result-box result-ok';
      box.style.display = 'block';
      const denoms = data.inventario?.denominaciones || {};
      let txt = '✅ Inventario guardado\n';
      for (const [d,c] of Object.entries(denoms)) if (c>0) txt += `  $${d} × ${c}\n`;
      txt += `Total: $${data.inventario?.total_pesos || 0}`;
      box.textContent = txt;
      refreshInventario();
    } else {
      box.className = 'result-box result-err';
      box.style.display = 'block';
      box.textContent = '❌ ' + (data.mensaje || 'Error');
    }
  } catch(e) {
    const box = document.getElementById('invResult');
    box.className = 'result-box result-err';
    box.style.display = 'block';
    box.textContent = 'Error: ' + e.message;
  }
}

// ─── Test tubo raw ─────────────────────────────────────────────────────────
async function testTubo(cmd) {
  cmd = cmd.toUpperCase().trim();
  document.getElementById('rawCmdInput').value = cmd;
  const box = document.getElementById('rawResult');
  box.className = 'result-box';
  box.style.display = 'block';
  box.textContent = '⏳ Enviando ' + cmd + ' al hardware...';
  const fd = new FormData();
  fd.append('action', 'enviar_comando');
  fd.append('comando', cmd);
  try {
    const r = await fetch(API, { method: 'POST', body: fd });
    const data = await r.json();
    if (data.success) {
      box.className = 'result-box result-ok';
      box.textContent = '✅ Comando enviado: ' + cmd
        + '\nRespuesta HW: ' + (data.respuesta?.rx || '(sin echo)')
        + '\n\n👁️ Observa qué moneda salió físicamente y anota el mapeo.';
    } else {
      box.className = 'result-box result-err';
      box.textContent = '❌ ' + (data.mensaje || JSON.stringify(data));
    }
  } catch(e) {
    box.className = 'result-box result-err';
    box.textContent = 'Error: ' + e.message;
  }
}

// ─── Logs ──────────────────────────────────────────────────────────────────
async function refreshLogs() {
  try {
    const r = await fetch('?ajax=logs&_=' + Date.now());
    const text = await r.text();
    const container = document.getElementById('logContainer');
    const lines = text.split('\n').filter(l => l.trim());
    container.innerHTML = lines.map(line => {
      let cls = '';
      if (line.includes('MONEDA') || line.includes('💰') || line.includes('COIN'))  cls = 'log-coin';
      else if (line.includes('ERROR') || line.includes('❌'))  cls = 'log-err';
      else if (line.includes('⚠️') || line.includes('WARN'))   cls = 'log-warn';
      else if (line.includes('✅') || line.includes('[OK]'))   cls = 'log-ok';
      return `<div class="${cls}">${escHtml(line)}</div>`;
    }).join('');
    container.scrollTop = container.scrollHeight;
  } catch(e) {}
}

// ─── Saldo ─────────────────────────────────────────────────────────────────
async function refreshSaldo() {
  try {
    const fd = new FormData(); fd.append('action', 'get_saldo');
    const r = await fetch(API, { method: 'POST', body: fd });
    const data = await r.json();
    if (data.saldo_cliente !== undefined || data.saldo !== undefined) {
      const cliente = parseFloat(data.saldo_cliente ?? data.saldo) || 0;
      document.getElementById('saldoDisplay').textContent = '$' + cliente.toFixed(2);
    }
    if (data.saldo_maquina !== undefined) {
      document.getElementById('totalInventario').textContent = parseFloat(data.saldo_maquina).toFixed(2);
    }
  } catch(e) {}
}

// ─── Inventario ────────────────────────────────────────────────────────────
async function refreshInventario() {
  try {
    const fd = new FormData(); fd.append('action', 'get_coin_inventory');
    const r = await fetch(API, { method: 'POST', body: fd });
    const data = await r.json();
    if (!data || !data.denominaciones) return;
    const d = data.denominaciones;
    const maxCant = Math.max(...Object.values(d), 1);
    [20,10,5,2,1].forEach(denom => {
      const cant = parseInt(d[denom] || 0);
      const pct  = Math.round(cant / maxCant * 100);
      const bar  = document.getElementById('bar-' + denom);
      const txt  = document.getElementById('cant-' + denom);
      if (bar) bar.style.width = pct + '%';
      if (txt) txt.textContent = cant + ' monedas';
    });
    document.getElementById('totalInventario').textContent = data.total_pesos || 0;
  } catch(e) {}
}

// ─── Cola de dispensado ────────────────────────────────────────────────────
async function refreshQueue() {
  try {
    const r = await fetch('?ajax=queue&_=' + Date.now());
    const data = await r.json();
    document.getElementById('queueContent').textContent    = data.queue    || '(vacío)';
    document.getElementById('responseContent').textContent = data.response || '(vacío)';
  } catch(e) {}
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ─── Auto-refresh ──────────────────────────────────────────────────────────
refreshLogs();
refreshSaldo();
refreshInventario();
refreshQueue();

setInterval(refreshSaldo,      2000);
setInterval(refreshInventario, 4000);
setInterval(refreshLogs,       3000);
setInterval(refreshQueue,      2000);
</script>

<?php
// ─── AJAX handlers ──────────────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    header('Content-Type: text/plain; charset=utf-8');
    if ($_GET['ajax'] === 'logs') {
        $logFile = __DIR__ . '/admin/dist/logs/monedero_listener.log';
        if (file_exists($logFile)) {
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            echo implode("\n", array_slice($lines, -80));
        } else {
            echo 'No hay logs todavía. ¿Está corriendo el listener?';
        }
    } elseif ($_GET['ajax'] === 'queue') {
        header('Content-Type: application/json');
        $q = __DIR__ . '/admin/dist/logs/monedero_dispense_queue.json';
        $rs = __DIR__ . '/admin/dist/logs/monedero_dispense_response.json';
        echo json_encode([
            'queue'    => file_exists($q)  ? file_get_contents($q)  : null,
            'response' => file_exists($rs) ? file_get_contents($rs) : null,
        ]);
    }
    exit;
}
?>
</body>
</html>
