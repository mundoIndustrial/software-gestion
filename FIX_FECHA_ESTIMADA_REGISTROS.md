# Fix: Real-Time Fecha Estimada en Tabla de Registros (/registros)

## Problema Identificado 📋
En la tabla de `/registros`, cuando se actualizaba el `dia_de_entrega`, la `fecha_estimada_de_entrega` se calculaba correctamente en la base de datos pero **NO se mostraba en tiempo real en la tabla**. El usuario tenía que hacer refresh para ver el cambio.

## Causa Raíz 🔍

### Problema 1: Columna sin identificador CSS
La vista `orders/index.blade.php` (usada por `/registros`) tenía la columna de fecha estimada pero sin un selector CSS único para identificarla en el real-time listener.

### Problema 2: Real-time Listener no manejaba el campo
El archivo `realtime-listeners.js` NO tenía un handler para `fecha_estimada_de_entrega`. Solo manejaba campos como `estado`, `area`, `dia_de_entrega` y `novedades`.

### Problema 3: Broadcast no incluía el campo calculado
El controlador `RegistroOrdenController::update()` estaba haciendo broadcast usando `$validatedData` (datos de entrada), NO los datos calculados. Cuando se actualizaba `dia_de_entrega`, el servicio calculaba `fecha_estimada_de_entrega` automáticamente, pero **no lo incluía en el array que se pasaba al broadcast**.

---

## Solución Implementada ✅

### 1. Agregar Identificadores a la Vista
**Archivo**: `resources/views/orders/index.blade.php`

**Cambio**: Agregué clases y atributos para identificar la celda de fecha estimada:
```html
<!-- Antes: -->
<div class="table-cell" style="flex: 0 0 180px;">
    <div class="cell-content" style="justify-content: flex-start;">
        <span>{{ ... }}</span>
    </div>
</div>

<!-- Después: -->
<div class="table-cell fecha-estimada-cell" style="flex: 0 0 180px;" 
     data-fecha-estimada="{{ $orden->fecha_estimada_de_entrega ? $orden->fecha_estimada_de_entrega->format('d/m/Y') : '-' }}">
    <div class="cell-content" style="justify-content: flex-start;">
        <span class="fecha-estimada-span">{{ ... }}</span>
    </div>
</div>
```

### 2. Actualizar Real-Time Listener
**Archivo**: `public/js/orders js/realtime-listeners.js`

**Cambios**:
- Agregué handler para el campo `fecha_estimada_de_entrega`
- Soporta ambas vistas:
  - `supervisor-pedidos/index`: Celda con clase `.fecha-estimada`
  - `orders/index` (/registros): Celda con clase `.fecha-estimada-cell`
- Formatea la fecha de ISO a `d/m/Y`

```javascript
else if (field === 'fecha_estimada_de_entrega') {
    // Buscar celda en ambas vistas
    let fechaCell = row.querySelector('.fecha-estimada');
    if (!fechaCell) {
        fechaCell = row.querySelector('.fecha-estimada-cell');
    }
    
    if (fechaCell && ordenData.fecha_estimada_de_entrega !== undefined) {
        const fechaFormato = ordenData.fecha_estimada_de_entrega 
            ? this._formatFecha(ordenData.fecha_estimada_de_entrega)
            : '-';
        
        // Para supervisor-pedidos
        if (fechaCell.classList.contains('fecha-estimada')) {
            fechaCell.textContent = fechaFormato;
        }
        
        // Para orders/index
        if (fechaCell.classList.contains('fecha-estimada-cell')) {
            const span = fechaCell.querySelector('.fecha-estimada-span');
            if (span) {
                span.textContent = fechaFormato;
            }
        }
    }
}
```

### 3. Asegurar Broadcast Incluya Campos Calculados
**Archivo**: `app/Http/Controllers/RegistroOrdenController.php`

**Cambio**: Modificué el método `update()` para:
- Obtener la orden actualizada con `fresh()` para tener TODOS los valores de la BD
- Detectar cuándo se calculó `fecha_estimada_de_entrega` automáticamente
- Incluir ese campo en el broadcast

```php
// Obtener la orden actualizada con todos los campos calculados
$ordenActualizada = $orden->fresh();

// Preparar campos que fueron realmente actualizados
$changedFields = array_keys($validatedData);

// Si se actualizó dia_de_entrega, añadir fecha_estimada_de_entrega
if (in_array('dia_de_entrega', $changedFields) && 
    !in_array('fecha_estimada_de_entrega', $changedFields)) {
    $changedFields[] = 'fecha_estimada_de_entrega';
}

// Broadcast con la orden actualizada y campos reales
broadcast(new \App\Events\OrdenUpdated($ordenActualizada, 'updated', $changedFields));
```

---

## Flujo Completo Ahora 🔄

```
Usuario actualiza "Días de entrega" en /registros
    ↓
JavaScript envía PATCH a /registros/{pedido} con { dia_de_entrega: 5 }
    ↓
RegistroOrdenController::update() recibe la solicitud
    ↓
RegistroOrdenUpdateService::updateOrder() ejecuta:
    - Llama handleDeliveryDayUpdate()
    - Calcula fecha_estimada_de_entrega (ej: 2026-01-15)
    - Actualiza ambos campos en BD
    ↓
RegistroOrdenController obtiene orden actualizada
    ↓
Controller detecta que dia_de_entrega cambió
    ↓
Controller agrega fecha_estimada_de_entrega a changedFields
    ↓
BROADCAST: OrdenUpdated event con:
    - orden (con fecha_estimada_de_entrega = "2026-01-15T...")
    - changedFields: ['dia_de_entrega', 'fecha_estimada_de_entrega']
    ↓
WebSocket entrega evento a todos los clientes en la tabla /registros
    ↓
realtime-listeners.js recibe OrdenUpdated
    ↓
Para cada campo en changedFields:
    - Busca fila por numero_pedido
    - Llama _updateField()
    ↓
_updateField() para 'fecha_estimada_de_entrega':
    - Busca .fecha-estimada-cell en la fila
    - Obtiene span.fecha-estimada-span
    - Formatea fecha: "2026-01-15" → "15/01/2026"
    - Actualiza contenido del span
    ↓
✨ Usuario ve la fecha actualizada en TIEMPO REAL en la tabla
```

---

## Archivos Modificados

| Archivo | Líneas | Cambio |
|---------|--------|---------|
| `resources/views/orders/index.blade.php` | 211 | Agregadas clases y atributos a celda de fecha |
| `public/js/orders js/realtime-listeners.js` | 84-115 | Handler para fecha_estimada_de_entrega + _formatFecha() |
| `app/Http/Controllers/RegistroOrdenController.php` | 117-143, imports | Broadcast mejorado con orden fresh + campos calculados |

---

## Validación en Logs 🔬

Ahora en `storage/logs/laravel.log` debes ver:
```
Broadcast enviado para pedido 45486 con campos: ["dia_de_entrega","fecha_estimada_de_entrega"]
```

Y en Console del navegador:
```
✅ Día de entrega actualizado: 15
📡 Evento OrdenUpdated recibido
✅ Fecha estimada actualizada en tiempo real: 15/01/2026
```

---

## Pruebas Recomendadas ✓

1. Abre `/registros` en el navegador
2. Localiza un pedido (ej: #45486)
3. Haz clic en el campo "Días de entrega"
4. Cámbialo a un valor diferente (ej: 15)
5. **Espera a ver la fecha actualizada en TIEMPO REAL** (sin hacer refresh)
6. Abre una segunda ventana con `/registros` en otra pestaña
7. Actualiza un pedido desde la primera ventana
8. **Verifica que ambas ventanas se actualicen simultáneamente**

---

## Notas Importantes 📌

- La columna "FECHA ESTIMADA" es de solo lectura - se calcula automáticamente
- El cálculo respeta:
  - Fin de semanas (sábados y domingos)
  - Feriados colombianos
  - La fecha de creación de la orden
- El broadcast se ejecuta automáticamente cada vez que se modifica `dia_de_entrega`
- Si la fecha es `null`, se muestra "-" en lugar de "N/A"

---

**Estado**: ✅ Completo - Listo para producción
**Fecha**: 2025-12-18
**Vistas Afectadas**: `/registros` (orders/index), `/supervisor-pedidos` (supervisor-pedidos/index)
