# Template de Descripción de Prendas - Documentación

## Resumen

Se ha implementado un nuevo sistema de template para generar descripciones de prendas de forma estructurada y modular usando el Helper `DescripcionPrendaHelper`.

## Archivos Creados/Modificados

### 1. `app/Helpers/DescripcionPrendaHelper.php` (NUEVO)

Helper que contiene la lógica de generación de descripciones con dos métodos principales:

#### `generarDescripcion(array $prenda): string`

Genera la descripción formateada según el template especificado.

**Estructura de entrada:**
```php
$prenda = [
    'numero' => 1,                                    // Número de prenda
    'tipo' => 'Camisa Drill',                        // Nombre de la prenda
    'color' => 'Naranja',                            // Color
    'tela' => 'Drill Borneo',                        // Tipo de tela
    'ref' => 'REF-DB-001',                          // Referencia de tela
    'manga' => 'Larga',                             // Tipo de manga
    'logo' => 'Logo bordado en espalda',            // Descripción del logo
    'bolsillos' => ['Pecho', 'Espalda'],            // Array de bolsillos
    'reflectivos' => ['Mangas', 'Puños'],           // Array de reflectivos
    'otros' => ['Refuerzo en cuello'],              // Array de otros detalles
    'tallas' => ['S' => 50, 'M' => 50, 'L' => 50], // Array talla => cantidad
];
```

**Salida (formato):**
```
1: CAMISA DRILL
Color: Naranja | Tela: Drill Borneo REF-DB-001 | Manga: Larga

DESCRIPCIÓN:
- Logo: Logo bordado en espalda

Bolsillos:
• Pecho
• Espalda

Reflectivo:
• Mangas
• Puños

Otros detalles:
• Refuerzo en cuello

TALLAS:
- S: 50
- M: 50
- L: 50
```

#### `extraerDatosPrenda($prenda, int $index): array`

Extrae datos estructurados de un modelo `PrendaPedido` para alimentar el template.

**Funcionalidades:**
- Carga relaciones (color, tela, manga)
- Parsea `descripcion_variaciones` para extraer componentes
- Estructura datos en formato requerido por `generarDescripcion()`

### 2. `app/Models/PrendaPedido.php` (MODIFICADO)

**Cambios:**
- Agregado `use App\Helpers\DescripcionPrendaHelper;`
- Simplificado método `generarDescripcionDetallada($index = 1)` para usar el helper

**Antes (130+ líneas de lógica compleja):**
```php
public function generarDescripcionDetallada($index = 1)
{
    $lineas = [];
    // ... 100+ líneas de lógica manual
}
```

**Después (3 líneas de código limpio):**
```php
public function generarDescripcionDetallada($index = 1)
{
    $datos = DescripcionPrendaHelper::extraerDatosPrenda($this, $index);
    return DescripcionPrendaHelper::generarDescripcion($datos);
}
```

### 3. `app/Models/PedidoProduccion.php` (DOCUMENTACIÓN ACTUALIZADA)

Actualizada la documentación de `getDescripcionPrendasAttribute()` para reflejar el nuevo formato.

## Ventajas del Nuevo Sistema

### 1. **Modularidad**
- Lógica separada en Helper dedicado
- Fácil de probar y mantener
- Reutilizable en otros contextos

### 2. **Flexibilidad**
- Datos estructurados como array
- Fácil generar descripciones desde cualquier origen
- Soporta escalado a múltiples formatos

### 3. **Mantenibilidad**
- Código más limpio y legible
- Responsabilidades claras
- Menos líneas de código duplicado

### 4. **Rendimiento**
- Parseo de datos más eficiente
- Cacheable si es necesario
- Reducción de lógica compleja

## Uso

### Uso Básico (desde modelo)

```php
$prenda = PrendaPedido::find(1);
$descripcion = $prenda->generarDescripcionDetallada(1);
echo $descripcion;
```

### Uso Directo (desde Helper)

```php
use App\Helpers\DescripcionPrendaHelper;

$prenda = [
    'numero' => 1,
    'tipo' => 'Camisa',
    'color' => 'Azul',
    'tela' => 'Denim',
    'ref' => 'REF-001',
    'manga' => 'Larga',
    'logo' => 'Bordado pecho',
    'bolsillos' => ['Pecho', 'Espalda'],
    'reflectivos' => [],
    'otros' => [],
    'tallas' => ['S' => 10, 'M' => 20],
];

$descripcion = DescripcionPrendaHelper::generarDescripcion($prenda);
```

### Extracción desde Modelo

```php
use App\Helpers\DescripcionPrendaHelper;

$prenda = PrendaPedido::with('color', 'tela', 'tipoManga')->find(1);
$datos = DescripcionPrendaHelper::extraerDatosPrenda($prenda, 1);
$descripcion = DescripcionPrendaHelper::generarDescripcion($datos);
```

## Pruebas

Se incluye archivo de demostración: `demo-descripcion.php`

**Ejecutar:**
```bash
php demo-descripcion.php
```

**Ejemplos incluidos:**
1. Descripción completa con todos los campos
2. Descripción mínima (solo datos básicos)
3. Descripción parcial (algunos detalles opcionales)

## Integración con Vistas

### Para mostrar en Blade:

```blade
@if($pedido->prendas)
    @foreach($pedido->prendas as $index => $prenda)
        <pre class="descripcion-prenda">{{ $prenda->generarDescripcionDetallada($index + 1) }}</pre>
    @endforeach
@endif
```

### Para mostrar en Modal:

```javascript
const descripcion = prenda.generarDescripcionDetallada(1);
// Usar con renderizador existente
renderDescripcionPrendasEnModal(descripcion);
```

## Campos Opcionales

Los siguientes campos son opcionales en el array `$prenda`:

- `ref` - Referencia de tela (se omite si está vacío)
- `logo` - Se omite si no hay valor
- `bolsillos` - Se omite si array está vacío
- `reflectivos` - Se omite si array está vacío
- `otros` - Se omite si array está vacío

## Estructura de BD Esperada

Para que `extraerDatosPrenda()` funcione correctamente, la tabla `prendas_pedido` debe tener:

```
- nombre_prenda (string)
- cantidad_talla (json/array)
- descripcion (text) - para logo y detalles
- descripcion_variaciones (text) - para manga, bolsillos, reflectivos
- color_id (FK)
- tela_id (FK)
- tipo_manga_id (FK)
```

## Próximos Pasos

1. ✅ Implementar Helper con funciones de generación
2. ✅ Actualizar modelos para usar el helper
3. ✅ Crear pruebas unitarias
4. ⏳ Ejecutar suite de pruebas completa
5. ⏳ Actualizar funciones JavaScript de renderizado
6. ⏳ Testing end-to-end en vistas

## Notas Importantes

- El helper **NO** modifica datos en BD, solo genera strings
- Las relaciones deben estar cargadas para mayor eficiencia
- Se recomienda usar `with()` para eager loading
- Compatible con versiones PHP 7.4+

