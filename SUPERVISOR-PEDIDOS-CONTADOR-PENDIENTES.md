# ✅ SISTEMA DE CONTADOR DE ÓRDENES PENDIENTES - SUPERVISOR PEDIDOS

## 🎯 Objetivo
En el rol SUPERVISOR_PEDIDOS, mostrar un contador de órdenes pendientes de aprobación que se actualiza en tiempo real en el sidebar.

## ✅ IMPLEMENTACIÓN COMPLETADA

### 1. **SupervisorPedidosController.php**
- ✅ Agregado método `ordenesPendientesCount()` que retorna JSON con contador
- Endpoint: `GET /supervisor-pedidos/ordenes-pendientes-count`
- Cuenta órdenes con:
  - `aprobado_por_supervisor_en` = NULL (no aprobadas)
  - `estado` != 'Anulada' (no anuladas)
  - `cotizacion_id` != NULL (con cotización asociada)

### 2. **routes/web.php**
- ✅ Agregada ruta: `Route::get('/ordenes-pendientes-count', ...)->name('supervisor-pedidos.ordenes-pendientes-count')`

### 3. **sidebar-supervisor-pedidos.blade.php**
- ✅ Agregado badge `#ordenesPendientesCount` al menú "Pendientes"
- ✅ Badge se muestra solo si hay órdenes pendientes

### 4. **supervisor-pedidos/layout.blade.php**
- ✅ Agregado script inline que:
  - Carga el contador al cargar la página
  - Recarga cada 30 segundos automáticamente
  - Actualiza el badge en tiempo real

## 📊 FLUJO DE FUNCIONAMIENTO

1. **Usuario accede a Supervisor de Pedidos**
   - Script carga contador de órdenes pendientes
   - Fetch a `/supervisor-pedidos/ordenes-pendientes-count`
   - Badge se actualiza con el número

2. **Usuario aprueba una orden**
   - Se ejecuta `aprobarOrden()` en el controlador
   - Campo `aprobado_por_supervisor_en` se actualiza
   - Orden desaparece de "Pendientes"
   - Cada 30 segundos, el contador se recalcula
   - Badge se decrementa automáticamente

3. **Contador se actualiza en tiempo real**
   - Cada 30 segundos se recarga el contador
   - Si llega a 0, el badge se oculta
   - Si hay pendientes, se muestra el número

## 🔄 CRITERIOS DE ÓRDENES PENDIENTES

**Mostradas en Pendientes:**
- ✅ `aprobado_por_supervisor_en` = NULL (no aprobadas)
- ✅ `estado` != 'Anulada' (no anuladas)
- ✅ `cotizacion_id` != NULL (con cotización)

**NO mostradas en Pendientes:**
- ❌ Órdenes ya aprobadas (`aprobado_por_supervisor_en` != NULL)
- ❌ Órdenes anuladas (`estado` = 'Anulada')
- ❌ Órdenes sin cotización (`cotizacion_id` = NULL)

## 🎨 DISEÑO DEL BADGE

- **Ubicación**: Sidebar, menú "Pendientes" (sección "Estado de Aprobación")
- **Estilo**: Badge rojo con número blanco
- **Comportamiento**: 
  - Se muestra solo si hay pendientes (count > 0)
  - Se oculta si no hay pendientes (count = 0)
  - Se actualiza cada 30 segundos

## 📝 ARCHIVOS MODIFICADOS

1. **app/Http/Controllers/SupervisorPedidosController.php**
   - Línea 587-617: Agregado método `ordenesPendientesCount()`

2. **routes/web.php**
   - Línea 437-438: Agregada ruta para contador

3. **resources/views/components/sidebars/sidebar-supervisor-pedidos.blade.php**
   - Línea 20-27: Agregado badge al menú Pendientes

4. **resources/views/supervisor-pedidos/layout.blade.php**
   - Línea 373: Llamada a `cargarContadorOrdenesPendientes()` en DOMContentLoaded
   - Línea 376-395: Función para cargar y actualizar contador

## ✨ CARACTERÍSTICAS

✅ Contador de órdenes pendientes en tiempo real
✅ Badge se actualiza automáticamente cada 30 segundos
✅ Solo cuenta órdenes pendientes de aprobación
✅ Badge se oculta si no hay pendientes
✅ Integrado en el sidebar del supervisor
✅ Sin necesidad de recargar la página
✅ Endpoint JSON seguro (requiere autenticación)

## 🚀 CÓMO FUNCIONA

**Paso 1: Usuario accede a Supervisor de Pedidos**
```
GET /supervisor-pedidos/
→ Carga layout supervisor-pedidos/layout.blade.php
→ Script ejecuta cargarContadorOrdenesPendientes()
```

**Paso 2: Script obtiene contador**
```
fetch('/supervisor-pedidos/ordenes-pendientes-count')
→ SupervisorPedidosController::ordenesPendientesCount()
→ Retorna JSON: { success: true, count: 3 }
```

**Paso 3: Badge se actualiza**
```
badge.textContent = 3
badge.style.display = 'inline-flex'
```

**Paso 4: Recarga cada 30 segundos**
```
setInterval(cargarContadorOrdenesPendientes, 30000)
→ Vuelve a obtener el contador
→ Si cambió, actualiza el badge
```

## ✅ GARANTÍAS

✅ Solo cuenta órdenes PENDIENTES de aprobación
✅ Contador se actualiza en tiempo real
✅ Badge se oculta cuando no hay pendientes
✅ Funciona sin recargar la página
✅ Endpoint JSON seguro (requiere autenticación)
✅ Compatible con todos los navegadores modernos

## 📊 RESPUESTA DEL ENDPOINT

```json
{
  "success": true,
  "count": 3,
  "message": "Hay 3 orden(es) pendiente(s) de aprobación"
}
```

Si no hay pendientes:
```json
{
  "success": true,
  "count": 0,
  "message": "No hay órdenes pendientes"
}
```

## 🧪 CÓMO PROBAR

### Test 1: Verificar que el badge se muestra
```
1. Ir a /supervisor-pedidos/
2. Buscar el badge rojo en el menú "Pendientes"
3. El badge debe mostrar el número de órdenes pendientes
4. Si no hay pendientes, el badge debe estar oculto
```

### Test 2: Verificar que el contador se actualiza
```
1. Abrir /supervisor-pedidos/ en 2 navegadores
2. En el navegador 1, aprobar una orden
3. En el navegador 2, esperar 30 segundos
4. El badge debe decrementarse automáticamente
```

### Test 3: Verificar el endpoint JSON
```
1. Abrir DevTools (F12)
2. Ir a la pestaña Network
3. Recargar la página
4. Buscar la petición a /supervisor-pedidos/ordenes-pendientes-count
5. Verificar que retorna JSON con count > 0
```

## 📅 Fecha: 4 de Diciembre de 2025
## 🎯 Estado: COMPLETADO ✅
