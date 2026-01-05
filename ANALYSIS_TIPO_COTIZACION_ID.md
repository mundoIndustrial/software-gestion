# Analysis: tipo_cotizacion_id Assignment in Cotizaciones

## Summary
Found the issue: **tipo_cotizacion_id = 3 is being assigned to "Prenda" (cloth only) cotizations in CotizacionPrendaController.php, but this is inconsistent with the main CotizacionController.php which uses hardcoded values 1, 2, and 4**.

---

## Current Implementation Details

### Database Setup
- **Table:** `tipos_cotizacion` (plural)
- **Fields:** `id` (auto-increment), `codigo` (string), `nombre` (string), `descripcion` (text), `activo` (boolean)
- **Method:** Uses dynamic lookup via `TipoCotizacion::getIdPorCodigo(string $codigo)`

### Seeded Types (CrearTiposCotizacionSeeder.php)
```php
[
    ['nombre' => 'Prenda/Logo',  'codigo' => 'prenda_logo',  'descripcion' => 'Cotización con prendas y LOGO'],
    ['nombre' => 'Logo',         'codigo' => 'logo',         'descripcion' => 'Cotización solo con LOGO'],
    ['nombre' => 'General',      'codigo' => 'general',      'descripcion' => 'Cotización general'],
]
```

**Note:** These are seeded in order, so they would typically have IDs 1, 2, 3 respectively.

---

## Issue #1: Hardcoded ID 3 in CotizacionPrendaController

**File:** [app/Infrastructure/Http/Controllers/CotizacionPrendaController.php](app/Infrastructure/Http/Controllers/CotizacionPrendaController.php#L75)

```php
$cotizacion = Cotizacion::create([
    // ...
    'tipo_cotizacion_id' => 3, // Cotización de Prenda ❌ HARDCODED
    // ...
]);
```

**Problem:**
- Assigns hardcoded ID 3 for "Prenda" (cloth only) cotizations
- ID 3 maps to 'general' type in the seeder (not 'prenda')
- Not consistent with the mapping in CotizacionController

---

## Issue #2: Hardcoded IDs 1, 2, 4 in CotizacionController

**File:** [app/Infrastructure/Http/Controllers/CotizacionController.php](app/Infrastructure/Http/Controllers/CotizacionController.php#L673-L684)

```php
// Mapear tipo a tipo_cotizacion_id
// Solo 3 tipos: Logo (L=2), Combinado (PL=1), Reflectivo (RF=4)
if ($tipoCotizacionEnviado === 'PL' || $tipoCotizacionEnviado === 'PB') {
    $tipoCotizacionId = 1; // Combinado
} elseif ($tipoCotizacionEnviado === 'L') {
    $tipoCotizacionId = 2; // Logo
} elseif ($tipoCotizacionEnviado === 'RF') {
    $tipoCotizacionId = 4; // Reflectivo ❌ ID 4 doesn't exist in seeder!
} else {
    $tipoCotizacionId = 1; // Por defecto Combinado
}
```

**Mapping in CotizacionController:**
- `PL` (Prenda/Logo - COMBINADA) → ID 1
- `L` (Logo only) → ID 2
- `RF` (Reflectivo) → ID 4 ⚠️ **This ID doesn't exist in the seeder!**
- Default → ID 1

**Problem:**
- References ID 4 (Reflectivo) which is NOT created by the seeder
- Only 3 types are seeded (IDs 1, 2, 3), so ID 4 doesn't exist
- **Mismatch:** Seeder creates 'general' (ID 3), but code expects it to be 'prenda' or references non-existent ID 4

---

## Issue #3: ID 3 Inconsistency

According to the seeder:
- **ID 1** = 'prenda_logo' (Prenda/Logo - COMBINADA)
- **ID 2** = 'logo' (Logo only)
- **ID 3** = 'general' (General - NOT prenda-only)

But CotizacionPrendaController hardcodes:
- **ID 3** = "Cotización de Prenda" (cloth only)

**This is a mismatch!** ID 3 should be 'general', not 'prenda'.

---

## The COMBINADA Issue

According to the code comments and update logic in CotizacionController (line 673):
> "Solo 3 tipos: Logo (L=2), Combinado (PL=1), Reflectivo (RF=4)"

And the user's statement:
> "For COMBINADA (combined) cotizations, ONLY ID 1 should be assigned, NOT ID 3"

**Current State:**
- COMBINADA (PL) correctly uses ID 1 ✅ in CotizacionController.update()
- But CotizacionPrendaController.store() uses ID 3 ❌ (incorrectly)

---

## Where tipo_cotizacion_id is Assigned

### 1. **CotizacionController.php - store() method**
   - **Line:** ~520-560 (in the store method)
   - **Logic:** Uses CrearCotizacionHandler
   - **Mapping:** PL → ID 1, L → ID 2, RF → ID 4
   - **Status:** References non-existent ID 4

### 2. **CotizacionController.php - update() method**
   - **Line:** 673-684
   - **Logic:** Updates tipo_cotizacion_id based on tipo_cotizacion parameter
   - **Mapping:** 
     - PL/PB → ID 1 (Combinado) ✅
     - L → ID 2 (Logo) ✅
     - RF → ID 4 (Reflectivo) ❌
   - **Status:** Same hardcoded values

### 3. **CotizacionPrendaController.php - store() method**
   - **Line:** 75
   - **Assignment:** `'tipo_cotizacion_id' => 3` ❌
   - **Comment:** "Cotización de Prenda"
   - **Status:** Hardcoded, inconsistent with seeder

### 4. **CotizacionBordadoController.php - store() method**
   - **Line:** 507
   - **Assignment:** `'tipo_cotizacion_id' => $tipoBordado->id`
   - **Logic:** Dynamically looks up 'L' (Logo) type
   - **Status:** Correct approach! Uses dynamic lookup ✅

---

## The Right Way: CotizacionBordadoController

```php
// Buscar el tipo de cotización "Logo/Bordado" dinámicamente
$tipoBordado = \App\Models\TipoCotizacion::where('codigo', 'L')->first();

if (!$tipoBordado) {
    throw new Exception('Error: Tipo de cotización Logo no está registrado en el sistema.');
}

$cotizacion = Cotizacion::create([
    // ...
    'tipo_cotizacion_id' => $tipoBordado->id, // Dynamic lookup ✅
    // ...
]);
```

**This is the correct pattern!** It:
- Uses dynamic lookup by `codigo` instead of hardcoding IDs
- Handles missing types gracefully
- Works regardless of actual ID values in database

---

## Recommendations

### 1. **Remove all hardcoded IDs**
   Replace:
   ```php
   'tipo_cotizacion_id' => 1; // Hardcoded
   ```
   With:
   ```php
   'tipo_cotizacion_id' => TipoCotizacion::getIdPorCodigo('prenda_logo') ?? 1;
   ```

### 2. **Update Seeder for Missing Type**
   If you need a "Reflectivo" type (RF), add it to CrearTiposCotizacionSeeder:
   ```php
   ['nombre' => 'Reflectivo', 'codigo' => 'reflectivo', 'descripcion' => 'Cotización con material reflectivo'],
   ```

### 3. **Replace Hardcoded Values in CotizacionController**
   - Line 673-684: Replace hardcoded IDs with dynamic lookups

### 4. **Fix CotizacionPrendaController**
   - **Line 75:** Change from `3` to dynamic lookup for the appropriate type
   - Question: Should "Prenda only" create type be 'prenda_logo' (ID 1) or something else?

### 5. **Consistent Approach**
   Use the pattern from CotizacionBordadoController everywhere

---

## Type Code Mapping (Proposed Fix)

```php
// In CotizacionController.php store() and update()
$tipoCodigoMap = [
    'PL' => 'prenda_logo',  // Combinado (Prenda + Logo)
    'PB' => 'prenda_logo',  // Combinado (same as PL)
    'L'  => 'logo',         // Logo only
    'RF' => 'reflectivo',   // Reflectivo (need to add to seeder)
    'P'  => 'prenda_logo',  // Prenda (should this be its own type?)
];

$codigoTipo = $tipoCodigoMap[$tipoCotizacionEnviado] ?? 'prenda_logo';
$tipoCotizacionId = TipoCotizacion::getIdPorCodigo($codigoTipo)?->id ?? 1;
```

---

## Files Needing Updates

1. **[app/Infrastructure/Http/Controllers/CotizacionController.php](app/Infrastructure/Http/Controllers/CotizacionController.php)**
   - Line 673-684 (update method)
   - Store method (if it has hardcoded IDs)

2. **[app/Infrastructure/Http/Controllers/CotizacionPrendaController.php](app/Infrastructure/Http/Controllers/CotizacionPrendaController.php)**
   - Line 75 (store method)

3. **[database/seeders/CrearTiposCotizacionSeeder.php](database/seeders/CrearTiposCotizacionSeeder.php)**
   - Add 'Reflectivo' type if needed

4. **[app/Http/Controllers/CotizacionBordadoController.php](app/Infrastructure/Http/Controllers/CotizacionBordadoController.php)**
   - Already uses correct pattern ✅

---

## Key Findings Summary

| Aspect | Status | Issue |
|--------|--------|-------|
| COMBINADA (PL) in update() | ✅ Correct ID 1 | None |
| Logo (L) in update() | ✅ Correct ID 2 | None |
| Reflectivo (RF) in update() | ❌ References non-existent ID 4 | Seeder doesn't create this type |
| Prenda only (P) in PrendaController | ❌ Hardcoded ID 3 | Should use 'prenda_logo' (ID 1) or dynamic lookup |
| BordadoController | ✅ Correct pattern | Uses dynamic lookup by codigo |
| Hardcoded IDs | ❌ Bad practice | Should use dynamic lookup |

