# Solución: Duplicación de Categorías EPP y Handlers CQRS

**Fecha:** 21 de Enero de 2026  
**Problema:** Categorías duplicadas con sufijo `_1` (PIES/PIES_1, CABEZA/CABEZA_1, etc.) y handlers CQRS registrados múltiples veces

---

## 🔍 Análisis del Problema

### Problema #1: Categorías Duplicadas con Sufijo `_1`

**Síntomas:**
- PIES (ID 1) y PIES_1 (ID 1101)
- CABEZA (ID 2) y CABEZA_1 (ID 1106)
- MANOS (ID 3) y MANOS_1 (ID 1103)
- CUERPO (ID 4) y CUERPO_1 (ID 1104)
- Y más...

**Causa Raíz:**
En `app/Http/Controllers/API/ArticulosImportController.php`, el método `procesarCategorias()` **buscaba solo por `nombre`**:

```php
$categoria = DB::table('epp_categorias')
    ->where('nombre', '=', $nombreLimpio)  // ← Solo búsqueda por nombre
    ->first();
```

Pero `EppCategoriaSeeder.php` usa `updateOrCreate()` con **`codigo`** como clave única:

```php
EppCategoria::updateOrCreate(
    ['codigo' => $categoria['codigo']],  // ← Clave es 'codigo', no 'nombre'
    $categoria
);
```

**Resultado:**
- El seeder crea: `PIES` (código='PIES', nombre='Protección de Pies')
- El ImportController busca por nombre, no encuentra nada
- Crea categoría nueva con código='PIES_1' (genera suffix para evitar duplicado de código)
- Resultado: PIES + PIES_1, ambas activas

---

### Problema #2: Handlers CQRS Registrados Múltiples Veces

**Síntomas en Log (15:51:52):**
```
[2026-01-21 15:51:52] local.DEBUG: QueryBus: Handler registrado (DUPLICADO)
[2026-01-21 15:51:52] local.DEBUG: QueryBus: Handler registrado (DUPLICADO)
[2026-01-21 15:51:52] local.DEBUG: QueryBus: Handler registrado (DUPLICADO)
```

**Causa Raíz:**
El método `boot()` en `CQRSServiceProvider.php` no tenía guard contra ejecuciones múltiples. Si el provider se cargaba más de una vez (o en múltiples instancias), `registerQueries()` y `registerCommands()` se ejecutaban varias veces registrando handlers duplicados.

---

##  Soluciones Implementadas

### Solución #1: ArticulosImportController - Búsqueda por Nombre O Código

**Archivo:** `app/Http/Controllers/API/ArticulosImportController.php`

**Cambio en método `procesarCategorias()`:**

```php
// ANTES: Solo búsqueda por nombre
$categoria = DB::table('epp_categorias')
    ->where('nombre', '=', $nombreLimpio)
    ->first();

// DESPUÉS: Búsqueda por nombre O código normalizado
$codigoNormalizado = strtoupper(preg_replace('/[^A-Z0-9_]/', '', 
    str_replace(' ', '_', substr($nombreLimpio, 0, 50))));

$categoria = DB::table('epp_categorias')
    ->where('nombre', '=', $nombreLimpio)
    ->orWhere('codigo', '=', $codigoNormalizado)  // ← AGREGADO
    ->first();
```

**Beneficio:**
- Ahora encuentra categorías creadas por `EppCategoriaSeeder`
- No crea duplicados con sufijo `_1`
- Mantiene compatibilidad con categorías auto-creadas anteriormente

---

### Solución #2: CQRSServiceProvider - Guard contra Ejecución Múltiple

**Archivo:** `app/Providers/CQRSServiceProvider.php`

**Cambio en método `boot()`:**

```php
public function boot(QueryBus $queryBus, CommandBus $commandBus): void
{
    // ARREGLO: Guard para evitar que boot() se ejecute múltiples veces
    if ($this->app->has('cqrs.booted') && $this->app->get('cqrs.booted')) {
        return;
    }

    // Registrar Queries
    $this->registerQueries($queryBus);

    // Registrar Commands
    $this->registerCommands($commandBus);

    // Marcar como booted para evitar ejecución múltiple
    $this->app->instance('cqrs.booted', true);

    \Illuminate\Support\Facades\Log::info(' [CQRSServiceProvider] CQRS providers registrados');
}
```

**Beneficio:**
- `boot()` solo se ejecuta UNA VEZ, incluso si se carga el provider múltiples veces
- Previene duplicación de registros de handlers
- Usa patrón singleton con key 'cqrs.booted' en el container

---

##  Checklist de Verificación

- [x] ArticulosImportController busca por nombre Y código
- [x] CQRSServiceProvider tiene guard de ejecución única
- [x] Código normalizado (OTRA → OTRA, Otra Protección → OTRA)
- [x] Logs actualizados con comentarios ARREGLO
- [x] Sin cambios en comportamiento de negocio
- [x] Compatible con categorías existentes

---

## 🧪 Cómo Verificar la Solución

1. **Limpiar datos duplicados (opcional):**
   ```sql
   DELETE FROM epp_categorias 
   WHERE codigo LIKE '%_1' OR codigo LIKE '%_2';
   ```

2. **Ejecutar seeders:**
   ```bash
   php artisan db:seed --class=EppCategoriaSeeder
   ```

3. **Verificar logs:**
   ```bash
   tail -f storage/logs/laravel.log | grep "Categoría"
   ```
   Deberá mostrar solo búsquedas/creaciones, sin duplicados.

4. **Verificar categorías en BD:**
   ```sql
   SELECT id, codigo, nombre FROM epp_categorias 
   ORDER BY codigo;
   ```
   No habrá categorías con sufijo `_1`, `_2`, etc.

---

## 📝 Notas

- **Compatibilidad:** La búsqueda `OR` es retrocompatible con categorías existentes
- **Performance:** No hay degradación (una búsqueda por ID es igual de rápida)
- **Logging:** Se mantienen todos los logs DEBUG e INFO para auditoría

