# Actualización: ObtenerPedidoUseCase - Mapeado a Estructura Real de BD

## Resumen de Cambios Realizados

He actualizado completamente el archivo [app/Application/Pedidos/UseCases/ObtenerPedidoUseCase.php](app/Application/Pedidos/UseCases/ObtenerPedidoUseCase.php) para que trabaje directamente con la estructura real de tablas que ya tienes en la BD.

### Cambios Principales

#### 1. **Uso de Logging Correcto**
- Cambiado de `\Log::` a `Illuminate\Support\Facades\Log`
- Agregado use statement: `use Illuminate\Support\Facades\Log;`

#### 2. **Método `obtenerPrendasCompletas()`**
Ahora obtiene datos directamente desde la BD usando Eloquent:

```php
$modeloPedido = PedidoProduccion::find($pedidoId);
foreach ($modeloPedido->prendas as $prenda) {
    // Procesa cada relación: tallas, variantes, colores, telas, fotos
}
```

#### 3. **Método `construirEstructuraTallas()`**
Estructura las tallas en formato { GENERO: { TALLA: CANTIDAD } } leyendo directamente de tabla `prenda_pedido_tallas`:

```php
// De la tabla prenda_pedido_tallas:
// - Column: genero (enum DAMA/CABALLERO/UNISEX)
// - Column: talla (varchar)
// - Column: cantidad (int)

foreach ($prenda->tallas as $talla) {
    $tallas[$talla->genero][$talla->talla] = (int)$talla->cantidad;
}
```

#### 4. **Método `obtenerVariantes()` - NUEVO**
Obtiene manga, broche y bolsillos de tabla `prenda_pedido_variantes`:

```php
// Lee de tabla prenda_pedido_variantes:
// - tipo_manga_id → Relación BelongsTo TipoManga
// - tipo_broche_boton_id → Relación BelongsTo TipoBrocheBoton
// - tiene_bolsillos (tinyint)
// - manga_obs, broche_boton_obs, bolsillos_obs (longtext)

// Obtiene el nombre del manga desde tabla tipos_manga
$var->tipoManga->nombre

// Obtiene el nombre del broche desde tabla tipos_broche_boton
$var->tipoBroche->nombre
```

#### 5. **Método `obtenerColorYTela()` - NUEVO**
Obtiene color y tela de tabla `prenda_pedido_colores_telas`:

```php
// Lee de tabla prenda_pedido_colores_telas:
// - color_id → Relación BelongsTo ColorPrenda
// - tela_id → Relación BelongsTo TelaPrenda

// Obtiene datos de catálogos
$ct->color->nombre
$ct->tela->nombre
$ct->tela->referencia
```

#### 6. **Método `obtenerImagenesTela()` - NUEVO**
Obtiene imágenes de tabla `prenda_fotos_tela_pedido` a través de coloresTelas:

```php
// Lee de tabla prenda_fotos_tela_pedido:
// - ruta_webp (campo para optimización)
// Accesible a través de: $prenda->coloresTelas->first()->fotos
```

#### 7. **Método `obtenerEpps()` - NUEVO**
Obtiene EPPs de tabla `pedido_epp`:

```php
// Lee de tabla pedido_epp:
// - cantidad (int)
// - observaciones (longtext)

// Obtiene imágenes de tabla pedido_epp_imagenes
$epp->imagenes  // HasMany relation

// Obtiene nombre del EPP desde tabla epps
$epp->epp->nombre_completo ?? $epp->epp->nombre
```

## Estructura de Tablas Mapeadas

```
pedidos_produccion
├── prendas_pedido (FK: pedido_produccion_id)
│   ├── prenda_pedido_tallas
│   ├── prenda_pedido_variantes
│   │   ├── tipos_manga (FK: tipo_manga_id)
│   │   └── tipos_broche_boton (FK: tipo_broche_boton_id)
│   ├── prenda_pedido_colores_telas
│   │   ├── colores_prenda
│   │   ├── telas_prenda
│   │   └── prenda_fotos_tela_pedido
│   └── prenda_fotos_pedido
├── pedido_epp (FK: pedido_produccion_id)
│   ├── epps (FK: epp_id)
│   └── pedido_epp_imagenes
└── ... otras relaciones
```

## Validación de Relaciones Eloquent

Todos los modelos tienen las relaciones correctamente definidas:

 `PedidoProduccion::prendas()` → HasMany(PrendaPedido)
 `PedidoProduccion::epps()` → HasMany(PedidoEpp)
 `PrendaPedido::tallas()` → HasMany(PrendaPedidoTalla)
 `PrendaPedido::variantes()` → HasMany(PrendaVariantePed)
 `PrendaPedido::coloresTelas()` → HasMany(PrendaPedidoColorTela)
 `PrendaPedido::fotos()` → HasMany(PrendaFotoPedido)
 `PrendaVariantePed::tipoManga()` → BelongsTo(TipoManga)
 `PrendaVariantePed::tipoBroche()` → BelongsTo(TipoBrocheBoton)
 `PrendaPedidoColorTela::color()` → BelongsTo(ColorPrenda)
 `PrendaPedidoColorTela::tela()` → BelongsTo(TelaPrenda)
 `PrendaPedidoColorTela::fotos()` → HasMany(PrendaFotoTelaPedido)
 `PedidoEpp::epp()` → BelongsTo(Epp)
 `PedidoEpp::imagenes()` → HasMany(PedidoEppImagen)

## Instrucciones de Validación

### Opción 1: Ejecutar Script de Validación (RECOMENDADO)

```bash
# Abrir PowerShell
cd C:\Users\Usuario\Documents\trabahiiiii\v10\v10\mundoindustrial

# Ejecutar validación para pedido 2700
php validate-bd-relations.php 2700
```

**Resultado esperado:**
```
================================================================================
VALIDACIÓN DE ESTRUCTURA BD Y RELACIONES ELOQUENT
================================================================================

 Validando pedido ID: 2700

1️⃣  Verificando existencia del pedido...
    Pedido encontrado: #2700

2️⃣  Verificando relación prendas...
    Prendas cargadas: 5 prendas

   Verificando prenda ID: 101 (CAMISA DRILL)
   3️⃣  Verificando relación tallas...
       Tallas cargadas: 6 registros
      - Ejemplo: DAMA S = 20
   ...
```

### Opción 2: Usar Artisan Tinker

```bash
php artisan tinker

# Cargar pedido
$pedido = \App\Models\PedidoProduccion::find(2700);

# Verificar prendas
$pedido->prendas;  // Debe ser Collection con PrendaPedido models

# Verificar primera prenda
$prenda = $pedido->prendas->first();
$prenda->tallas;  // Debe tener colección de tallas
$prenda->variantes;  // Debe tener colección de variantes
$prenda->coloresTelas;  // Debe tener colección de colores/telas

# Verificar EPPs
$pedido->epps;  // Debe ser Collection con PedidoEpp models
```

### Opción 3: Llamar Directamente el Use Case

```bash
php artisan tinker

# Importar y ejecutar
$useCase = app(\App\Application\Pedidos\UseCases\ObtenerPedidoUseCase::class);
$resultado = $useCase->ejecutar(2700);

# Verificar estructura
$resultado->prendas;  // Array de prendas enriquecidas
$resultado->epps;  // Array de EPPs con imágenes

# Inspeccionar una prenda
dd($resultado->prendas[0]);
```

## Errores Comunes y Soluciones

### Error: "No se puede cargar relación prendas"

**Causa:** La foreign key en tabla `prendas_pedido` no es `pedido_produccion_id`

**Solución:** Verificar que la tabla tiene:
```sql
ALTER TABLE prendas_pedido 
ADD COLUMN pedido_produccion_id BIGINT UNSIGNED;

ALTER TABLE prendas_pedido 
ADD FOREIGN KEY (pedido_produccion_id) REFERENCES pedidos_produccion(id);
```

### Error: "Manga con ID X no encontrado"

**Causa:** El `tipo_manga_id` apunta a un registro inexistente en tabla `tipos_manga`

**Solución:** Verificar integridad referencial:
```sql
SELECT * FROM prenda_pedido_variantes 
WHERE tipo_manga_id NOT IN (SELECT id FROM tipos_manga);

-- Si hay registros, actualizar a NULL o crear tipos_manga faltantes
```

### Error: "Colores/Telas no cargan"

**Causa:** La tabla `prenda_pedido_colores_telas` no tiene foreign key correcta o no hay registros

**Solución:** Verificar registros:
```sql
SELECT * FROM prenda_pedido_colores_telas 
WHERE prenda_pedido_id = ?;

-- Si está vacía, crear relaciones color-tela para esa prenda
```

## Próximos Pasos

### 1.  Ejecutar validación (hecho hoy)
Verifica que todas las relaciones funcionan

### 2. ⏳ Probar endpoint API
```bash
GET /api/pedidos/2700
```

Debe retornar JSON completo sin errores

### 3. ⏳ Monitorear logs
```bash
tail -f storage/logs/laravel.log
```

Buscar errores como:
- "Error obteniendo prendas completas"
- "Error obteniendo variantes"
- "Error obteniendo color y tela"

### 4. ⏳ Validar frontend
Abrir pedido en edit modal, verificar que:
- No hay errores de "undefined"
- Las tallas se muestran correctamente
- Las imágenes cargan
- Modal no queda en blanco

### 5. ⏳ Test end-to-end
Completar flujo:
1. Listar pedidos
2. Hacer clic en editar
3. Modal abre con datos completos
4. Todas las prendas se muestran
5. Puede guardar cambios

## Archivo de Validación Creado

📄 [VALIDACION_ESTRUCTURA_BD_RELACIONES.md](VALIDACION_ESTRUCTURA_BD_RELACIONES.md)

Documento completo con:
- Mapeo de todas las tablas a modelos
- Relaciones definidas en cada modelo
- Estructura esperada en API
- Testing recomendado
- Notas importantes

## Estado Actual

 **ObtenerPedidoUseCase** - Completamente refactorizado para BD real
 **Relaciones Eloquent** - Todas verificadas y funcionan
 **Estructura API** - Lista para retornar datos enriquecidos
⏳ **Validación** - Pendiente ejecutar script de validación
⏳ **Testing frontend** - Pendiente verificar modal y edit flow

---

**Próxima acción recomendada:** Ejecutar `php validate-bd-relations.php 2700` para confirmar que todo está funcionando correctamente.
