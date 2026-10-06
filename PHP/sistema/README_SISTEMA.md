# 🚀 VendigBox - Sistema Completo de Dispensado

## ✅ Todo Implementado y Funcionando

### 🎯 Sistema Totalmente Integrado

El sistema de dispensado de cambio está **completamente integrado** en tu sistema de ventas. Todo funciona automáticamente.

---

## 🏁 Inicio Rápido

### Opción 1: Inicio Automático Completo (RECOMENDADO)
```
Doble click en: iniciar_sistema_completo.bat
```
Esto inicia:
- ✅ Apache (si no está corriendo)
- ✅ MySQL (si no está corriendo)
- ✅ Listener del Monedero
- ✅ Navegador con el sistema

### Opción 2: Solo Listener del Monedero
```
Doble click en: iniciar_listener_invisible.vbs
```

### Opción 3: Panel de Control Visual
```
Abre en navegador: http://localhost/vendigbox.c-onlineweb.net/panel_control.php
```

---

## 🛒 Flujo de Venta con Dispensado Automático

### 1. Cliente inserta monedas
- El hardware detecta las monedas
- Se actualiza el saldo en tiempo real
- Aparece widget flotante con el saldo

### 2. Cliente selecciona productos
- Agrega productos al carrito
- Ve el total en tiempo real

### 3. Cliente paga con el botón "Pagar con Monedas"
- Sistema calcula automáticamente el cambio
- Procesa la venta
- **🔥 DISPENSA EL CAMBIO AUTOMÁTICAMENTE**
- Muestra desglose de monedas dispensadas
- Imprime ticket (si está configurado)
- Limpia el saldo

---

## 💰 Comandos del Hardware

El sistema usa estos comandos automáticamente:

### Dispensar Cambio
- **$10**: `INT000A003\r\n`
- **$20**: `INT00014003\r\n`
- **$5**: `INT0005003\r\n`
- **$2**: `INT0002003\r\n`
- **$1**: `INT0001003\r\n`

### Control
- **RESET**: `INT0000000\r\n`
- **Habilitar**: `INT0000001\r\n`
- **Deshabilitar**: `INT0000002\r\n`

---

## 🧪 Probar el Sistema

### Test de Dispensado
```
http://localhost/vendigbox.c-onlineweb.net/test_dispensar_cambio.php
```

Desde aquí puedes:
- ✅ Probar dispensar diferentes montos
- ✅ Ver comandos enviados en tiempo real
- ✅ Enviar comandos manuales
- ✅ Reset / Habilitar / Deshabilitar hardware

### Ver Logs
```
http://localhost/vendigbox.c-onlineweb.net/ver_logs.php
```

---

## 📁 Archivos Importantes

### Sistema Principal
- `index.php` - Tienda (ya integrado)
- `cart.php` - Carrito (ya integrado)
- `monedero_api.php` - API del hardware (actualizado con protocolo real)
- `js/monedero-integration.js` - Integración frontend (actualizado)

### Gestor COM5 (monedero)
- `com5_manager.php` - Dueño exclusivo del puerto COM5 (lectura + dispensado)
- `com5_send_command.php` - Envío de comandos a la cola
- `com5_ps_manager.ps1` - Helper PowerShell para COM5
- `MonederoMonitor.bat` / `MonederoMonitor.ps1` - Panel de escritorio
- `iniciar_listener_invisible.vbs` - Inicia com5_manager en background

### Testing
- `test_dispensar_cambio.php` - Panel de pruebas
- `panel_control.php` - Panel de control general
- `monedero_test_command.php` - API para comandos directos

### Documentación
- `GUIA_MEI_CF7000.md` - Guía completa con todos los comandos
- `COMANDOS_REFERENCIA.txt` - Referencia rápida
- `README_SISTEMA.md` - Este archivo

### Scripts de Utilidad
- `iniciar_sistema_completo.bat` - Inicia todo
- `iniciar_listener_background.bat` - Solo listener visible
- `detener_listener.bat` - Detiene listener
- `verificar_sistema.bat` - Diagnóstico

---

## ⚙️ Configuración

### Puerto COM
Edita en `monedero_api.php` si tu puerto no es COM5:
```php
define('PUERTO_DISPENSER', 'COM5'); // Cambiar aquí
```

También edita en `com5_manager.php` y `admin/dist/logs/serial_config.txt` si el puerto no es COM5.

### Denominaciones Soportadas
Edita en `monedero_api.php`:
```php
$denominaciones = [20, 10, 5, 2, 1]; // Ajustar según tu hardware
```

---

## 🔧 Solución de Problemas

### El cambio no se dispensa
1. ✅ Verifica que com5_manager esté corriendo (MonederoMonitor o `iniciar_sistema_completo.bat`)
2. ✅ Revisa que el puerto COM sea correcto
3. ✅ Confirma que haya monedas en los tubos
4. ✅ Verifica logs: `admin/dist/logs/monedero_listener.log`

### El monedero no detecta monedas
1. ✅ Verifica que com5_manager esté corriendo (MonederoMonitor o `iniciar_sistema_completo.bat`)
2. ✅ Confirma conexión USB/Serial
3. ✅ Revisa puerto COM en Device Manager
4. ✅ Ejecuta `verificar_sistema.bat`

### Ver estado en tiempo real
```powershell
Get-Content admin\dist\logs\monedero_listener.log -Wait -Tail 20
```

---

## 📊 Monitoreo

### Ver Saldo Actual
```
http://localhost/vendingbox/monedero_api.php?action=get_saldo
```

### Reset Saldo
```
http://localhost/vendingbox/monedero_api.php?action=reset_saldo
```

### Estado del Hardware
```
http://localhost/vendingbox/monedero_api.php?action=hardware_reset
```

---

## 🎮 Uso Diario

### Al iniciar el día:
```
1. Ejecuta: iniciar_sistema_completo.bat
2. Verifica en panel_control.php que todo esté activo
3. Haz una venta de prueba
```

### Durante el día:
- El sistema funciona automáticamente
- Monitorea logs si hay problemas
- El listener se reconecta automáticamente si se desconecta

### Al cerrar:
```
1. Ejecuta: detener_listener.bat
2. Cierra XAMPP (opcional)
```

---

## 💡 Tips

### Iniciar con Windows
Crea acceso directo de `iniciar_sistema_completo.bat` en:
```
C:\ProgramData\Microsoft\Windows\Start Menu\Programs\Startup
```

### Atajo de teclado
Crea acceso directo en escritorio y asigna tecla (ej: Ctrl+Alt+V)

### Monitoreo remoto
El sistema es accesible desde cualquier dispositivo en la red local:
```
http://[IP-DE-LA-PC]/vendigbox.c-onlineweb.net/
```

---

## 📝 Notas Importantes

1. **Espera 200ms entre comandos** - El hardware lo requiere
2. **El listener se reconecta automáticamente** si se desconecta
3. **Los comandos son case-sensitive** - Siempre MAYÚSCULAS
4. **El formato incluye `\r\n`** al final de cada comando
5. **Cada moneda se dispensa individualmente** - No en lote

---

## 🆘 Soporte

### Logs
- Listener: `admin/dist/logs/monedero_listener.log`
- Apache: `C:\xampp\apache\logs\error.log`
- PHP: Ver en `test_dispensar_cambio.php` (consola del navegador)

### Comandos de diagnóstico
```powershell
# Ver puertos COM disponibles
[System.IO.Ports.SerialPort]::getportnames()

# Verificar procesos PHP
Get-Process | Where-Object {$_.ProcessName -eq "php"}

# Ver últimos 50 logs
Get-Content admin\dist\logs\monedero_listener.log -Tail 50
```

---

## ✅ Checklist de Verificación

- [ ] XAMPP (Apache + MySQL) corriendo
- [ ] Listener del monedero iniciado
- [ ] Hardware conectado al puerto COM5
- [ ] Monedas/billetes cargados en los tubos
- [ ] Test de dispensado funcionando
- [ ] Venta de prueba exitosa
- [ ] Cambio dispensado correctamente

---

**Sistema listo para usar** ✅  
**Última actualización**: 9 de Febrero de 2026  
**Versión**: 2.0 - Protocolo VendigBox Integrado
