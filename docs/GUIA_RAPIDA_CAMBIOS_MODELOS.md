#  GUÍA RÁPIDA: CAMBIOS EN MODELOS Y RELACIONES

**Última Actualización:** 16 de Enero, 2026

---

## CAMBIO PRINCIPAL

```
tipos_broche  →  tipos_broche_boton
tipo_broche_id  →  tipo_broche_boton_id
```

---

## MODELOS ACTUALIZADOS

### 1️⃣ TipoBroche

```php
// app/Models/TipoBroche.php
class TipoBroche extends Model
{
    protected $table = 'tipos_broche_boton';  // ← CAMBIO
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
```

**Impacto:** Todas las queries sobre `TipoBroche::where()`, inserts, updates van a tabla `tipos_broche_boton`

---

### 2️⃣ TipoManga

```php
// app/Models/TipoManga.php
class TipoManga extends Model
{
    protected $table = 'tipos_manga';  //  Sin cambios
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
```

---

### 3️⃣ TelaPrenda

```php
// app/Models/TelaPrenda.php
class TelaPrenda extends Model
{
    protected $table = 'telas_prenda';  //  Sin cambios
    protected $fillable = ['nombre', 'referencia', 'descripcion', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
```

---

### 4️⃣ PrendaVariante (Relación)

```php
// app/Models/PrendaVariante.php
public function tipoBrocheBoton(): BelongsTo
{
    // CAMBIO: tipo_broche_boton_id (antes tipo_broche_id)
    return $this->belongsTo(TipoBroche::class, 'tipo_broche_boton_id');
}
```

---

## SERVICIOS ACTUALIZADOS

### EnriquecerDatosService

```php
// app/Services/Pedidos/EnriquecerDatosService.php

// ANTES:
DB::table('tipos_broche')->where('nombre', $prenda['broche'])->first();
$prenda['tipo_broche_id'] = $broche->id;

// DESPUÉS:
DB::table('tipos_broche_boton')->where('nombre', $prenda['broche'])->first();
$prenda['tipo_broche_boton_id'] = $broche->id;
```

---

## VALIDACIONES ACTUALIZADAS

### SupervisorPedidosController

```php
// app/Http/Controllers/SupervisorPedidosController.php

// ANTES:
'prendas.*.tipo_broche_id' => 'nullable|exists:tipos_broche,id',

// DESPUÉS:
'prendas.*.tipo_broche_boton_id' => 'nullable|exists:tipos_broche_boton,id',
```

---

## JSON ENVIADO DESDE FRONTEND

El JSON que envía el frontend debe usar:

```json
{
  "variantes": [
    {
      "tipo_broche_boton_id": 1,     ← CAMBIO (antes: tipo_broche_id)
      "broche_boton_obs": "..."
    }
  ]
}
```

### Cambios en JavaScript

Busca y reemplaza en tus archivos JavaScript:

```javascript
// ANTES:
tipo_broche_id: dataVariante.tipo_broche_id || null

// DESPUÉS:
tipo_broche_boton_id: dataVariante.tipo_broche_boton_id || null
```

---

## ARCHIVOS MODIFICADOS

| Archivo | Cambio | Líneas |
|---------|--------|--------|
| `app/Models/TipoBroche.php` | `'tipos_broche'` → `'tipos_broche_boton'` | 9 |
| `app/Models/TipoManga.php` | Documentación | 1-8 |
| `app/Models/TelaPrenda.php` | Documentación | 1-15 |
| `app/Models/PrendaVariante.php` | Comentario relación | 97-107 |
| `app/Services/Pedidos/EnriquecerDatosService.php` | 4 cambios | 12, 91, 96, 101, 107 |
| `app/Http/Controllers/SupervisorPedidosController.php` | 1 cambio | 1001 |

---

## SQL PARA BASE DE DATOS

```sql
-- 1. Renombrar tabla
RENAME TABLE tipos_broche TO tipos_broche_boton;

-- 2. Verificar que la tabla existe
SHOW TABLES LIKE 'tipos_broche_boton';

-- 3. Ver estructura
DESC tipos_broche_boton;

-- 4. Contar registros
SELECT COUNT(*) FROM tipos_broche_boton;
```

---

## VERIFICACIÓN

```php
// Test que los modelos usan la tabla correcta
\Log::info('TipoBroche table: ' . (new TipoBroche())->getTable()); 
// Output: TipoBroche table: tipos_broche_boton

\Log::info('TipoManga table: ' . (new TipoManga())->getTable());
// Output: TipoManga table: tipos_manga

\Log::info('TelaPrenda table: ' . (new TelaPrenda())->getTable());
// Output: TelaPrenda table: telas_prenda

// Verificar que la relación funciona
$variante = PrendaVariante::with('tipoBrocheBoton')->first();
dd($variante->tipoBrocheBoton); // Debe retornar TipoBroche o null
```

---

## CHECKLIST

- [x] Modelo `TipoBroche` apunta a `tipos_broche_boton`
- [x] Relación `PrendaVariante::tipoBrocheBoton()` usa `tipo_broche_boton_id`
- [x] Servicio `EnriquecerDatosService` usa tabla correcta
- [x] Validación usa tabla correcta
- [x] Documentación completa
- [ ] **TODO**: Actualizar frontend (campos JSON)
- [ ] **TODO**: Ejecutar migraciones en BD
- [ ] **TODO**: Ejecutar tests

---

## IMPACTO EN OTRAS ÁREAS

### Frontend (PENDIENTE)

Archivos que deben actualizar `tipo_broche_id` → `tipo_broche_boton_id`:

```
public/js/pedidos-produccion/form-handlers.js
public/js/pedidos-produccion/PedidoFormManager.js
public/js/pedidos-produccion/PedidoValidator.js
public/js/asesores/cotizaciones/cotizaciones.js
public/js/asesores/cotizaciones/cargar-borrador.js
resources/views/asesores/pedidos/show.blade.php
resources/views/components/template-producto.blade.php
```

### Base de Datos (PENDIENTE)

- Renombrar tabla `tipos_broche` a `tipos_broche_boton`
- Actualizar foreign keys en tablas relacionadas
- Considerar crear migración explícita

---

## PREGUNTAS FRECUENTES

**P: ¿Cambió el nombre del modelo?**  
R: No, sigue siendo `TipoBroche`. Solo cambió la tabla que usa internamente.

**P: ¿Necesito actualizar todas las queries?**  
R: No si usas el modelo Eloquent. Si usas `DB::table('tipos_broche')`, sí.

**P: ¿Cómo sé si necesito actualizar mi código?**  
R: Busca `'tipos_broche'` (comillas) o `tipos_broche_id` en tu código.

**P: ¿Se pierden datos al renombrar?**  
R: No. Es solo un renombre de tabla, los datos permanecen.

---

## CONTACTO

Para dudas sobre esta actualización, referencia:
- [ACTUALIZACION_MODELOS_TABLAS_16ENE2026.md](ACTUALIZACION_MODELOS_TABLAS_16ENE2026.md) - Documento completo
- [ENTREGA_FINAL_AUDITORIA.md](ENTREGA_FINAL_AUDITORIA.md) - Contexto general

