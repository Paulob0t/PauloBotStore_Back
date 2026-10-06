<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor de Despachos - VendingBox</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 14px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stat-card.total .number { color: #667eea; }
        .stat-card.pendientes .number { color: #f39c12; }
        .stat-card.enviados .number { color: #3498db; }
        .stat-card.despachados .number { color: #27ae60; }
        .stat-card.errores .number { color: #e74c3c; }
        
        .table-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            overflow-x: auto;
        }
        
        .controls {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-0 { background: #fff3cd; color: #856404; }
        .status-1 { background: #cce5ff; color: #004085; }
        .status-2 { background: #d4edda; color: #155724; }
        .status-3 { background: #f8d7da; color: #721c24; }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        
        .ubicacion {
            font-weight: bold;
            padding: 3px 8px;
            background: #667eea;
            color: white;
            border-radius: 5px;
            font-family: monospace;
        }
        
        .refresh-info {
            text-align: center;
            color: #666;
            font-size: 12px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🤖 Monitor de Despachos Arduino</h1>
            <p>Sistema de control de máquina expendedora VendingBox</p>
        </div>
        
        <div class="stats" id="stats">
            <div class="stat-card total">
                <h3>Total</h3>
                <div class="number">-</div>
            </div>
            <div class="stat-card pendientes">
                <h3>⏳ Pendientes</h3>
                <div class="number">-</div>
            </div>
            <div class="stat-card enviados">
                <h3>📤 Enviados</h3>
                <div class="number">-</div>
            </div>
            <div class="stat-card despachados">
                <h3>✅ Despachados</h3>
                <div class="number">-</div>
            </div>
            <div class="stat-card errores">
                <h3>❌ Errores</h3>
                <div class="number">-</div>
            </div>
        </div>
        
        <div class="table-container">
            <div class="controls">
                <button class="btn btn-primary" onclick="cargarDespachos()">🔄 Actualizar</button>
                <button class="btn btn-success" onclick="filtrarEstado(2)">✅ Ver Despachados</button>
                <button class="btn btn-warning" onclick="filtrarEstado(0)">⏳ Ver Pendientes</button>
                <button class="btn btn-danger" onclick="filtrarEstado(3)">❌ Ver Errores</button>
                <button class="btn btn-primary" onclick="filtrarEstado(null)">📋 Ver Todos</button>
            </div>
            
            <div id="content">
                <div class="loading">
                    <p>⏳ Cargando datos...</p>
                </div>
            </div>
            
            <div class="refresh-info">
                Última actualización: <span id="lastUpdate">-</span>
            </div>
        </div>
    </div>

    <script>
        let estadoFiltro = null;
        
        function formatearFecha(fecha) {
            if (!fecha) return '-';
            const d = new Date(fecha);
            return d.toLocaleString('es-MX', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        
        function obtenerEstadoTexto(estado) {
            const estados = {
                0: '⏳ Pendiente',
                1: '📤 Enviado',
                2: '✅ Despachado',
                3: '❌ Error'
            };
            return estados[estado] || 'Desconocido';
        }
        
        async function cargarDespachos() {
            const content = document.getElementById('content');
            content.innerHTML = '<div class="loading"><p>⏳ Cargando datos...</p></div>';
            
            try {
                let url = 'consultar_despachos.php?limite=100';
                if (estadoFiltro !== null) {
                    url += `&estatus=${estadoFiltro}`;
                }
                
                const response = await fetch(url);
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Error al cargar datos');
                }
                
                // Actualizar estadísticas
                actualizarEstadisticas(data.estadisticas);
                
                // Mostrar tabla
                if (data.despachos.length === 0) {
                    content.innerHTML = '<div class="no-data"><p>📭 No hay despachos registrados</p></div>';
                    return;
                }
                
                let html = `
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Ubicación</th>
                                <th>Cantidad</th>
                                <th>SKU</th>
                                <th>Estado</th>
                                <th>Folio</th>
                                <th>Fecha Registro</th>
                                <th>Fecha Enviado</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.despachos.forEach(d => {
                    html += `
                        <tr>
                            <td>#${d.id_despacho}</td>
                            <td>${d.nombre_producto}</td>
                            <td><span class="ubicacion">${d.ubicacion}</span></td>
                            <td>${d.cantidad}</td>
                            <td><code>${d.sku || '-'}</code></td>
                            <td><span class="status-badge status-${d.estatus_despacho}">${obtenerEstadoTexto(d.estatus_despacho)}</span></td>
                            <td><small>${d.folio}</small></td>
                            <td>${formatearFecha(d.fecha_registro)}</td>
                            <td>${formatearFecha(d.fecha_enviado)}</td>
                        </tr>
                    `;
                });
                
                html += '</tbody></table>';
                content.innerHTML = html;
                
                // Actualizar timestamp
                document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString('es-MX');
                
            } catch (error) {
                console.error('Error:', error);
                content.innerHTML = `
                    <div class="no-data">
                        <p>❌ Error al cargar datos</p>
                        <p style="color: #e74c3c; font-size: 12px;">${error.message}</p>
                    </div>
                `;
            }
        }
        
        function actualizarEstadisticas(stats) {
            if (!stats) return;
            
            const statsCards = document.querySelectorAll('.stat-card .number');
            statsCards[0].textContent = stats.total || 0;
            statsCards[1].textContent = stats.pendientes || 0;
            statsCards[2].textContent = stats.enviados || 0;
            statsCards[3].textContent = stats.despachados || 0;
            statsCards[4].textContent = stats.errores || 0;
        }
        
        function filtrarEstado(estado) {
            estadoFiltro = estado;
            cargarDespachos();
        }
        
        // Cargar datos al iniciar
        cargarDespachos();
        
        // Auto-refresh cada 30 segundos
        setInterval(cargarDespachos, 30000);
    </script>
</body>
</html>
