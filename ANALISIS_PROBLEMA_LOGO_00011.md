# 🎯 ANÁLISIS DETALLADO - Problema: No se trae información del Pedido LOGO

## ESTADO ACTUAL

### Flujo de datos:
1. Usuario hace click en "Recibo de Logo" → Ejecuta `verFacturaLogo('LOGO-00011')`
2. Se hace fetch a `/registros/LOGO-00011`
3. El controlador `RegistroOrdenQueryController::show()` busca en LogoPedido
4. Devuelve JSON con los datos
5. Se dispara evento `load-order-detail-logo` 
6. El componente llena los campos HTML

### Datos que DEBERÍA traer según BD:

```sql
SELECT * FROM logo_pedidos WHERE numero_pedido = 'LOGO-00011'
```

Campos disponibles en BD:
- ✅ `id` - ID del registro
- ✅ `numero_pedido` - LOGO-00011 (ÚNICA KEY)
- ✅ `cliente` - Nombre del cliente
- ✅ `asesora` - Nombre de la asesora
- ✅ `descripcion` - Descripción del logo
- ✅ `tecnicas` - JSON array ["BORDADO", "SUBLIMACION"]
- ✅ `ubicaciones` - JSON array con ubicaciones
- ✅ `forma_de_pago` - CONTADO, CRÉDITO, etc.
- ✅ `encargado_orden` - Persona encargada
- ✅ `fecha_de_creacion_de_orden` - TIMESTAMP ← IMPORTANTE
- ✅ `created_at` - Timestamp de creación
- ✅ `observaciones_tecnicas` - Texto de observaciones

---

## PROBLEMA ENCONTRADO

En `RegistroOrdenQueryController@show()` (línea 243-320):

```php
public function show($pedido)
{
    // Primero, intentar buscar en LogoPedido
    $logoPedido = \App\Models\LogoPedido::where('numero_pedido', $pedido)->first();
    
    if ($logoPedido) {
        $logoPedidoArray = $logoPedido->toArray();
        
        // ... código para completar desde PedidoProduccion si existe...
        
        // PASO 3: Asegurar valores finales
        $logoPedidoArray['numero_pedido'] = $logoPedido->numero_pedido ?? $pedido;
        $logoPedidoArray['cliente'] = $logoPedidoArray['cliente'] ?: '-';
        $logoPedidoArray['asesora'] = $logoPedidoArray['asesora'] ?: '-';
        $logoPedidoArray['descripcion'] = $logoPedido->descripcion ?? '';
        $logoPedidoArray['fecha_de_creacion_de_orden'] = $logoPedidoArray['fecha_de_creacion_de_orden'] ?? null;
        // ... más campos ...
        
        return response()->json($logoPedidoArray);
    }
}
```

### ¿Qué FALTA?

El controlador SÍ está trayendo los datos correctos, PERO:

1. **No hay un "logeo" claro de qué se está retornando**
2. **El componente espera ciertos campos que podrían no estar siendo devueltos correctamente**
3. **Los datos JSON (tecnicas, ubicaciones) podrían venir sin parsear**

---

## FLUJO DEL COMPONENTE

En `order-detail-modal-manager.js` (línea 648):

```javascript
window.addEventListener('load-order-detail-logo', function(event) {
    const orden = event.detail;
    
    // Rellenar campos
    const asesoraSpan = document.querySelector('#order-detail-modal-wrapper-logo #asesora-value');
    if (asesoraSpan) {
        asesoraSpan.textContent = orden.asesora || '-'; // ← Aquí usa orden.asesora
    }
    
    // Fecha
    if (orden.fecha_de_creacion_de_orden) {
        const fecha = new Date(orden.fecha_de_creacion_de_orden);
        // ... rellenar cajas de fecha ...
    }
});
```

---

## SOLUCIÓN RECOMENDADA

Modificar `RegistroOrdenQueryController@show()` para asegurar que:

1. ✅ Los datos de LogoPedido se completen correctamente
2. ✅ La fecha se devuelva siempre en formato ISO (YYYY-MM-DD HH:MM:SS)
3. ✅ Los JSON fields se devuelvan parseados (como arrays, no strings)
4. ✅ Agregar logs para debug

---

## CAMPOS QUE EL COMPONENTE ESPERA

Basado en `order-detail-modal-logo.blade.php` y `order-detail-modal-manager.js`:

```javascript
// Campos esperados:
{
    numero_pedido: "LOGO-00011",
    cliente: "ACME Corp",
    asesora: "María García",              // ← Sin esto no se muestra
    descripcion: "Logo bordado en pecho",
    tecnicas: ["BORDADO", "SUBLIMACION"],
    ubicaciones: [...],                    // ← JSON array
    forma_de_pago: "CONTADO",
    encargado_orden: "Juan Pérez",
    fecha_de_creacion_de_orden: "2025-12-20 14:30:00",  // ← SIN ESTO NO MUESTRA FECHA
    estado: "pendiente",
    area: "creacion_de_orden",
    prendas: [],                           // ← Para mostrar cantidad
    observaciones_tecnicas: "..."
}
```

---

## VERIFICACIÓN RÁPIDA EN BD

Para verificar si LOGO-00011 tiene datos:

```sql
SELECT 
    numero_pedido,
    cliente,
    asesora,
    descripcion,
    tecnicas,
    ubicaciones,
    forma_de_pago,
    encargado_orden,
    fecha_de_creacion_de_orden,
    estado,
    area,
    observaciones_tecnicas
FROM logo_pedidos
WHERE numero_pedido = 'LOGO-00011';
```

---

## ACCIONES REQUERIDAS

1. ✅ Verificar que LogoPedido tiene datos en BD
2. ✅ Asegurar que el controlador devuelve TODOS estos campos
3. ✅ Verificar que los campos JSON se devuelven parseados
4. ✅ Confirmar que `fecha_de_creacion_de_orden` está en formato ISO
5. ⚙️ Si falta algún campo, agregarlo al array de respuesta

