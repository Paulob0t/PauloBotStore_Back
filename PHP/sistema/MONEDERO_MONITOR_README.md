# 🪙 MONEDERO MONITOR - GUÍA DE USO

## 📋 Descripción

**Monedero Monitor** es una aplicación de escritorio para VendingBox que permite monitorear en tiempo real el listener del monedero, ver logs, estadísticas de saldo y control de inventario de cambio.

---

## 🚀 Ejecutar la Aplicación

### Opción 1: Con PowerShell (Recomendado para desarrollo)
```batch
MonederoMonitor.bat
```
O directamente:
```powershell
powershell.exe -ExecutionPolicy Bypass -File MonederoMonitor.ps1
```

### Opción 2: Compilar a .EXE (Recomendado para producción)

#### Paso 1: Instalar ps2exe
```powershell
Install-Module ps2exe -Scope CurrentUser
```

#### Paso 2: Compilar el script
```powershell
Invoke-ps2exe .\MonederoMonitor.ps1 .\MonederoMonitor.exe -noConsole -title "Monedero Monitor" -company "VendingBox" -version 1.0.0.0
```

#### Paso 3: Ejecutar el .exe
```batch
MonederoMonitor.exe
```

---

## 📊 Características

### ✅ Interfaz Visual Moderna
- Tema oscuro profesional
- Indicadores de estado en tiempo real
- Logs con colores y timestamps

### 💰 Monitoreo de Saldo
- Saldo actual del monedero
- Cambio disponible para devolución
- Desglose de monedas por denominación

### 📋 Logs en Tiempo Real
- Ver actividad del listener en vivo
- Detectar errores inmediatamente
- Historial completo de transacciones

### 🎛️ Controles
- **▶ INICIAR**: Inicia el listener del monedero
- **⏹ DETENER**: Detiene el listener
- **🗑 LIMPIAR**: Limpia el área de logs de la pantalla
- **⚠ RESET SALDO**: Resetea saldo, inventario y logs del sistema (usar cuando saques el dinero físico)
- **➖ MINIMIZAR**: Minimiza a la bandeja del sistema
- **⚙ CAMBIAR SERVIDOR**: Alterna entre Laragon y XAMPP

### 🔔 System Tray (Bandeja del Sistema)
- Ícono dorado con símbolo $ (MONEDA DORADA)
- Notificaciones cuando se insertan monedas
- Menú contextual: Mostrar Ventana / Reset Saldo y Monedas / Salir
- Doble clic para restaurar ventana
- El icono permanece visible incluso si minimizas
- Sistema de verificación automática cada 5 segundos

### 🔄 Detección de Instancias Múltiples
- Solo puede haber una instancia corriendo a la vez
- Si intentas abrir otra, automáticamente restaura la ventana existente
- Si está minimizada, te indica dónde buscar el icono dorado
- Sistema robusto con mutex y archivo de estado

---

## 🎨 Capturas de Pantalla

```
┌─────────────────────────────────────────────┐
│     🪙 MONEDERO MONITOR                     │
│     VendingBox Control Panel                │
├─────────────────────────────────────────────┤
│ ┌─────────┐ ┌──────────────┐ ┌────────────┐│
│ │ ESTADO  │ │ SALDO ACTUAL │ │   CAMBIO   ││
│ │ 🟢 RUN  │ │   $125.50    │ │  $235.00   ││
│ └─────────┘ └──────────────┘ └────────────┘│
├─────────────────────────────────────────────┤
│ 💰 DESGLOSE: $20×5  $10×8  $5×10  $2×15    │
├─────────────────────────────────────────────┤
│ 📋 LOGS EN TIEMPO REAL                      │
│ ┌─────────────────────────────────────────┐ │
│ │ [10:30:15] ✅ Listener iniciado         │ │
│ │ [10:30:20] 🪙 MONEDA: $10 | Saldo: $135│ │
│ │ [10:30:45] 💵 Cambio dispensado: $9    │ │
│ │ ...                                     │ │
│ └─────────────────────────────────────────┘ │
├─────────────────────────────────────────────┤
│ [▶ INICIAR] [⏹ DETENER] [🗑 LIMPIAR] [➖]  │
└─────────────────────────────────────────────┘
```

---

## ⚙️ Requisitos

- Windows 10/11
- PowerShell 5.1 o superior (incluido en Windows)
- PHP instalado y en PATH (para ejecutar listener)
- Puerto COM5 configurado para el hardware del monedero

---

## 🔧 Configuración

### Archivos de Sistema
Los archivos de logs y datos se encuentran en:
```
sistema/admin/dist/logs/
├── monedero_listener.log     # Logs del listener
├── coin_inventory.log         # Inventario de cambio
├── saldo_actual.json          # Saldo en tiempo real
├── server_preference.txt      # Servidor PHP seleccionado (Laragon/XAMPP)
└── monitor_running.state      # Estado de ejecución (proceso activo)
```

### Servidores PHP Compatibles
El programa detecta automáticamente:
- **Laragon** - C:\laragon\bin\php\
- **XAMPP** - C:\xampp\php\ (y variantes en D:\ y Program Files)

Si tienes ambos instalados, te preguntará cuál usar la primera vez y guardará tu preferencia.

---

## 📝 Notas Importantes

### ⚠️ Al cerrar la ventana
Cuando cierres la ventana, te preguntará:
- **SÍ**: Cerrar completamente (detiene el listener)
- **NO**: Minimizar a bandeja (listener sigue corriendo)
- **CANCELAR**: No hacer nada

### 🔄 Auto-actualización
- Las estadísticas se actualizan cada 2 segundos
- Los logs se muestran en tiempo real
- El estado del listener se verifica constantemente

### 💡 Tips
- Minimiza a la bandeja para mantenerlo corriendo sin ocupar espacio en pantalla
- Las notificaciones te avisarán cuando se inserten monedas
- Si el listener se detiene inesperadamente, verás un mensaje en rojo
- El icono dorado ($) se verifica automáticamente cada 5 segundos para asegurar que permanezca visible
- Usa "⚠ RESET SALDO" solo cuando hayas sacado físicamente el dinero del monedero
- Si tienes Laragon y XAMPP, el botón "⚙ CAMBIAR SERVIDOR" te permite alternar entre ellos
- El programa solo permite una instancia: si intentas abrirlo de nuevo, restaura la ventana existente

---

## 🐛 Solución de Problemas

### La aplicación no inicia
```powershell
# Verificar política de ejecución
Get-ExecutionPolicy

# Si es "Restricted", cambiar a:
Set-ExecutionPolicy -Scope CurrentUser -ExecutionPolicy RemoteSigned
```

### No se ve el listener corriendo
- Verifica que PHP esté en el PATH: `php --version`
- Verifica que existe `monedero_listener.php`
- Revisa los logs en la aplicación
- Usa el botón "⚙ CAMBIAR SERVIDOR" para verificar/cambiar entre Laragon y XAMPP

### El icono desaparece de la bandeja del sistema
- El programa ahora tiene verificación automática cada 5 segundos
- Si minimizas y no ves el icono, espera unos segundos
- Busca una **MONEDA DORADA ($)** junto al reloj de Windows
- Si aún no aparece, cierra completamente y vuelve a abrir

### Ya tengo una instancia abierta pero no la encuentro
- Intenta abrir el programa de nuevo
- Automáticamente buscará y restaurará la ventana existente
- Si está minimizada, te dirá dónde buscar el icono dorado
- Haz **DOBLE CLIC** en el icono de moneda dorada ($)

### Necesito resetear el saldo porque saqué el dinero
- Usa el botón **⚠ RESET SALDO** (naranja)
- Esto limpia: saldo actual, inventario de monedas y logs
- También disponible en el menú del icono: "Reset Saldo y Monedas"

### El .exe no funciona después de compilar
- Asegúrate de usar `-noConsole` en ps2exe
- Verifica que todas las rutas sean relativas a `$PSScriptRoot`
- Compila con `-requireAdmin` si necesitas permisos elevados

---

## 📞 Soporte

Para reportar bugs o sugerencias:
- Email: soporte@vendingbox.com
- Sistema de tickets interno

---

## 📜 Licencia

© 2026 VendingBox. Todos los derechos reservados.
Uso interno exclusivo.

---

## 🎯 Roadmap

### Próximas versiones
- [ ] Gráficas de ingresos por hora/día
- [ ] Alertas cuando el cambio esté bajo
- [ ] Exportar reportes en PDF/Excel
- [ ] Configuración de puerto COM desde la GUI
- [ ] Modo oscuro/claro configurable
- [ ] Soporte multi-idioma

### ✅ Versión Actual 2.0.0
- [x] Detección automática Laragon/XAMPP
- [x] Sistema de instancia única mejorado
- [x] Icono persistente en bandeja del sistema
- [x] Reset de saldo y monedas
- [x] Verificación automática de icono cada 5s
- [x] Restauración automática de ventana existente

---

**Versión:** 2.0.0  
**Última actualización:** 28 de abril de 2026  
**Desarrollado para:** VendingBox Online System
