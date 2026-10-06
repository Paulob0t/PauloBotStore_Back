# ========================================
# LIMPIAR TODO LO QUE NO SEA ADMIN
# Limpieza COMPLETA - solo admin puro
# ========================================

$baseDir = "C:\xampp\htdocs\Nube\sistema\admin\dist"

Write-Host "`n========================================================" -ForegroundColor Cyan
Write-Host "    LIMPIEZA COMPLETA - SOLO ADMIN PURO" -ForegroundColor Cyan
Write-Host "========================================================`n" -ForegroundColor Cyan

# ========================================
# ARCHIVOS A ELIMINAR (TODO lo que no sea admin)
# ========================================

$archivosAEliminar = @(
    # === CONSULTAS DE VENTA ===
    "obtener_categorias.php",
    "obtener_detalle_venta.php",
    "obtener_nombre_categoria.php",
    "get_empresa_config.php",
    
    # === IMPRESION/TICKETS ===
    "qz-tray.js",
    "imprimir_corte.php",
    
    # === OPERACION LOCAL ===
    "_iniciar_listener_web.bat",
    
    # === LOGS VIEJOS ===
    "error_ajax.log",
    "error_log",
    
    # === TESTS NO NECESARIOS ===
    "test.php",
    
    # === SCRIPTS DE LIMPIEZA ===
    "LIMPIAR_ARCHIVOS_VENTA.ps1"
)

# ========================================
# MOSTRAR ARCHIVOS QUE SE VAN A ELIMINAR
# ========================================

Write-Host "[ARCHIVOS QUE SE ELIMINARAN]`n" -ForegroundColor Yellow

$eliminados = 0
$noEncontrados = 0

foreach ($archivo in $archivosAEliminar) {
    $rutaCompleta = Join-Path $baseDir $archivo
    if (Test-Path $rutaCompleta) {
        Write-Host "   [X] $archivo" -ForegroundColor Red
        $eliminados++
    } else {
        Write-Host "   [!] $archivo (no existe)" -ForegroundColor Gray
        $noEncontrados++
    }
}

Write-Host "`n========================================================`n" -ForegroundColor Gray

Write-Host "Total a eliminar: $eliminados items" -ForegroundColor Yellow
Write-Host "No encontrados: $noEncontrados items`n" -ForegroundColor Gray

# ========================================
# CONFIRMAR ELIMINACION
# ========================================

Write-Host "[!] CONFIRMAS LA ELIMINACION?" -ForegroundColor Red
Write-Host "   Dejara SOLO archivos administrativos puros`n" -ForegroundColor Yellow

$confirmacion = Read-Host "Escribe 'SI' para confirmar"

if ($confirmacion -ne "SI") {
    Write-Host "`n[X] CANCELADO - No se elimino nada`n" -ForegroundColor Red
    exit
}

# ========================================
# ELIMINAR ARCHIVOS
# ========================================

Write-Host "`n[ELIMINANDO ARCHIVOS...]`n" -ForegroundColor Cyan

$contadorEliminados = 0

foreach ($archivo in $archivosAEliminar) {
    $rutaCompleta = Join-Path $baseDir $archivo
    if (Test-Path $rutaCompleta) {
        try {
            Remove-Item -Path $rutaCompleta -Force -ErrorAction Stop
            Write-Host "   [OK] Eliminado: $archivo" -ForegroundColor Green
            $contadorEliminados++
        } catch {
            Write-Host "   [ERROR] al eliminar: $archivo - $_" -ForegroundColor Red
        }
    }
}

Write-Host "`n========================================================`n" -ForegroundColor Gray

# ========================================
# MOSTRAR ARCHIVOS QUE QUEDARON
# ========================================

Write-Host "[ARCHIVOS FINALES - SOLO ADMIN PURO]`n" -ForegroundColor Green

$archivosRestantes = Get-ChildItem -Path $baseDir -File | Sort-Object Name

foreach ($archivo in $archivosRestantes) {
    Write-Host "   [+] $($archivo.Name)" -ForegroundColor White
}

Write-Host "`n[CARPETAS QUE QUEDARON]`n" -ForegroundColor Green

$carpetasRestantes = Get-ChildItem -Path $baseDir -Directory | Sort-Object Name

foreach ($carpeta in $carpetasRestantes) {
    $archivos = (Get-ChildItem -Path $carpeta.FullName -Recurse -File).Count
    Write-Host "   [+] $($carpeta.Name)\ ($archivos archivos)" -ForegroundColor White
}

Write-Host "`n========================================================" -ForegroundColor Green
Write-Host "         LIMPIEZA ADMIN PURO COMPLETADA" -ForegroundColor Green
Write-Host "========================================================`n" -ForegroundColor Green

Write-Host "[RESUMEN]" -ForegroundColor Cyan
Write-Host "   Eliminados: $contadorEliminados items" -ForegroundColor Yellow
Write-Host "   Restantes: $($archivosRestantes.Count) archivos" -ForegroundColor Green
Write-Host "   Carpetas: $($carpetasRestantes.Count) carpetas`n" -ForegroundColor Green

Write-Host "[ARCHIVOS ADMIN QUE QUEDARON]" -ForegroundColor Yellow
Write-Host "   [+] Panel: index.php, login.php, menu.php, navbar.php, footer.php" -ForegroundColor White
Write-Host "   [+] Productos: guardar_producto.php, tabla_productos.php, formulario_producto.php" -ForegroundColor White
Write-Host "   [+] Categorias: guardar_categoria_y_subcategorias.php, tabla_categorias.php, tabla_subcategorias.php" -ForegroundColor White
Write-Host "   [+] Empresa: configurar_empresa.php, save_empresa_config.php" -ForegroundColor White
Write-Host "   [+] Cortes: cortes_caja.php, historial_movimientos.php" -ForegroundColor White
Write-Host "   [+] Auth: iniciarSesion.php, cerrarSesion.php, register.php, registrarUsuario.php" -ForegroundColor White
Write-Host "   [+] Config: db_config_dual.php" -ForegroundColor White
Write-Host "   [+] Tests: test_db_nube.php, test_json_limpio.php, verificar_sincronizacion_log.php`n" -ForegroundColor White

Write-Host "[TODO LO DE VENTA/OPERACION ELIMINADO]" -ForegroundColor Gray
Write-Host "   [-] obtener_categorias.php" -ForegroundColor Gray
Write-Host "   [-] obtener_detalle_venta.php" -ForegroundColor Gray
Write-Host "   [-] get_empresa_config.php" -ForegroundColor Gray
Write-Host "   [-] qz-tray.js" -ForegroundColor Gray
Write-Host "   [-] imprimir_corte.php`n" -ForegroundColor Gray

Write-Host "========================================================`n" -ForegroundColor Cyan
