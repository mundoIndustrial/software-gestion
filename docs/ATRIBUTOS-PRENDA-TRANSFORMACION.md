# Transformación de Atributos de Prenda - Documentación

## Resumen

Igual que con los `estados`, ahora tenemos un sistema de transformación legible para:
- **COLOR**: color_id → nombre del color
- **TELA**: tela_id → nombre de la tela
- **REFERENCIA**: tela_id → referencia de la tela

## Componentes Creados

### 1. Helper: `AtributosPrendaHelper`
**Ubicación**: `app/Helpers/AtributosPrendaHelper.php`

Métodos disponibles:
```php
// Obtener nombre del color
AtributosPrendaHelper::obtenerNombreColor($color_id)

// Obtener nombre de la tela
AtributosPrendaHelper::obtenerNombreTela($tela_id)

// Obtener referencia de la tela
AtributosPrendaHelper::obtenerReferenciaTela($tela_id)

// Obtener tela formateada: "Nombre (Ref: XXXXX)"
AtributosPrendaHelper::formatearTela($tela_id)

// Obtener toda la info del color
AtributosPrendaHelper::obtenerColor($color_id)

// Obtener toda la info de la tela
AtributosPrendaHelper::obtenerTela($tela_id)
```

### 2. Directivas Blade

Las directivas están registradas en `app/Providers/BladeDirectivesServiceProvider.php`:

```blade
<!-- Para COLOR -->
@colorNombre($color_id)           <!-- Alias: @colorLabel($color_id) -->

<!-- Para TELA -->
@telaNombre($tela_id)             <!-- Alias: @telaLabel($tela_id) -->
@telaReferencia($tela_id)
@telaFormato($tela_id)            <!-- "Nombre (Ref: XXXXX)" -->
```

### 3. Trait: `HasLegibleAtributosPrenda`
**Ubicación**: `app/Traits/HasLegibleAtributosPrenda.php`

Para modelos que tengan campos `color_id` y `tela_id`:

```php
use App\Traits\HasLegibleAtributosPrenda;

class MiModelo extends Model
{
    use HasLegibleAtributosPrenda;
}
```

Proporciona estos atributos de acceso:
```php
$modelo->color_label           // Nombre del color
$modelo->tela_label            // Nombre de la tela
$modelo->tela_referencia       // Referencia de la tela
$modelo->tela_formato          // "Nombre (Ref: XXXXX)"
$modelo->color_info            // Array con id, nombre, codigo
$modelo->tela_info             // Array con id, nombre, referencia, descripcion
```

## Ejemplos de Uso

### En Blade Views

**Cuando solo tienes el ID:**
```blade
<!-- Mostrar nombre del color -->
<span>@colorNombre($product->color_id)</span>

<!-- Mostrar nombre y referencia de la tela -->
<div>
    <strong>Tela:</strong> @telaNombre($product->tela_id)
    @if(!empty(@telaReferencia($product->tela_id)))
        <span style="color: #666;">(Ref: @telaReferencia($product->tela_id))</span>
    @endif
</div>

<!-- Mostrar tela con todo formateado -->
<span>@telaFormato($product->tela_id)</span>
```

**Cuando tienes el modelo con trait:**
```blade
<!-- Usando atributos del modelo -->
<span>{{ $product->color_label }}</span>
<span>{{ $product->tela_label }}</span>
<span>{{ $product->tela_referencia }}</span>
<span>{{ $product->tela_formato }}</span>

<!-- Acceder a información completa -->
@php
    $colorInfo = $product->color_info;
    $telaInfo = $product->tela_info;
@endphp
```

**Cuando tienes relaciones cargadas:**
```blade
<!-- Ya está siendo usado así en todo el proyecto -->
<span>{{ $product->color->nombre }}</span>
<span>{{ $product->tela->nombre }}</span>
<span>{{ $product->tela->referencia }}</span>
```

### En PHP (Controllers, Services, etc.)

```php
use App\Helpers\AtributosPrendaHelper;

// En un controller o service
$colorNombre = AtributosPrendaHelper::obtenerNombreColor($colorId);
$telaNombre = AtributosPrendaHelper::obtenerNombreTela($telaId);
$telaInfo = AtributosPrendaHelper::obtenerTela($telaId);

// Si el modelo usa el trait
$product->color_label    // Acceso directo
$product->tela_formato   // Formato personalizado
```

### En JavaScript/AJAX

Para respuestas AJAX, el helper se puede usar en controllers:

```php
public function obtenerProducto($id)
{
    $producto = Producto::find($id);
    
    return [
        'id' => $producto->id,
        'nombre' => $producto->nombre,
        'color_nombre' => AtributosPrendaHelper::obtenerNombreColor($producto->color_id),
        'tela_formato' => AtributosPrendaHelper::formatearTela($producto->tela_id),
    ];
}
```

## Optimización

El helper incluye **caché en memoria** para evitar múltiples consultas a BD:

```php
// Primera llamada: consulta a BD
AtributosPrendaHelper::obtenerNombreColor(5)  // SELECT...

// Siguientes llamadas: desde caché
AtributosPrendaHelper::obtenerNombreColor(5)  // Desde caché
AtributosPrendaHelper::obtenerNombreColor(5)  // Desde caché

// Limpiar caché si es necesario
AtributosPrendaHelper::limpiarCaches();
```

## Comparación con Sistema de Estados

| Aspecto | Estados | Atributos Prenda |
|--------|---------|-----------------|
| Tipo | Enum (PHP 8.1) | Database relations |
| Origen | Clase Enum | Tablas: colores_prenda, telas_prenda |
| Helper | EstadoHelper | AtributosPrendaHelper |
| Trait | HasLegibleEstado | HasLegibleAtributosPrenda |
| Directivas | @estadoLabelCotizacion | @colorNombre, @telaNombre |
| Caché | Enums nativos | Memory cache en helper |

## Vistas que Usan Este Sistema

Ya están usando relaciones cargadas (recomendado):
- `resources/views/asesores/cotizaciones/show.blade.php` - Variantes de cotización
- `resources/views/asesores/pedidos/show.blade.php` - Productos de pedido
- `resources/views/tableros.blade.php` - Tablero de telas

Pueden ser mejoradas con el helper si se usan IDs sin relaciones:
- Cualquier otra tabla que muestre color_id/tela_id sin eager loading

## Recomendaciones

1. **Preferencia de uso (por orden)**:
   - ✅ Relaciones cargadas: `{{ $model->tela->nombre }}`
   - ✅ Atributos de acceso: `{{ $model->tela_label }}`
   - ✅ Directivas Blade: `@telaNombre($tela_id)`
   - ✅ Helper directo: `AtributosPrendaHelper::obtenerNombreTela($id)`

2. **Para modelos nuevos**: Añade el trait `HasLegibleAtributosPrenda` si tienen campos `color_id` o `tela_id`

3. **Para APIs**: Usa el helper en Resources para transformar al serializar

4. **Performance**: El caché del helper evita N+1 queries en bucles

## Estado de Implementación

- ✅ Helper creado y funcional
- ✅ Directivas Blade registradas
- ✅ Trait para modelos disponible
- ✅ Documentación completa
- ✅ Sistema listo para uso en vistas y controllers
