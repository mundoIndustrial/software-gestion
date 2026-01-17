# Guía: Guardar EPP en Pedidos

## Funcionalidad
Cuando se crea un pedido, es necesario guardar los EPP (Equipos de Protección Personal) que se agregaron al pedido. Estos se guardan en la tabla `pedido_epp` con información detallada.

## Estructura de Datos

### Tabla `pedido_epp`
```sql
id                      INT (PK)
pedido_produccion_id    INT (FK) → pedidos_produccion
epp_id                  INT (FK) → epps
cantidad                INT (cantidad de EPP)
tallas_medidas          JSON (datos específicos)
observaciones           TEXT
created_at, updated_at, deleted_at
```

### Tabla `pedido_epp_imagenes`
```sql
id                  INT (PK)
pedido_epp_id       INT (FK) → pedido_epp
archivo             VARCHAR (ruta de imagen)
principal           BOOLEAN (es imagen principal)
orden               INT (orden de presentación)
created_at, updated_at
```

## Endpoints API

### 1. Obtener EPP de un Pedido
```
GET /api/pedidos/{pedido}/epps
```

**Respuesta:**
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "epp_id": 5,
            "epp_nombre": "Casco de Seguridad",
            "epp_codigo": "CASCO-001",
            "epp_categoria": "Cabeza",
            "cantidad": 10,
            "tallas_medidas": {
                "talla": "L",
                "medida": "58cm"
            },
            "observaciones": "Con logo de empresa",
            "imagenes": [
                {
                    "id": 1,
                    "archivo": "/storage/pedidos/1/epp/casco-frente.jpg",
                    "principal": true,
                    "orden": 0
                }
            ]
        }
    ],
    "count": 1
}
```

### 2. Guardar EPP en un Pedido
```
POST /api/pedidos/{pedido}/epps
```

**Body:**
```json
{
    "epps": [
        {
            "epp_id": 5,
            "cantidad": 10,
            "tallas_medidas": {
                "talla": "L",
                "medida": "58cm",
                "color": "Blanco"
            },
            "observaciones": "Con logo de empresa",
            "imagenes": [
                {
                    "archivo": "/storage/pedidos/1/epp/casco-frente.jpg",
                    "principal": true,
                    "orden": 0
                }
            ]
        },
        {
            "epp_id": 3,
            "cantidad": 50,
            "tallas_medidas": {
                "tamaño": "M",
                "tipo": "Nitrilo"
            },
            "observaciones": "Caja de 100 unidades"
        }
    ]
}
```

**Respuesta:**
```json
{
    "success": true,
    "message": "2 EPP agregados al pedido",
    "data": [...]
}
```

### 3. Actualizar un EPP del Pedido
```
PATCH /api/pedidos/{pedido}/epps/{pedidoEpp}
```

**Body:**
```json
{
    "cantidad": 15,
    "tallas_medidas": {
        "talla": "XL"
    },
    "observaciones": "Aumentar cantidad"
}
```

### 4. Eliminar un EPP del Pedido
```
DELETE /api/pedidos/{pedido}/epps/{pedidoEpp}
```

### 5. Exportar EPP como JSON
```
GET /api/pedidos/{pedido}/epps/exportar/json
```

**Respuesta:**
```json
{
    "success": true,
    "json": "[{\"id\":1,\"epp_id\":5,...}]"
}
```

## Uso en Frontend

### Ejemplo: Guardar EPP cuando se crea un Pedido

```javascript
// Cuando se crea el pedido
async function crearPedidoConEpp(pedidoData) {
    // 1. Crear pedido (usar endpoint existente)
    const pedidoResponse = await fetch('/api/pedidos', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(pedidoData)
    });
    const pedido = await pedidoResponse.json();

    // 2. Agregar EPP al pedido
    const eppsDelFormulario = obtenerEppsDeLaModalForm(); // EPP que el usuario agregó
    
    const response = await fetch(`/api/pedidos/${pedido.id}/epps`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            epps: eppsDelFormulario
        })
    });

    if (response.ok) {
        const data = await response.json();
        console.log('EPP guardados:', data);
    }
}

// Función para obtener los EPP del formulario modal
function obtenerEppsDeLaModalForm() {
    // Esta función debe extraer los EPP del estado del formulario
    // que se fue llenando con el modal de agregar EPP
    return [
        {
            epp_id: 5,
            cantidad: 10,
            tallas_medidas: {...},
            observaciones: "...",
            imagenes: [...]
        }
    ];
}
```

### Ejemplo: Consultar EPP de un Pedido Existente

```javascript
async function obtenerEppsDePedido(pedidoId) {
    const response = await fetch(`/api/pedidos/${pedidoId}/epps`);
    const data = await response.json();
    
    if (data.success) {
        console.log('EPP del pedido:', data.data);
        mostrarEppEnTabla(data.data);
    }
}
```

## Flujo Completo

1. **Usuario crea un pedido** desde la cotización
2. **Se abre modal de agregar EPP** (ya existe)
3. **Usuario agrega EPP y completa datos**:
   - Cantidad
   - Tallas/Medidas
   - Observaciones
   - Imágenes
4. **Al finalizar el pedido, se guarda con:**
   ```javascript
   POST /api/pedidos/{id}/epps
   ```
5. **Se guardan en tabla `pedido_epp`** y sus imágenes en `pedido_epp_imagenes`

## Integración con PedidoService

Para integrar esto en el flujo existente de `PedidoService`, la clase debe:

1. Crear el pedido
2. Crear las prendas
3. **[NUEVO] Guardar los EPP del pedido**

```php
$eppService = new PedidoEppService();
$eppService->guardarEppsDelPedido($pedido, $eppsArray);
```

## Relaciones en Modelos

### PedidoProduccion
```php
public function epps()
{
    return $this->hasMany(PedidoEpp::class);
}

public function pedidosEpp()
{
    return $this->hasMany(PedidoEpp::class, 'pedido_produccion_id');
}
```

### PedidoEpp
```php
public function pedidoProduccion()
{
    return $this->belongsTo(PedidoProduccion::class);
}

public function epp()
{
    return $this->belongsTo(Epp::class);
}

public function imagenes()
{
    return $this->hasMany(PedidoEppImagen::class);
}
```

### PedidoEppImagen
```php
public function pedidoEpp()
{
    return $this->belongsTo(PedidoEpp::class);
}
```

## Validaciones

- `epp_id` debe existir en tabla `epps`
- `cantidad` debe ser >= 1
- `tallas_medidas` es opcional pero debe ser JSON válido
- `observaciones` es opcional
- Las imágenes se deben guardar correctamente en storage

## Próximos Pasos

1. Integrar llamada a `/api/pedidos/{id}/epps` en el flujo de creación de pedido
2. Actualizar el modal de agregar EPP para guardar referencia al pedido
3. Mostrar EPP del pedido en página de detalle del pedido
4. Permitir editar/eliminar EPP del pedido después de creado
