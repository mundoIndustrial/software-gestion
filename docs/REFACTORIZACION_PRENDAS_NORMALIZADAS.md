# Refactorización: Normalización de Prendas en Pedidos de Producción

## 📋 Resumen Ejecutivo

Se ha normalizado la tabla **EXISTENTE** `prendas_pedido` sacando los campos de variantes a una tabla hija `prenda_variantes`, siguiendo principios de DDD y buenas prácticas de diseño de ERPs para producción textil.

### Cambios Principales

✅ **Migración de Tabla Existente**: Refactorización de estructura, NO creación nueva  
✅ **Foreign Key**: `numero_pedido` → `pedido_produccion_id` (relación directa con tabla padre)  
✅ **Separación de Responsabilidades**: Datos básicos en `prendas_pedido`, variantes en `prenda_variantes`  
✅ **Migración de Datos**: Scripts automáticos para mover datos de variantes a tabla hija  
✅ **Eliminación de Reflectivo**: NO se gestiona reflectivo (OUT OF SCOPE)  
✅ **Eliminación de Campos Redundantes**: `cantidad`, `descripcion_variaciones` (ahora calculados)  
✅ **Escalabilidad**: Diseño apto para múltiples variantes por prenda  

---

## 🏗️ Estructura de Bases de Datos

### Tabla: `prendas_pedido` (Padre)

```sql
CREATE TABLE prendas_pedido (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    pedido_produccion_id BIGINT UNSIGNED NOT NULL,
    nombre_prenda VARCHAR(255),
    descripcion TEXT,
    genero ENUM('Dama', 'Caballero', 'Mixto', 'Infantil', 'N/A'),
    de_bodega BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (pedido_produccion_id) REFERENCES pedidos_produccion(id) ON DELETE CASCADE,
    INDEX (pedido_produccion_id),
    INDEX (de_bodega)
);
```

**Campos**:
- `pedido_produccion_id`: FK a `pedidos_produccion.id` (cascada)
- `nombre_prenda`: Nombre comercial (ej: "CAMISA POLO")
- `descripcion`: Detalles generales
- `genero`: Género de la prenda
- `de_bodega`: ¿Es de bodega existente o nueva?

---

### Tabla: `prenda_variantes` (Hija)

```sql
CREATE TABLE prenda_variantes (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    prenda_pedido_id BIGINT UNSIGNED NOT NULL,
    talla VARCHAR(50),
    cantidad UNSIGNED INT DEFAULT 0,
    color_id BIGINT UNSIGNED,
    tela_id BIGINT UNSIGNED,
    tipo_manga_id BIGINT UNSIGNED,
    tipo_broche_boton_id BIGINT UNSIGNED,
    manga_obs TEXT,
    broche_boton_obs TEXT,
    tiene_bolsillos BOOLEAN DEFAULT FALSE,
    bolsillos_obs TEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (prenda_pedido_id) REFERENCES prendas_pedido(id) ON DELETE CASCADE,
    FOREIGN KEY (color_id) REFERENCES colores_prenda(id) ON DELETE SET NULL,
    FOREIGN KEY (tela_id) REFERENCES telas_prenda(id) ON DELETE SET NULL,
    FOREIGN KEY (tipo_manga_id) REFERENCES tipos_manga(id) ON DELETE SET NULL,
    FOREIGN KEY (tipo_broche_boton_id) REFERENCES tipos_broche(id) ON DELETE SET NULL,
    
    INDEX (prenda_pedido_id),
    INDEX (talla),
    INDEX (color_id),
    INDEX (tela_id),
    INDEX (tipo_manga_id),
    INDEX (tipo_broche_boton_id),
    
    UNIQUE KEY unique_prenda_variante (
        prenda_pedido_id, 
        talla, 
        color_id, 
        tela_id, 
        tipo_manga_id, 
        tipo_broche_boton_id
    )
);
```

**Campos**:
- `talla`: Identificador de talla (S, M, L, XL, etc.)
- `cantidad`: Unidades para esta combinación
- `color_id`, `tela_id`, `tipo_manga_id`: FKs a catálogos
- `tipo_broche_boton_id`: Broche O botón (mismo catálogo)
- Observaciones específicas: `manga_obs`, `broche_boton_obs`, `bolsillos_obs`

---

## 📊 Diagrama Entidad-Relación

```
┌─────────────────────────────────────────────────────────┐
│            pedidos_produccion                            │
│  ────────────────────────────────────────────────────    │
│  id (PK)                                                 │
│  numero_pedido                                           │
│  cliente_id                                              │
│  estado                                                  │
│  ...                                                     │
└──────────────────┬──────────────────────────────────────┘
                   │
                   │ 1:N (ON DELETE CASCADE)
                   │
                   ↓
┌──────────────────────────────────────────────────────────┐
│          prendas_pedido                                   │
│  ────────────────────────────────────────────────────    │
│  id (PK)                                                 │
│  pedido_produccion_id (FK) ──────────┐                  │
│  nombre_prenda                       │                  │
│  descripcion                         │                  │
│  genero                              │                  │
│  de_bodega                           │                  │
│  created_at, updated_at, deleted_at  │                  │
└──────────────────┬──────────────────────────────────────┘
                   │
                   │ 1:N (ON DELETE CASCADE)
                   │
                   ↓
┌──────────────────────────────────────────────────────────┐
│         prenda_variantes                                  │
│  ────────────────────────────────────────────────────    │
│  id (PK)                                                 │
│  prenda_pedido_id (FK)                                  │
│  talla                                                   │
│  cantidad                                                │
│  color_id (FK) ──────────→ colores_prenda                │
│  tela_id (FK) ───────────→ telas_prenda                  │
│  tipo_manga_id (FK) ─────→ tipos_manga                   │
│  tipo_broche_boton_id (FK) → tipos_broche               │
│  manga_obs, broche_boton_obs, bolsillos_obs             │
│  created_at, updated_at                                  │
└────────────────────────────────────────────────────────┬┘
                                                         │
              ┌──────────────────────────────────────────┘
              │
    ┌─────────┴─────────┬──────────────┬─────────────┐
    ↓                   ↓              ↓             ↓
┌────────────┐ ┌──────────────┐ ┌──────────┐ ┌─────────────┐
│  colores   │ │ telas_prenda │ │ tipos    │ │ tipos_broche│
│  _prenda   │ │              │ │ _manga   │ │             │
└────────────┘ └──────────────┘ └──────────┘ └─────────────┘
```

---

## 🔗 Relaciones Eloquent

### Modelo: `PrendaPedido`

```php
class PrendaPedido extends Model {
    // Una prenda pertenece a UN pedido
    public function pedidoProduccion(): BelongsTo {
        return $this->belongsTo(PedidoProduccion::class, 'pedido_produccion_id');
    }
    
    // Una prenda tiene MUCHAS variantes
    public function variantes(): HasMany {
        return $this->hasMany(PrendaVariante::class, 'prenda_pedido_id');
    }
    
    // Accessor: Cantidad total = suma de variantes
    public function getCantidadTotalAttribute(): int {
        return $this->variantes()->sum('cantidad') ?? 0;
    }
}
```

### Modelo: `PrendaVariante`

```php
class PrendaVariante extends Model {
    // Una variante pertenece a UNA prenda
    public function prendaPedido(): BelongsTo {
        return $this->belongsTo(PrendaPedido::class, 'prenda_pedido_id');
    }
    
    // Una variante tiene UN color
    public function color(): BelongsTo {
        return $this->belongsTo(ColorPrenda::class, 'color_id');
    }
    
    // Una variante tiene UNA tela
    public function tela(): BelongsTo {
        return $this->belongsTo(TelaPrenda::class, 'tela_id');
    }
    
    // Una variante tiene UN tipo de manga
    public function tipoManga(): BelongsTo {
        return $this->belongsTo(TipoManga::class, 'tipo_manga_id');
    }
    
    // Una variante tiene UN tipo de broche/botón
    public function tipoBrocheBoton(): BelongsTo {
        return $this->belongsTo(TipoBroche::class, 'tipo_broche_boton_id');
    }
}
```

### Modelo: `PedidoProduccion` (REFACTORIZADO)

```php
class PedidoProduccion extends Model {
    // Un pedido tiene MUCHAS prendas
    public function prendasPed(): HasMany {
        return $this->hasMany(PrendaPedido::class, 'pedido_produccion_id', 'id');
    }
}
```

---

## 📝 Ejemplo de Uso

### Crear una Prenda

```php
$prenda = PrendaPedido::create([
    'pedido_produccion_id' => $pedido->id,  // ← NUEVO: Usa pedido_produccion_id
    'nombre_prenda' => 'CAMISA POLO',
    'descripcion' => 'Camisa tipo polo de algodón',
    'genero' => 'Dama',
    'de_bodega' => false,
]);
```

```php
// Variante 1: Talla M, Rojo, Algodón, Manga Corta, 50 unidades
$prenda->variantes()->create([
    'talla' => 'M',
    'cantidad' => 50,
    'color_id' => ColorPrenda::where('nombre', 'Rojo')->first()->id,
    'tela_id' => TelaPrenda::where('nombre', 'Algodón 100%')->first()->id,
    'tipo_manga_id' => TipoManga::where('nombre', 'Corta')->first()->id,
    'tipo_broche_boton_id' => TipoBroche::where('nombre', 'Botones')->first()->id,
    'tiene_bolsillos' => true,
    'bolsillos_obs' => 'Bolsillo pecho',
]);

// Variante 2: Talla L, Azul, Algodón, Manga Larga, 30 unidades
$prenda->variantes()->create([
    'talla' => 'L',
    'cantidad' => 30,
    'color_id' => ColorPrenda::where('nombre', 'Azul')->first()->id,
    'tela_id' => TelaPrenda::where('nombre', 'Algodón 100%')->first()->id,
    'tipo_manga_id' => TipoManga::where('nombre', 'Larga')->first()->id,
    'tipo_broche_boton_id' => TipoBroche::where('nombre', 'Broche Snap')->first()->id,
    'tiene_bolsillos' => false,
]);
```

### Consultar Datos

```php
// Obtener pedido con todas sus prendas y variantes (EAGER LOADING)
$pedido = PedidoProduccion::with([
    'prendasPed.variantes.color',
    'prendasPed.variantes.tela',
    'prendasPed.variantes.tipoManga',
    'prendasPed.variantes.tipoBrocheBoton'
])->find($id);

// Iterar prendas
foreach ($pedido->prendasPed as $prenda) {
    echo "Prenda: {$prenda->nombre_prenda}";
    echo "Cantidad Total: {$prenda->cantidad_total}";  // Accessor
    echo "Descripción Variantes: {$prenda->descripcion_variantes}";  // Accessor
    
    // Variantes
    foreach ($prenda->variantes as $variante) {
        echo "  - Talla: {$variante->talla}";
        echo "    Color: {$variante->color->nombre}";
        echo "    Tela: {$variante->tela->nombre}";
        echo "    Cantidad: {$variante->cantidad}";
        echo "    Descripción: {$variante->descripcion_completa}";  // Accessor
    }
}

// Obtener tallas disponibles
$tallas = $prenda->obtenerTallasDisponibles();

// Obtener cantidades por talla
$cantidadesPorTalla = $prenda->obtenerCantidadesPorTalla();

// Obtener información detallada para reporte
$info = $prenda->obtenerInfoDetallada();
```

### Scopes Útiles

```php
// Filtrar por pedido
PrendaPedido::porPedido($pedidoId)->get();

// Filtrar por origen
PrendaPedido::porOrigen($deBodega = false)->get();

// Filtrar por género
PrendaPedido::porGenero('Dama')->get();

// Variantes con bolsillos
PrendaVariante::conBolsillos()->get();

// Variantes por talla
PrendaVariante::porTalla('M')->get();

// Variantes por color
PrendaVariante::porColor($colorId)->get();
```

---

## 🔄 Migraciones

### Orden de Ejecución Crítico

⚠️ **LAS MIGRACIONES DEBEN EJECUTARSE EN ESTE ORDEN:**

1. **`2026_01_16_normalize_prendas_pedido.php`**
   - Altera tabla EXISTENTE `prendas_pedido`
   - Agrega `pedido_produccion_id` (FK)
   - Migra datos: `numero_pedido` → `pedido_produccion_id`
   - Elimina `numero_pedido`
   - Elimina campos de variantes (color_id, tela_id, etc.)
   - Elimina campos de reflectivo

2. **`2026_01_16_create_prenda_variantes_table.php`**
   - Crea tabla `prenda_variantes` DESPUÉS de normalizar padre
   - Define FKs a catálogos
   - Crea índice UNIQUE

3. **`2026_01_16_migrate_prenda_variantes_data.php`**
   - Migra DATOS de variantes a tabla hija
   - Procesa `cantidad_talla` JSON
   - Crea variante por cada talla
   - Preserva observaciones

### Estructura ANTES (Actual)

```sql
prendas_pedido:
- id
- numero_pedido (INT) ← Problema: no es FK
- nombre_prenda
- cantidad (INT) ← Será eliminado (redundante)
- descripcion
- descripcion_variaciones (JSON) ← Será eliminado
- cantidad_talla (JSON) ← Se normalizará a prenda_variantes
- color_id ← Se mueve a prenda_variantes
- tela_id ← Se mueve a prenda_variantes
- tipo_manga_id ← Se mueve a prenda_variantes
- tipo_broche_id ← Se mueve a prenda_variantes
- tiene_bolsillos ← Se mueve a prenda_variantes
- manga_obs ← Se mueve a prenda_variantes
- bolsillos_obs ← Se mueve a prenda_variantes
- broche_obs ← Se mueve a prenda_variantes
- tiene_reflectivo ← SE ELIMINA (OUT OF SCOPE)
- reflectivo_obs ← SE ELIMINA (OUT OF SCOPE)
- genero
- de_bodega
```

### Estructura DESPUÉS (Target)

**Tabla: `prendas_pedido`**
```sql
- id (PK)
- pedido_produccion_id (FK) ← NUEVO
- nombre_prenda
- descripcion
- genero
- de_bodega
- created_at
- updated_at
- deleted_at
```

**Tabla: `prenda_variantes` (Nueva)**
```sql
- id (PK)
- prenda_pedido_id (FK)
- talla
- cantidad
- color_id (FK)
- tela_id (FK)
- tipo_manga_id (FK)
- tipo_broche_boton_id (FK)
- manga_obs
- broche_boton_obs
- tiene_bolsillos
- bolsillos_obs
- created_at
- updated_at
```

---

## ✅ Verificación Post-Migración

```php
// Verificar estructura
Schema::hasTable('prendas_pedido');           // true
Schema::hasTable('prenda_variantes');         // true
Schema::hasColumn('prendas_pedido', 'pedido_produccion_id');
Schema::hasColumn('prenda_variantes', 'prenda_pedido_id');

// Verificar relaciones
$pedido->prendasPed()->count();               // N prendas
$prenda->variantes()->count();                // N variantes
$variante->prendaPedido()->exists();          // true
$variante->color()->exists();                 // true/false según datos
```

---

## 🎯 Ventajas del Nuevo Diseño

✅ **Normalización**: Datos en su forma más atómica  
✅ **Escalabilidad**: Fácil agregar nuevas variantes  
✅ **Integridad**: FK con ON DELETE CASCADE  
✅ **Performance**: Índices en campos frecuentes  
✅ **Mantenibilidad**: Responsabilidades claras  
✅ **Queries Eficientes**: Eager loading con relaciones  
✅ **SIN Reflectivo**: OUT OF SCOPE (se gestiona por separado)  

---

## ⚠️ Notas Importantes

1. **Reflectivo**: NO incluido en este diseño. Se gestiona a través de `PrendaReflectivo` si es necesario.

2. **Backward Compatibility**: Si existen datos en la tabla antigua con `numero_pedido`, se requiere migración de datos.

3. **Unicidad de Variantes**: El índice UNIQUE asegura que no haya duplicados para la misma combinación de atributos.

4. **Observaciones**: Cada característica (manga, broche, bolsillos) tiene su campo `_obs` para notas específicas.

5. **Foreign Keys**: Todas apuntan a catálogos existentes:
   - `colores_prenda`
   - `telas_prenda`
   - `tipos_manga`
   - `tipos_broche`

---

## 📚 Archivos Creados/Modificados

### Creados
- ✅ `app/Models/PrendaVariante.php` - Nuevo modelo para variantes
- ✅ `database/migrations/2026_01_16_normalize_prendas_pedido.php`
- ✅ `database/migrations/2026_01_16_create_prenda_variantes_table.php`

### Refactorizados
- ✅ `app/Models/PrendaPedido.php` - Actualizado con nueva estructura
- ✅ `app/Models/PedidoProduccion.php` - Relación actualizada a usar `pedido_produccion_id`

---

## 🚀 Próximos Pasos

1. Ejecutar migraciones: `php artisan migrate`
2. Migrar datos de tabla antigua (si aplica)
3. Actualizar servicios que manipulen prendas
4. Ejecutar tests para validar relaciones
5. Actualizar documentación en proyectos dependientes

---

**Última actualización**: 16 de Enero, 2026  
**Versión**: 1.0  
**Estado**: ✅ Implementado
