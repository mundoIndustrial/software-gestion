# Validación de Estructura BD y Relaciones Eloquent

## Estado Actual: ✅ VERIFICADO

Todas las relaciones Eloquent están correctamente definidas en los modelos para trabajar con la estructura real de la BD.

## Mapeo de Tablas → Modelos → Relaciones

### Tabla: `pedidos_produccion` 
**Modelo:** `PedidoProduccion` (/app/Models/PedidoProduccion.php)

Relaciones definidas:
- ✅ `prendas()` → HasMany → PrendaPedido (FK: pedido_produccion_id)
- ✅ `epps()` → HasMany → PedidoEpp (FK: pedido_produccion_id)

### Tabla: `prendas_pedido`
**Modelo:** `PrendaPedido` (/app/Models/PrendaPedido.php)

Relaciones definidas:
- ✅ `pedidoProduccion()` → BelongsTo → PedidoProduccion (FK: pedido_produccion_id)
- ✅ `tallas()` → HasMany → PrendaPedidoTalla (FK: prenda_pedido_id)
- ✅ `variantes()` → HasMany → PrendaVariantePed (FK: prenda_pedido_id)
- ✅ `coloresTelas()` → HasMany → PrendaPedidoColorTela (FK: prenda_pedido_id)
- ✅ `fotos()` → HasMany → PrendaFotoPedido (FK: prenda_pedido_id)
- ✅ `fotosTelas()` → HasManyThrough → PrendaFotoTelaPedido (via coloresTelas)
- ✅ `procesos()` → HasMany → PedidosProcesosPrendaDetalle (FK: prenda_pedido_id)

### Tabla: `prenda_pedido_tallas`
**Modelo:** `PrendaPedidoTalla` (/app/Models/PrendaPedidoTalla.php)

Relaciones definidas:
- ✅ `prenda()` → BelongsTo → PrendaPedido

**Campos en BD:**
- `genero` enum('DAMA','CABALLERO','UNISEX')
- `talla` varchar(50)
- `cantidad` int UN

### Tabla: `prenda_pedido_variantes`
**Modelo:** `PrendaVariantePed` (/app/Models/PrendaVariantePed.php)

Relaciones definidas:
- ✅ `prenda()` → BelongsTo → PrendaPedido
- ✅ `tipoManga()` → BelongsTo → TipoManga (FK: tipo_manga_id)
- ✅ `tipoBroche()` → BelongsTo → TipoBrocheBoton (FK: tipo_broche_boton_id)

**Campos en BD:**
- `tipo_manga_id` bigint UN
- `tipo_broche_boton_id` bigint UN
- `manga_obs` longtext
- `broche_boton_obs` longtext
- `tiene_bolsillos` tinyint(1)
- `bolsillos_obs` longtext

### Tabla: `prenda_pedido_colores_telas`
**Modelo:** `PrendaPedidoColorTela` (/app/Models/PrendaPedidoColorTela.php)

Relaciones definidas:
- ✅ `prendaPedido()` → BelongsTo → PrendaPedido
- ✅ `color()` → BelongsTo → ColorPrenda (FK: color_id)
- ✅ `tela()` → BelongsTo → TelaPrenda (FK: tela_id)
- ✅ `fotos()` → HasMany → PrendaFotoTelaPedido (FK: prenda_pedido_colores_telas_id)

### Tabla: `prenda_fotos_pedido`
**Modelo:** `PrendaFotoPedido` (/app/Models/PrendaFotoPedido.php)

**Campos en BD:**
- `ruta_original` varchar(255)
- `ruta_webp` varchar(255)
- `orden` int

### Tabla: `prenda_fotos_tela_pedido`
**Modelo:** `PrendaFotoTelaPedido` (/app/Models/PrendaFotoTelaPedido.php)

**Campos en BD:**
- `prenda_pedido_colores_telas_id` bigint UN (FK)
- `ruta_original` varchar(500)
- `ruta_webp` varchar(500)
- `orden` int

### Tabla: `pedido_epp`
**Modelo:** `PedidoEpp` (/app/Models/PedidoEpp.php)

Relaciones definidas:
- ✅ `pedidoProduccion()` → BelongsTo → PedidoProduccion
- ✅ `epp()` → BelongsTo → Epp
- ✅ `imagenes()` → HasMany → PedidoEppImagen

**Campos en BD:**
- `cantidad` int
- `observaciones` longtext

### Tabla: `pedido_epp_imagenes`
**Modelo:** `PedidoEppImagen` (/app/Models/PedidoEppImagen.php)

**Campos en BD:**
- `ruta_original` varchar(500)
- `ruta_web` varchar(500)
- `principal` tinyint(1)
- `orden` int UN

## Validación de `ObtenerPedidoUseCase`

Ubicación: `/app/Application/Pedidos/UseCases/ObtenerPedidoUseCase.php`

**Métodos implementados:**

1. ✅ `obtenerPrendasCompletas(int $pedidoId)` - Extrae todas las prendas del pedido
2. ✅ `construirEstructuraTallas($prenda)` - Estructura tallas como { GENERO: { TALLA: CANTIDAD } }
3. ✅ `obtenerVariantes($prenda)` - Obtiene manga, broche y bolsillos desde tabla variantes
4. ✅ `obtenerColorYTela($prenda)` - Obtiene color y tela desde coloresTelas
5. ✅ `obtenerImagenesTela($prenda)` - Obtiene imágenes de tela desde fotosTela
6. ✅ `obtenerEpps(int $pedidoId)` - Obtiene EPPs del pedido

## Estructura de Datos Esperada en API

```json
{
  "data": {
    "id": 1,
    "numero": "PED-2700",
    "numero_pedido": 2700,
    "prendas": [
      {
        "id": 100,
        "prenda_pedido_id": 100,
        "nombre_prenda": "CAMISA DRILL",
        "tela": "DRILL BORNEO",
        "color": "NARANJA",
        "ref": "REF-DB-001",
        "descripcion": "Descripción de la prenda",
        "de_bodega": false,
        "tallas": {
          "DAMA": {
            "S": 20,
            "M": 20,
            "L": 20
          },
          "CABALLERO": {
            "M": 30,
            "L": 30
          }
        },
        "variantes": [
          {
            "manga": "LARGA",
            "manga_obs": "Con presilla",
            "broche": "BOTONES",
            "broche_obs": null,
            "bolsillos": true,
            "bolsillos_obs": "Pecho y espalda"
          }
        ],
        "imagenes": [
          "storage/prendas/2700/camisa-1.webp"
        ],
        "imagenes_tela": [
          "storage/telas/drill-borneo-naranja.webp"
        ],
        "manga": "LARGA",
        "obs_manga": "Con presilla",
        "broche": "BOTONES",
        "obs_broche": null,
        "tiene_bolsillos": true,
        "obs_bolsillos": "Pecho y espalda",
        "tiene_reflectivo": false
      }
    ],
    "epps": [
      {
        "id": 5,
        "pedido_epp_id": 5,
        "epp_id": 1,
        "epp_nombre": "CHALECO DE SEGURIDAD",
        "cantidad": 30,
        "observaciones": null,
        "imagenes": [
          "storage/epps/chaleco-1.webp"
        ]
      }
    ]
  }
}
```

## Testing Recomendado

### 1. Verificar relaciones en Tinker

```bash
# Navegar al directorio
cd c:\Users\Usuario\Documents\trabahiiiii\v10\v10\mundoindustrial

# Activar el entorno
python -m venv .venv  # Si no existe
.\.venv\Scripts\activate

# Abrir Laravel Tinker
php artisan tinker

# Verificar que la relación prendas existe y se carga
>>> $pedido = \App\Models\PedidoProduccion::find(2700);
>>> $pedido->prendas;  // Debe retornar colección de PrendaPedido
>>> $pedido->prendas->first()->tallas;  // Debe retornar colección de PrendaPedidoTalla
>>> $pedido->prendas->first()->variantes;  // Debe retornar colección de PrendaVariantePed
>>> $pedido->prendas->first()->coloresTelas;  // Debe retornar colección de PrendaPedidoColorTela
>>> $pedido->epps;  // Debe retornar colección de PedidoEpp
```

### 2. Verificar que ObtenerPedidoUseCase funciona

```php
// En un test o en rutas de prueba
$useCase = app(\App\Application\Pedidos\UseCases\ObtenerPedidoUseCase::class);
$resultado = $useCase->ejecutar(2700);

// Debe tener:
// - $resultado->prendas (array no vacío)
// - cada prenda con estructura correcta
// - $resultado->epps (array)
```

### 3. Verificar el endpoint API

```bash
# Hacer llamada HTTP
GET /api/pedidos/2700

# Debe retornar JSON con estructura completa
# Sin errores de "undefined" o "map"
```

## Log Recomendado

Monitorear `/storage/logs/laravel.log` para:

1. ❌ `Error obteniendo prendas completas` - Problema en acceso a relaciones
2. ❌ `Error construyendo estructura de tallas` - Problema con tabla prenda_pedido_tallas
3. ❌ `Error obteniendo variantes` - Problema con tabla prenda_pedido_variantes
4. ❌ `Error obteniendo color y tela` - Problema con coloresTelas
5. ❌ `Error obteniendo imágenes de tela` - Problema con fotosTela
6. ❌ `Error obteniendo EPPs` - Problema con tabla pedido_epp

## Próximos Pasos

1. ✅ Verificar estructura de BD (ya hecho)
2. ✅ Definir relaciones en modelos (ya hecho)
3. ⏳ Ejecutar test de relaciones en Tinker
4. ⏳ Verificar endpoint API retorna datos completos
5. ⏳ Validar que frontend procesa datos sin errores
6. ⏳ Monitorear logs en producción

## Notas Importantes

- **Sin tablas manuales**: No tocar las tablas, están correctamente estructuradas
- **Foreign keys**: Todas usan `_id` suffix y apuntan a tabla correcta
- **Enums**: `genero` en prenda_pedido_tallas es enum('DAMA','CABALLERO','UNISEX')
- **Opcional**: Algunos campos como `manga_obs`, `broche_boton_obs` son longtext pero pueden ser NULL
- **Flujo**: API → ObtenerPedidoUseCase → JavaScript (frontend) → generarHTMLFactura()
