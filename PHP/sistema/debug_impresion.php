<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Debug Impresión - VendingBox</title>
    <style>
        body { 
            font-family: monospace; 
            padding: 20px; 
            background: #1e1e1e; 
            color: #00ff00; 
        }
        button {
            background: #FFD700;
            color: #000;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin: 10px 5px;
        }
        button:hover { background: #FFC700; }
        .log { 
            background: #000; 
            padding: 15px; 
            border-radius: 5px; 
            margin-top: 20px; 
            max-height: 400px; 
            overflow-y: auto; 
        }
        .success { color: #00ff00; }
        .error { color: #ff0000; }
        .info { color: #00aaff; }
    </style>
</head>
<body>

<h1>🖨️ Debug Sistema de Impresión</h1>

<div>
    <button onclick="verificarQZ()">1. Verificar QZ Tray</button>
    <button onclick="conectarQZ()">2. Conectar QZ Tray</button>
    <button onclick="buscarImpresoras()">3. Buscar Impresoras</button>
    <button onclick="probarTicketVenta()">4. Probar Ticket Venta</button>
    <button onclick="limpiarLog()">Limpiar Log</button>
</div>

<div class="log" id="log"></div>

<script src="qz-tray.js"></script>
<script src="js/modulos/print-ticket.js"></script>
<script>

let logDiv = document.getElementById('log');

function log(mensaje, tipo = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const className = tipo;
    logDiv.innerHTML += `<div class="${className}">[${timestamp}] ${mensaje}</div>`;
    logDiv.scrollTop = logDiv.scrollHeight;
    console.log(mensaje);
}

function limpiarLog() {
    logDiv.innerHTML = '';
}

// =====================================================
// 1. VERIFICAR QZ TRAY
// =====================================================
function verificarQZ() {
    log('=== VERIFICANDO QZ TRAY ===', 'info');
    
    if (typeof qz === 'undefined') {
        log('❌ ERROR: qz-tray.js NO está cargado', 'error');
        return;
    }
    
    log('✅ qz está definido', 'success');
    log('✅ qz.version: ' + qz.version, 'success');
    
    if (typeof PrintTicket === 'undefined') {
        log('❌ ERROR: PrintTicket NO está definido', 'error');
        return;
    }
    
    log('✅ PrintTicket está definido', 'success');
    log('✅ Nombre impresora: ' + PrintTicket.printerName, 'success');
}

// =====================================================
// 2. CONECTAR QZ TRAY
// =====================================================
async function conectarQZ() {
    log('=== CONECTANDO A QZ TRAY ===', 'info');
    
    try {
        await PrintTicket.init();
        log('✅ QZ Tray inicializado', 'success');
        
        await PrintTicket.connect();
        log('✅ Conectado a QZ Tray', 'success');
        
    } catch (error) {
        log('❌ Error al conectar: ' + error.message, 'error');
    }
}

// =====================================================
// 3. BUSCAR IMPRESORAS
// =====================================================
async function buscarImpresoras() {
    log('=== BUSCANDO IMPRESORAS ===', 'info');
    
    try {
        if (!PrintTicket.isConnected) {
            log('⚠️ No conectado, conectando primero...', 'info');
            await PrintTicket.init();
            await PrintTicket.connect();
        }
        
        log('🔍 Listando impresoras disponibles...', 'info');
        const printers = await qz.printers.find();
        
        log('✅ Impresoras encontradas: ' + printers.length, 'success');
        printers.forEach((printer, i) => {
            log(`  ${i + 1}. ${printer}`, 'info');
        });
        
    } catch (error) {
        log('❌ Error al buscar impresoras: ' + error.message, 'error');
    }
}

// =====================================================
// 4. PROBAR TICKET DE VENTA
// =====================================================
async function probarTicketVenta() {
    log('=== PROBANDO TICKET DE VENTA ===', 'info');
    
    const ticketData = {
        folio: 'TEST-' + Date.now(),
        fecha: new Date().toLocaleString(),
        cajero: 'DEBUG TEST',
        productos: [
            {
                nombre: 'Coca Cola 600ml',
                cantidad: 2,
                precio: 15.00,
                descuento: 0
            },
            {
                nombre: 'Sabritas Original',
                cantidad: 1,
                precio: 18.50,
                descuento: 2.50
            }
        ],
        subtotal: 46.00,
        descuento: 2.50,
        iva: 6.96,
        total: 50.46,
        metodo_pago: 'Efectivo'
    };
    
    log('📄 Datos del ticket:', 'info');
    log(JSON.stringify(ticketData, null, 2), 'info');
    
    try {
        log('🖨️ Iniciando impresión...', 'info');
        
        const resultado = await PrintTicket.imprimirTicketVenta(ticketData);
        
        if (resultado) {
            log('✅ TICKET IMPRESO EXITOSAMENTE', 'success');
        } else {
            log('⚠️ Impresión completada pero sin confirmación', 'info');
        }
        
    } catch (error) {
        log('❌ Error al imprimir ticket: ' + error.message, 'error');
        log('Stack: ' + error.stack, 'error');
    }
}

// Auto-verificar al cargar
window.addEventListener('DOMContentLoaded', () => {
    log('🚀 Página cargada, ejecutando verificaciones...', 'info');
    setTimeout(() => {
        verificarQZ();
    }, 500);
});

</script>

</body>
</html>
