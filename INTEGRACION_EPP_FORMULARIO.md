# Integración de EPP en Formulario de Crear Pedido

## Problema Identificado
Cuando un usuario agregaba EPP a través del modal en el formulario de crear nuevo pedido (`/asesores/pedidos-produccion/crear-nuevo`), los EPP se mostraban visualmente pero **NO se incluían en el JSON enviado al backend**. Esto causaba que los EPP no se guardaran en la base de datos.

## Root Cause
El código JavaScript para agregar EPP solo creaba elementos visuales en `lista-items-pedido` pero **no agregaba los EPP al arreglo `window.itemsPedido`**, que es el arreglo usado por la función `recolectarDatosPedido()` para construir el JSON del pedido antes de enviarlo.

## Solución Implementada

### 1. Frontend - Agregar EPP a `window.itemsPedido`
**Archivo**: `public/js/modulos/crear-pedido/modales/modal-agregar-epp.js`

#### Cambios en `crearItemEPP()` (línea ~1050):
```javascript
// ✅ AGREGAR ITEM A window.itemsPedido PARA QUE SE INCLUYA EN EL FORMULARIO
if (!window.itemsPedido) {
    window.itemsPedido = [];
}

// Crear objeto EPP en el formato esperado por el backend
const itemEPP = {
    tipo: 'epp',
    epp_id: id,
    nombre: nombre,
    codigo: codigo,
    categoria: categoria,
    talla: talla,
    cantidad: cantidad,
    observaciones: observaciones || null,
    imagenes: imagenes || [],
    tallas_medidas: talla, // Campo requerido por PedidoEppService
};

console.log('✅ Agregando EPP a window.itemsPedido:', itemEPP);
window.itemsPedido.push(itemEPP);
console.log('📊 Total items en pedido después de EPP:', window.itemsPedido.length);
```

#### Cambios en `eliminarItemEPP()` (línea ~1100):
```javascript
// ✅ REMOVER TAMBIÉN DE window.itemsPedido
if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
    const indexToRemove = window.itemsPedido.findIndex(item => item.tipo === 'epp' && item.epp_id === eppId);
    if (indexToRemove !== -1) {
        window.itemsPedido.splice(indexToRemove, 1);
        console.log('✅ EPP removido de window.itemsPedido. Total items ahora:', window.itemsPedido.length);
    }
}
```

#### Cambios en `agregarEPPAlPedido()` (línea ~901):
```javascript
if (editandoEPPId) {
    // Estamos editando: eliminar el item anterior visual y de window.itemsPedido
    const itemAnterior = document.querySelector(`.item-epp[data-item-id="${editandoEPPId}"]`);
    if (itemAnterior) {
        itemAnterior.remove();
    }
    
    // ✅ REMOVER DEL ARRAY itemsPedido TAMBIÉN
    if (window.itemsPedido && Array.isArray(window.itemsPedido)) {
        const indexToRemove = window.itemsPedido.findIndex(item => item.tipo === 'epp' && item.epp_id === editandoEPPId);
        if (indexToRemove !== -1) {
            window.itemsPedido.splice(indexToRemove, 1);
            console.log('✅ EPP antiguo removido durante edición. Total items ahora:', window.itemsPedido.length);
        }
    }
    
    editandoEPPId = null; // Limpiar modo edición
}
```

### 2. Frontend - Procesar EPP en `recolectarDatosPedido()`
**Archivo**: `public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`

#### Cambios en función `recolectarDatosPedido()` (línea ~1248):
```javascript
// ✅ Si es EPP, incluir los datos específicos
if (item.tipo === 'epp') {
    baseItem.epp_id = item.epp_id;
    baseItem.codigo = item.codigo;
    baseItem.categoria = item.categoria;
    baseItem.talla = item.talla;
    baseItem.cantidad = item.cantidad;
    baseItem.observaciones = item.observaciones;
    baseItem.tallas_medidas = item.tallas_medidas; // Campo requerido por el backend
    console.log(`🛡️ [Item ${itemIndex}] EPP procesado:`, baseItem);
}
```

### 3. Backend - Procesar y Guardar EPP
**Archivo**: `app/Http/Controllers/Asesores/CrearPedidoEditableController.php`

#### Importar Servicio EPP (línea ~10):
```php
use App\Services\PedidoEppService; // ✅ IMPORTAR SERVICIO EPP
```

#### Inyectar Servicio en Constructor (línea ~24):
```php
public function __construct(
    // ... servicios existentes ...
    private PedidoEppService $eppService, // ✅ INYECTAR SERVICIO EPP
) {}
```

#### Procesar EPP en `crearPedido()` (línea ~290-325):
```php
// ✅ ARRAY PARA EPPS
$eppsParaGuardar = [];

foreach ($validated['items'] as $itemIndex => $item) {
    // Determinar el tipo de item
    $tipo = $item['tipo'] ?? 'cotizacion';
    
    // ✅ SI ES EPP, PROCESARLO SEPARADAMENTE
    if ($tipo === 'epp') {
        \Log::info('🛡️ [CrearPedidoEditableController] Procesando EPP:', $item);
        
        // Construir objeto EPP para guardar
        $eppData = [
            'epp_id' => $item['epp_id'] ?? null,
            'nombre' => $item['nombre'] ?? '',
            'codigo' => $item['codigo'] ?? '',
            'categoria' => $item['categoria'] ?? '',
            'talla' => $item['talla'] ?? '',
            'cantidad' => $item['cantidad'] ?? 0,
            'observaciones' => $item['observaciones'] ?? null,
            'imagenes' => $item['imagenes'] ?? [],
            'tallas_medidas' => $item['tallas_medidas'] ?? $item['talla'],
        ];
        
        $eppsParaGuardar[] = $eppData;
        
        // Contar cantidad para total del pedido
        $cantidadTotal += (int)($item['cantidad'] ?? 0);
        
        // Pasar al siguiente item (NO procesar como prenda)
        continue;
    }
    
    // ... procesar prendas normalmente ...
}
```

#### Guardar EPP en BD (línea ~780):
```php
// ✅ GUARDAR EPPS SI LOS HAY
if (!empty($eppsParaGuardar)) {
    \Log::info('🛡️ Guardando EPPs del pedido:', [
        'cantidad_epps' => count($eppsParaGuardar),
        'epps' => array_map(function($e) {
            return [
                'nombre' => $e['nombre'],
                'cantidad' => $e['cantidad'],
                'talla' => $e['talla'],
            ];
        }, $eppsParaGuardar),
    ]);
    
    try {
        $this->eppService->guardarEppsDelPedido($pedido, $eppsParaGuardar);
        \Log::info('✅ EPPs guardados exitosamente para pedido:', ['pedido_id' => $pedido->id]);
    } catch (\Exception $e) {
        \Log::error('❌ Error guardando EPPs:', [
            'error' => $e->getMessage(),
            'pedido_id' => $pedido->id,
        ]);
        // No lanzar error, solo loguear (los EPPs no bloquean la creación del pedido)
    }
}
```

## Flujo Completo

1. **Usuario accede**: `/asesores/pedidos-produccion/crear-nuevo`
2. **Usuario agrega EPP**: 
   - Click en selector de tipo "EPP"
   - Modal se abre (modal-agregar-epp.js)
   - Usuario selecciona EPP, talla, cantidad, imagenes
   - Presiona "Agregar" → Llama `agregarEPPAlPedido()`
3. **JavaScript agrega EPP**:
   - `crearItemEPP()` crea elemento visual en `lista-items-pedido`
   - **NUEVO**: También agrega al array `window.itemsPedido`
4. **Usuario envía formulario**:
   - Click en "Guardar Pedido"
   - `manejarSubmitFormulario()` invoca `recolectarDatosPedido()`
   - **NUEVO**: Procesa items tipo 'epp' con sus campos específicos
   - JSON incluye EPP con: tipo, epp_id, nombre, codigo, categoria, talla, cantidad, observaciones, imagenes
5. **Backend recibe y procesa**:
   - `CrearPedidoEditableController::crearPedido()`
   - **NUEVO**: Detecta items tipo 'epp'
   - **NUEVO**: Guarda EPP usando `PedidoEppService->guardarEppsDelPedido()`
   - Responde con pedido creado
6. **BD actualizada**: 
   - Tabla `pedido_epp` contiene nuevo registro
   - Tabla `pedido_epp_imagenes` contiene imágenes si las hay

## Cambios de Archivos

### Archivos Modificados:
1. `public/js/modulos/crear-pedido/modales/modal-agregar-epp.js`
   - Agregó: Agregar/remover items EPP de `window.itemsPedido`
   
2. `public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js`
   - Agregó: Procesar items tipo 'epp' en `recolectarDatosPedido()`
   
3. `app/Http/Controllers/Asesores/CrearPedidoEditableController.php`
   - Agregó: Import y inyección de `PedidoEppService`
   - Agregó: Separación y procesamiento de items EPP
   - Agregó: Guardado de EPP después de prendas

### Archivos NO modificados (pero afectados):
- `resources/views/asesores/pedidos/crear-pedido-nuevo.blade.php` (Ya tenía modal EPP integrado)
- `app/Services/PedidoEppService.php` (Servicio ya existente y funcional)

## Validaciones y Logs

El código incluye múltiples puntos de validación y logging:

**Frontend**:
- ✅ Log cuando EPP se agrega a `window.itemsPedido`
- ✅ Log del total de items después de agregar EPP
- ✅ Log cuando EPP se procesa en `recolectarDatosPedido()`
- ✅ Log cuando EPP se remueve del array

**Backend**:
- ✅ Log cuando se detecta item tipo 'epp'
- ✅ Log detallado de los EPP a guardar
- ✅ Log de éxito al guardar EPP
- ✅ Log de error si falla el guardado (sin bloquear creación del pedido)

## Testing

Para verificar que funciona:

1. Acceder a `/asesores/pedidos-produccion/crear-nuevo`
2. Completar datos básicos (cliente, asesora, forma de pago)
3. Agregar un EPP:
   - Click en selector → "EPP"
   - Agregar EPP desde modal
   - Verificar que aparece en `lista-items-pedido`
4. Enviar formulario
5. Verificar en logs backend que EPP fue procesado
6. Verificar en BD que `pedido_epp` contiene el nuevo registro

## Notas Importantes

- Los EPP se guardan **después** de las prendas, no bloqueando la creación del pedido
- Si falla el guardado de EPP, se loguea el error pero el pedido se crea igual
- El campo `cantidad` del EPP se suma al `cantidad_total` del pedido
- Las imágenes del EPP se guardan en la tabla `pedido_epp_imagenes`
- El formato de los datos EPP es compatible con el existente `PedidoEppService`

