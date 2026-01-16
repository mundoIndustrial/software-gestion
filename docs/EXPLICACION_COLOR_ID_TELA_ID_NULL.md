# Explicación: Por qué color_id y tela_id llegan NULL

## Problema Reportado
Cuando se crea un pedido nuevo, los campos `color_id` y `tela_id` en las variantes se guardan como `NULL` en la base de datos, en lugar de contener referencias válidas a la tabla de colores y telas.

## Flujo de Datos - Frontend

### 1. Captura de Datos en el Modal
El usuario ingresa datos en el formulario "Agregar Prenda Nueva":
```javascript
// En gestion-items-pedido.js línea 1290
// El usuario selecciona telas en: gestion-telas.js
const color = document.getElementById('nueva-prenda-color').value;  // Ej: "ROJO"
const tela = document.getElementById('nueva-prenda-tela').value;    // Ej: "ALGODÓN"

// Estos se guardan en window.telasAgregadas como STRINGS:
window.telasAgregadas.push({ 
    color: "ROJO",      // ← STRING, no ID numérico
    tela: "ALGODÓN",    // ← STRING, no ID numérico
    referencia: "REF123",
    imagenes: [...]
});
```

### 2. Construcción del Objeto de Prenda
En `gestion-items-pedido.js` línea 1299, se construye el objeto `prendaNueva`:
```javascript
const prendaNueva = {
    nombre_producto: nombrePrenda,
    descripcion: descripcion,
    genero: genero,
    origen: origen,
    telasAgregadas: telasConUrls,  // ← Array con {color, tela, referencia, imagenes}
    // ... otros campos ...
    color_id: null,     // ← NUNCA SE ESTABLECE
    tela_id: null       // ← NUNCA SE ESTABLECE
};
```

### 3. Recolección de Datos al Enviar el Pedido
En `gestion-items-pedido.js` línea 1319, al recolectar datos para enviar:
```javascript
// Línea 1360-1363
color_id: prenda.color_id || null,  // ← prenda.color_id NO EXISTE
tela_id: prenda.tela_id || null,    // ← prenda.tela_id NO EXISTE
```

Como `prenda` viene de `gestorPrendaSinCotizacion.obtenerActivas()` y el objeto nunca tuvo `color_id`/`tela_id`, 
estos campos quedan como `null`.

### 4. JSON Enviado al Backend
```json
{
    "items": [
        {
            "prenda": "CAMISA",
            "color": "ROJO",          // ← NOMBRE del color (string)
            "color_id": null,         // ← ID no disponible en frontend
            "tela": "ALGODÓN",        // ← NOMBRE de la tela (string)
            "tela_id": null,          // ← ID no disponible en frontend
            "variaciones": { ... },
            "procesos": { ... }
        }
    ]
}
```

## ¿Por qué es CORRECTO que lleguen NULL?

### Razón 1: Separación de Responsabilidades
- **Frontend**: Captura NOMBRES de color y tela ingresados por el usuario
- **Backend**: Busca IDs en la base de datos basándose en esos nombres

### Razón 2: El Backend ya lo Maneja
En `CrearPedidoEditableController.php` líneas 404-426:
```php
// Si vienen IDs, usarlos directamente
if (!empty($item['color_id'])) {
    $prendaData['color_id'] = $item['color_id'];
}
// Si vienen NOMBRES, buscar o crear y obtener IDs
elseif (!empty($item['color'])) {
    try {
        $color = $this->colorGeneroService->buscarOCrearColor($item['color']);
        $prendaData['color_id'] = $color->id;  // ← AQUÍ se obtiene el ID
    } catch (\Exception $e) {
        \Log::warning('Error procesando color', ...);
    }
}

// Lo mismo para tela:
if (!empty($item['tela_id'])) {
    $prendaData['tela_id'] = $item['tela_id'];
}
elseif (!empty($item['tela'])) {
    try {
        $tela = $this->colorGeneroService->obtenerOCrearTela($item['tela']);
        $prendaData['tela_id'] = $tela->id;  // ← AQUÍ se obtiene el ID
    } catch (\Exception $e) {
        \Log::warning('Error procesando tela', ...);
    }
}
```

## Solución Implementada

### Cambio en Frontend (16/01/2026)
En `gestion-items-pedido.js` línea 1318-1323:
```javascript
// ✅ AHORA CON COMENTARIO EXPLICATIVO
color: prenda.color || null,           // ← Nombre de color (string)
tela: prenda.tela || null,             // ← Nombre de tela (string)
color_id: null,                        // ← Intencionalmente NULL
tela_id: null,                         // ← Intencionalmente NULL
tipo_manga_id: prenda.tipo_manga_id || null,
tipo_broche_boton_id: prenda.tipo_broche_boton_id || null,
```

## Cómo Verificar que Funciona

### 1. En el Frontend (Console)
Antes de enviar, los datos deberían mostrar:
```javascript
// En gestion-items-pedido.js línea 1340-1360
console.log('🔎 [COLOR/TELA] Item ${prendaIndex}:', {
    color: "ROJO",           // ← Nombre
    color_id: null,          // ← NULL es correcto
    tela: "ALGODÓN",         // ← Nombre  
    tela_id: null            // ← NULL es correcto
});
```

### 2. En el Backend (Laravel Log)
El backend debe registrar:
```
✅ Color creado/obtenido: nombre=ROJO, id=5
✅ Tela creada/obtenida: nombre=ALGODÓN, id=12
```

### 3. En la Base de Datos
Después de crear el pedido:
```sql
SELECT id, color_id, tela_id FROM prenda_pedido_variantes 
WHERE pedido_id = 90149;

-- Debe mostrar:
id | color_id | tela_id
1  | 5        | 12
```

## Flujo Completo (16/01/2026)

```
FRONTEND (Usuario ingresa datos)
    ↓
nombre = "ROJO", tela = "ALGODÓN" (STRINGS)
    ↓
JSON: {color: "ROJO", tela: "ALGODÓN", color_id: null, tela_id: null}
    ↓
BACKEND (CrearPedidoEditableController)
    ↓
SI color_id es NULL y color es "ROJO":
    → buscarOCrearColor("ROJO") → retorna objeto con id=5
    → Asigna color_id = 5
    ↓
SI tela_id es NULL y tela es "ALGODÓN":
    → obtenerOCrearTela("ALGODÓN") → retorna objeto con id=12
    → Asigna tela_id = 12
    ↓
SALVA EN BD: variante con color_id=5, tela_id=12
    ↓
ÉXITO ✅
```

## Nota Importante: Observaciones (Manga, Bolsillos, Broche)

El mismo patrón se aplica a las observaciones:
- **Frontend**: Envía `obs_manga`, `obs_bolsillos`, `obs_broche`, `obs_reflectivo` como strings
- **Backend**: Los guarda directamente en la tabla `prenda_pedido_variantes`

El backend ya está configurado correctamente en `PedidoPrendaService.php` línea 377-396.

## Referencias
- Frontend: [gestion-items-pedido.js](../public/js/modulos/crear-pedido/procesos/gestion-items-pedido.js#L1318-L1323)
- Backend: [CrearPedidoEditableController.php](../app/Http/Controllers/Asesores/CrearPedidoEditableController.php#L404-L426)
- Service: [PedidoPrendaService.php](../app/Application/Services/PedidoPrendaService.php#L166-L200)
