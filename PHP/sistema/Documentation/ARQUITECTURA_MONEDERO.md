# 🏗️ ARQUITECTURA DEL SISTEMA DE MONEDERO

## 📊 **DIAGRAMA DE FLUJO**

```
┌─────────────────────────────────────────────────────────────┐
│                    USUARIO INSERTA MONEDA                    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│              MonederoMonitor.exe (SIEMPRE ACTIVO)            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │         monedero_listener.php (PROCESO ÚNICO)         │  │
│  ├──────────────────────────────────────────────────────┤  │
│  │  📥 DETECTAR MONEDAS (Loop principal - cada 50ms)     │  │
│  │     ├─ Lee COM5 vía PowerShell                       │  │
│  │     ├─ Detecta moneda insertada                      │  │
│  │     ├─ Actualiza saldo_actual.json                   │  │
│  │     └─ Actualiza coin_inventory.log                  │  │
│  │                                                        │  │
│  │  📤 DISPENSAR CAMBIO (Verifica cada 500ms)            │  │
│  │     ├─ Lee monedero_dispense_queue.json              │  │
│  │     ├─ Si hay comando pendiente:                     │  │
│  │     │   ├─ Envía INT000[HEX]003 a COM5               │  │
│  │     │   ├─ Actualiza coin_inventory.log              │  │
│  │     │   └─ Escribe monedero_dispense_response.json   │  │
│  │     └─ Elimina archivo de cola                       │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│               cart.php (INTERFAZ DE USUARIO)                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  1. Polling cada 150ms a monedero_api.php           │  │
│  │     └─ Lee saldo_actual.json                         │  │
│  │                                                        │  │
│  │  2. Al hacer venta:                                   │  │
│  │     ├─ Calcula cambio necesario                      │  │
│  │     └─ Llama a monedero_api.php?action=dispensar     │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│            monedero_api.php (BACKEND API - SIN COM5)         │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  get_saldo:                                           │  │
│  │     └─ Lee saldo_actual.json                         │  │
│  │                                                        │  │
│  │  dispensar_cambio:                                    │  │
│  │     ├─ Calcula desglose óptimo (algoritmo variado)   │  │
│  │     ├─ Escribe monedero_dispense_queue.json          │  │
│  │     ├─ Espera respuesta (max 10 segundos)            │  │
│  │     ├─ Lee monedero_dispense_response.json           │  │
│  │     └─ Retorna JSON al frontend                      │  │
│  │                                                        │  │
│  │  register_coin_received:                              │  │
│  │     └─ Actualiza coin_inventory.log                  │  │
│  └──────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔑 **REGLA DE ORO: UN SOLO PROCESO CONTROLA COM5**

### ❌ **ANTES (Problemático):**
```
monedero_listener.php → Lee COM5 → Detecta monedas ✅
monedero_api.php      → Intenta usar COM5 → ERROR: Puerto ocupado ❌
```

### ✅ **AHORA (Correcto):**
```
monedero_listener.php → DUEÑO EXCLUSIVO de COM5
    ├─ Detecta monedas (loop continuo)
    └─ Dispensa cambio (cuando hay comandos en cola)

monedero_api.php → NO toca COM5, usa archivos de comunicación
```

---

## 📁 **ARCHIVOS DE COMUNICACIÓN**

### **1. saldo_actual.json**
**Propósito:** Almacena el saldo detectado por el listener  
**Escribe:** monedero_listener.php  
**Lee:** monedero_api.php, cart.php (vía polling)

```json
{
  "saldo": 25.0,
  "timestamp": 1735556789,
  "fecha": "2026-04-29 13:28:30"
}
```

### **2. coin_inventory.log**
**Propósito:** Inventario de monedas disponibles  
**Escribe:** monedero_listener.php (INGRESO y DISPENSADO)  
**Lee:** monedero_api.php (para calcular desglose)

```json
{
  "timestamp": "2026-04-29 13:28:30",
  "total_pesos": 17,
  "denominaciones": {
    "1": 7,
    "2": 5,
    "5": 0,
    "10": 0,
    "20": 0
  },
  "log": [
    {
      "timestamp": "2026-04-29 13:20:15",
      "tipo": "INGRESO",
      "denominacion": 10,
      "cantidad": 1
    },
    {
      "timestamp": "2026-04-29 13:28:30",
      "tipo": "DISPENSADO",
      "denominacion": 2,
      "cantidad": 1
    }
  ]
}
```

### **3. monedero_dispense_queue.json**
**Propósito:** Cola de comandos de dispensado  
**Escribe:** monedero_api.php (cuando necesita dispensar)  
**Lee:** monedero_listener.php (cada 500ms)

```json
{
  "timestamp": 1735556789.123,
  "monto": 5,
  "desglose": {
    "2": 1,
    "1": 3
  },
  "status": "PENDING"
}
```

### **4. monedero_dispense_response.json**
**Propósito:** Respuesta del listener después de dispensar  
**Escribe:** monedero_listener.php (después de ejecutar)  
**Lee:** monedero_api.php (espera max 10 segundos)

```json
{
  "success": true,
  "mensaje": "Cambio dispensado exitosamente: $5",
  "timestamp": 1735556790.456,
  "fecha": "2026-04-29 13:28:31",
  "monto_solicitado": 5,
  "total_dispensado": 5,
  "comandos_enviados": [
    {
      "denominacion": 2,
      "comando": "INT0002003",
      "iteracion": 1,
      "respuesta": "SUCCESS"
    },
    {
      "denominacion": 1,
      "comando": "INT0001003",
      "iteracion": 1,
      "respuesta": "SUCCESS"
    }
  ]
}
```

---

## 🔄 **FLUJO DE DISPENSADO COMPLETO**

### **Paso 1: Usuario hace una venta con cambio**
```javascript
// cart.php
Cart.handleCashPayment()
  └─ Detecta saldo: $50
  └─ Total venta: $35
  └─ Cambio: $15
  └─ Llama: MonederoIntegration.dispensarCambioFisico(15)
```

### **Paso 2: Frontend llama al API**
```javascript
fetch('monedero_api.php?action=dispensar_cambio', {
  method: 'POST',
  body: JSON.stringify({ monto: 15 })
})
```

### **Paso 3: API calcula y escribe comando**
```php
// monedero_api.php
dispensarCambio() {
  1. Carga inventario: coin_inventory.log
  2. Calcula desglose óptimo (algoritmo variado):
     → $15 = $10×1 + $5×1
  3. Escribe comando en: monedero_dispense_queue.json
  4. ESPERA respuesta (max 10 segundos)
}
```

### **Paso 4: Listener detecta y procesa comando**
```php
// monedero_listener.php
Loop principal (cada 50ms):
  ├─ Detectar monedas (prioritario)
  └─ Cada 500ms: Verificar comandos de dispensar
      └─ Si encuentra monedero_dispense_queue.json:
          ├─ Envía INT000A003 (dispensa $10)
          ├─ Espera 300ms
          ├─ Envía INT0005003 (dispensa $5)
          ├─ Actualiza coin_inventory.log
          └─ Escribe monedero_dispense_response.json
```

### **Paso 5: API lee respuesta y retorna al frontend**
```php
// monedero_api.php
while (timeout < 10 segundos) {
  if (existe monedero_dispense_response.json) {
    return JSON al frontend;
  }
  sleep(100ms);
}
```

### **Paso 6: Frontend muestra resultado**
```javascript
// cart.php
if (response.success) {
  Swal.fire('Cambio dispensado: $15');
  procesarVenta(); // Guardar en base de datos
}
```

---

## ⚡ **RENDIMIENTO Y TIMING**

| Operación | Frecuencia | Tiempo |
|-----------|-----------|--------|
| Detectar monedas | Continuo (50ms) | < 5ms |
| Verificar comandos dispensar | Cada 500ms | < 2ms |
| Enviar comando a COM5 | Por moneda | ~100ms |
| Delay entre monedas | Fijo | 300ms |
| Timeout respuesta API | Máximo | 10s |

**Ejemplo:** Dispensar $15 ($10 + $5) = 2 monedas × 400ms = ~800ms total

---

## 🚨 **MANEJO DE ERRORES**

### **Error: Listener no responde (timeout)**
```json
{
  "error": true,
  "mensaje": "El listener no respondió. Verifica que MonederoMonitor.exe esté corriendo.",
  "timeout": true
}
```
**Solución:** Ejecutar `MonederoMonitor.bat`

### **Error: Puerto COM5 ocupado**
```
Error: Se ha denegado el acceso al puerto 'COM5'
```
**Solución:** Solo debería ocurrir si hay OTRO programa usando COM5 (no el listener)

### **Error: Inventario insuficiente**
```json
{
  "error": true,
  "mensaje": "Cambio insuficiente. Falta: $8.00"
}
```
**Solución:** Recargar monedero con más monedas

---

## 🔧 **MANTENIMIENTO**

### **Verificar que el sistema esté funcionando:**
```bash
# 1. Verificar que MonederoMonitor.exe esté corriendo
tasklist | findstr "MonederoMonitor"

# 2. Verificar logs del listener
tail -f admin/dist/logs/monedero_listener.log

# 3. Verificar estado de archivos
dir admin\dist\logs\*.json

# 4. Test de dispensado
http://localhost/vendingbox.online/sistema/test_dispensar_debug.php?monto=5
```

### **Reiniciar el sistema:**
```bash
# 1. Cerrar MonederoMonitor
taskkill /F /IM MonederoMonitor.exe

# 2. Limpiar archivos de cola
del admin\dist\logs\monedero_dispense_*.json

# 3. Reiniciar
MonederoMonitor.bat
```

---

## 📚 **ARCHIVOS CLAVE**

| Archivo | Rol | Puerto COM5 |
|---------|-----|-------------|
| `MonederoMonitor.exe` | Supervisor (ejecuta listener) | ❌ No |
| `monedero_listener.php` | **DUEÑO** de COM5 | ✅ **SÍ** |
| `monedero_api.php` | Backend API | ❌ No |
| `cart.php` | Frontend | ❌ No |

---

## 🎯 **RESUMEN**

✅ **UN SOLO proceso** (monedero_listener.php) controla COM5  
✅ **Comunicación por archivos** (no conflictos de puerto)  
✅ **Detección Y dispensado** en el mismo proceso  
✅ **Timeout de 10 segundos** para respuestas  
✅ **Algoritmo variado** para distribución de cambio  
✅ **Logs completos** de todas las operaciones  

**¡El sistema es robusto, eficiente y sin conflictos!** 🚀
