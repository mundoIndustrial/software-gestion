# ANÁLISIS COMPLETO DEL FLUJO DE GUARDADO DE PEDIDO DE PRODUCCIÓN

## 1. FLUJO GENERAL

```
Frontend (crear-pedido-editable.js)
    ↓
Recopila datos: prendas[], nombre, descripción, telas, variaciones, imágenes
    ↓
Envía JSON a: /asesores/pedidos-produccion/crear-desde-cotizacion/{id}
    ↓
Backend (PedidosProduccionController::crearDesdeCotizacion)
    ↓
Crea: PedidoProduccion + PrendaPedido + ProcesoPrenda + VariantePrenda
    ↓
Guarda imágenes en: prenda_fotos_tela_pedido (solo URLs)
```

## 2. DATOS QUE SE ENVÍAN DEL FRONTEND

### Objeto Prenda que se envía:
```javascript
{
    index: 0,
    nombre_producto: "CAMISA DRILL",              // String editado
    descripcion: "Camisa de trabajo...",          // String editado
    genero: ["DAMA"],                             // Array o String
    manga: "LARGA",                               // String editado (tipo_manga)
    broche: "BOTÓN",                              // String editado (tipo_broche)
    tiene_bolsillos: true,                        // Boolean editado
    tiene_reflectivo: false,                      // Boolean editado
    manga_obs: "Con pespunte",                    // Observaciones manga editadas
    bolsillos_obs: "Pecho y espalda",            // Observaciones bolsillos
    broche_obs: "Botones de calidad",            // Observaciones broche
    reflectivo_obs: "",                           // Observaciones reflectivo
    observaciones: null,                          // Observaciones generales
    telas_multiples: [                            // NUEVO: Telas/colores editadas
        {
            tela: "DRILL BORNEO",
            color: "AZUL MARINO",
            referencia: "REF-DB-001"
        }
    ],
    cantidades: {
        "S": 50,
        "M": 50,
        "L": 50
    },
    fotos: [...],                                 // Array de URLs de fotos prenda
    telas: [...],                                 // Array de URLs de fotos telas
    logos: [...]                                  // Array de URLs de logos
}
```

## 3. PROCESAMIENTO EN BACKEND

### 3.1 Creación de PedidoProduccion
**Tabla:** `pedidos_produccion`
```php
PedidoProduccion::create([
    'cotizacion_id' => $cotizacion->id,
    'numero_cotizacion' => $numeroCotizacion,
    'numero_pedido' => auto-generado,
    'cliente' => $cotizacion->cliente,
    'asesor_id' => auth()->id(),
    'forma_de_pago' => $formaPago,
    'estado' => 'No iniciado',
    'fecha_de_creacion_de_orden' => now(),
]);
```

### 3.2 Creación de PrendaPedido
**Tabla:** `prendas_pedido`
```php
PrendaPedido::create([
    'numero_pedido' => $pedido->numero_pedido,
    'nombre_prenda' => $prenda['nombre_producto'],
    'cantidad' => sum(cantidades),
    'descripcion' => construirDescripcionPrenda(),  // Incluye telas_multiples
    'cantidad_talla' => json_encode(cantidadesPorTalla),
    'color_id' => heredado de cotización,
    'tela_id' => heredado de cotización,
    'tipo_manga_id' => heredado de cotización,
    'tipo_broche_id' => heredado de cotización,
    'tiene_bolsillos' => booleano editado,
    'tiene_reflectivo' => booleano editado
]);
```

### 3.3 Construcción de Descripción
**Función:** `construirDescripcionPrenda($numeroPrenda, $producto, $cantidadesPorTalla)`

**Incluye:**
1. Prenda número y nombre
2. Descripción
3. **Telas/Colores múltiples** (NUEVO - de telas_multiples)
   - Formato: "Tela/Color: DRILL BORNEO REF:REF-DB-001 - AZUL MARINO"
4. Género
5. Manga + observaciones
6. Bolsillos + observaciones
7. Broche + observaciones
8. Reflectivo + observaciones
9. Tallas con cantidades

**Resultado almacenado en:** `prendas_pedido.descripcion`

### 3.4 Heredar Variantes de Cotización
**Función:** `heredarVariantesDePrenda($cotizacion, $prendaPedido, $index)`

Actualiza PrendaPedido con:
- `color_id` → de VariantePrenda
- `tela_id` → de VariantePrenda
- `tipo_manga_id` → de VariantePrenda
- `tipo_broche_id` → de VariantePrenda

## 4. MANEJO DE IMÁGENES

### 4.1 Imágenes de Prenda
**Capturadas en:** `prenda.fotos[]`
**Tipo:** URLs
**Guardadas en:** NO SE GUARDAN EN BD ACTUALMENTE
**Status:** ⚠️ NO IMPLEMENTADO

### 4.2 Imágenes de Telas
**Capturadas en:** `prenda.telas[]`
**Tipo:** URLs
**Almacenadas en:** `prenda_fotos_tela_pedido`
```
Tabla: prenda_fotos_tela_pedido
Campos:
- id
- prenda_pedido_id (FK a prendas_pedido)
- ruta_original
- ruta_webp
- created_at, updated_at, deleted_at
```

**Inserción en Backend:**
```php
// FALTA IMPLEMENTAR - No se están guardando en el controlador actual
foreach ($prenda['telas'] as $fotoTela) {
    PrendaFotoTelaPedido::create([
        'prenda_pedido_id' => $prendaPedido->id,
        'ruta_original' => $fotoTela,
        'ruta_webp' => null  // Si viene
    ]);
}
```

### 4.3 Imágenes de Logo
**Capturadas en:** `prenda.logos[]`
**Tipo:** URLs
**Guardadas en:** NO SE GUARDAN EN BD ACTUALMENTE
**Status:** ⚠️ NO IMPLEMENTADO

## 5. PROBLEMAS IDENTIFICADOS

### 🔴 CRÍTICO - Imágenes No Se Guardan
1. **Fotos de Prenda:** Se capturan pero NO se guardan en BD
2. **Fotos de Telas:** Se capturan pero NO se guardan en `prenda_fotos_tela_pedido`
3. **Fotos de Logo:** Se capturan pero NO se guardan en BD

**Tablas que podrían almacenarlas:**
- `prenda_fotos_tela_pedido` → Para telas (existe modelo y tabla)
- `prenda_fotos_pedido` → Para fotos de prenda (¿existe?)
- `logo_pedido` → Para logos (¿existe?)

### ⚠️ IMPORTANTE - IDs de Relaciones
1. Los `color_id`, `tela_id`, `tipo_manga_id`, `tipo_broche_id` se heredan de la cotización
2. Si se editan en el formulario, esos cambios NO se reflejan en los IDs (solo en texto)
3. La descripción SÍ incluye los valores editados

## 6. FLUJO DE DATOS COMPLETO (Paso a Paso)

### Frontend:
```
1. Usuario carga cotización
2. Sistema renderiza prendas con datos editables
3. Usuario edita: nombre, descripción, manga, tela/color, observaciones
4. Usuario elimina imágenes (se ocultan en DOM)
5. Usuario ingresa cantidades por talla
6. Usuario envía formulario
7. JavaScript recopila datos visibles:
   - Valores editados (texto)
   - Telas/colores editadas
   - Imágenes visibles (URLs)
   - Cantidades por talla
8. Envía POST JSON
```

### Backend (crearDesdeCotizacion):
```
1. Recibe JSON
2. Valida cotización
3. Crea PedidoProduccion
4. Para cada prenda:
   a. Construye descripción (incluye telas_multiples)
   b. Crea PrendaPedido
   c. Crea ProcesoPrenda
   d. Hereda variantes (color_id, tela_id, etc.)
5. Retorna JSON success
```

### Base de datos final:
```
pedidos_produccion:
├─ numero_pedido, cliente, asesor_id, forma_de_pago, estado

prendas_pedido:
├─ numero_pedido (FK)
├─ nombre_prenda
├─ cantidad
├─ descripcion (incluye telas_multiples + variaciones)
├─ cantidad_talla (JSON)
├─ color_id (heredado)
├─ tela_id (heredado)
├─ tipo_manga_id (heredado)
├─ tipo_broche_id (heredado)
├─ tiene_bolsillos
├─ tiene_reflectivo

prenda_fotos_tela_pedido:
├─ prenda_pedido_id (FK)
├─ ruta_original (URL)
├─ ruta_webp (URL o NULL)
```

## 7. VERIFICACIÓN NECESARIA

✅ **Datos que SÍ se guardan:**
- Nombre prenda editado
- Descripción prenda editada
- Variaciones editadas (manga, broche, bolsillos, reflectivo + obs)
- Telas/colores múltiples editadas (en descripción)
- Cantidad por talla (JSON)
- IDs heredados de relaciones

⚠️ **Datos que PARCIALMENTE se guardan:**
- Fotos de telas (capturadas pero NO insertadas en BD)

❌ **Datos que NO se guardan:**
- Fotos de prenda
- Fotos de logo

## 8. TABLA RESUMEN DE MAPEO

| Campo del Formulario | Guardado en Tabla | Columna | Formato |
|---|---|---|---|
| Nombre prenda | prendas_pedido | nombre_prenda | String |
| Descripción | prendas_pedido | descripcion | Text |
| Manga editada | prendas_pedido | descripcion | Incluida |
| Manga ID | prendas_pedido | tipo_manga_id | FK heredada |
| Observaciones manga | prendas_pedido | descripcion | Incluida |
| Tela editada | prendas_pedido | descripcion | Incluida |
| Tela ID | prendas_pedido | tela_id | FK heredada |
| Color editado | prendas_pedido | descripcion | Incluida |
| Color ID | prendas_pedido | color_id | FK heredada |
| Broche + obs | prendas_pedido | descripcion | Incluida |
| Bolsillos + obs | prendas_pedido | descripcion | Incluida |
| Reflectivo + obs | prendas_pedido | descripcion | Incluida |
| Cantidades/talla | prendas_pedido | cantidad_talla | JSON |
| Fotos telas URL | prenda_fotos_tela_pedido | ruta_original | ⚠️ NO SE GUARDA |
| Fotos prenda URL | ??? | ??? | ❌ NO SE GUARDA |
| Fotos logo URL | ??? | ??? | ❌ NO SE GUARDA |

