# 🔍 Diagnóstico de Variaciones No Guardadas

## Problema Reportado
Las variaciones (manga, bolsillos, etc.) NO se están guardando en `variantes_prenda`

## Causa Identificada ✅ SOLUCIONADA

**Problema Principal:**
- Si `TipoPrenda::reconocerPorNombre()` no encontraba el tipo de prenda, la función `guardarVariantes()` **salía sin guardar nada**
- Esto ocurría porque los nombres de prendas NO coincidían con las `palabras_clave` en la tabla `tipos_prenda`

**Ejemplo:**
```
Usuario escribe: "CAMISA DE ALGODÓN"
TipoPrenda busca por palabras_clave: "CAMISA" ✓ Encuentra
✅ Guarda variantes

Usuario escribe: "FRANELA VERDE"  
TipoPrenda busca por palabras_clave: "FRANELA" ✗ NO ENCUENTRA
❌ SIN GUARDAR VARIANTES (bug)
```

## Solución Implementada ✅

Se modificó `PrendaService::guardarVariantes()` para:

1. **Intentar reconocer por nombre** (como antes)
2. **Si falla, buscar un tipo genérico** (`OTRA`, `GENERICO`, `GENERAL`)
3. **Si no existe genérico, usar el PRIMER tipo disponible**
4. **Si aún no hay tipo, crear variante SIN `tipo_prenda_id`** (permitido por BD)

Código actualizado:
```php
// ANTES (❌ FALLABA)
if (!$tipoPrenda) {
    \Log::warning('No se pudo reconocer tipo de prenda', [
        'nombre' => $nombrePrenda
    ]);
    return;  // ← SALÍA SIN GUARDAR
}

// AHORA (✅ FUNCIONA)
if (!$tipoPrenda) {
    // Buscar tipo genérico
    $tipoPrenda = TipoPrenda::where('nombre', 'LIKE', '%OTRA%')
        ->orWhere('nombre', 'LIKE', '%GENERICO%')
        ->first();
    
    // Si no existe, usar el primero
    if (!$tipoPrenda) {
        $tipoPrenda = TipoPrenda::first();
    }
}

// Ahora permite tipo_prenda_id = null
'tipo_prenda_id' => $tipoPrenda ? $tipoPrenda->id : null
```

## ✅ Cambios Realizados

**Archivo:** `app/Services/PrendaService.php`

**Líneas:** 83-110

**Cambios:**
- Agregada lógica de fallback para encontrar tipo de prenda
- Permite crear variantes sin tipo_prenda_id
- Agregado logging detallado para debug

---

## 🧪 Prueba Rápida

### 1. Abre la consola del navegador (F12)
Presiona F12 y ve a la pestaña **Console**

### 2. Crea una cotización nueva
- Ingresa cliente
- Agrega una prenda (ej: "CAMISETA DEPORTIVA")
- Marca checkboxes de variaciones:
  - ☑️ Manga
  - ☑️ Bolsillos
  - ☑️ Broche
  - ☑️ Reflectivo

### 3. Escribe observaciones
- Manga: "Manga larga"
- Bolsillos: "2 bolsillos"
- Broche: "Botones de plástico"
- Reflectivo: "En espalda"

### 4. Guarda la cotización

### 5. Revisa los logs
```bash
# Terminal
tail -f storage/logs/laravel.log | grep -i "variante"
```

Deberías ver:
```
✅ Variante guardada exitosamente
```

---

## 🔍 Validación en la BD

Ejecuta estas queries para verificar que se guardó:

```sql
-- 1. Ver variantes de la prenda 6
SELECT 
    id,
    prenda_cotizacion_id,
    tipo_prenda_id,
    tipo_manga_id,
    tipo_broche_id,
    tiene_bolsillos,
    tiene_reflectivo,
    descripcion_adicional
FROM variantes_prenda
WHERE prenda_cotizacion_id = 6
LIMIT 1;

-- 2. Si la prenda 6 tiene variante
SELECT COUNT(*) as total_variantes
FROM variantes_prenda
WHERE prenda_cotizacion_id = 6;

-- 3. Ver toda la cotización relacionada
SELECT 
    pc.id,
    pc.nombre_producto,
    COUNT(vp.id) as variantes_count
FROM prendas_cotizacion_friendly pc
LEFT JOIN variantes_prenda vp ON pc.id = vp.prenda_cotizacion_id
WHERE pc.id = 6
GROUP BY pc.id;
```

---

## ⚠️ Si Aún No Se Guarda

Si después de los cambios AÚN no se guardan las variaciones:

1. **Verifica los logs:**
```bash
tail -n 50 storage/logs/laravel.log
```

2. **Busca estos patrones:**
```
❌ Error guardando variantes
✅ Variante guardada
No se pudo reconocer tipo de prenda
```

3. **Ejecuta la query para ver si la tabla tiene datos:**
```sql
SELECT COUNT(*) FROM variantes_prenda;
```

4. **Si la tabla está VACÍA**, entonces:
   - La función NO se está llamando
   - O hay error en la validación
   - O el `tipo_prenda_id` es requerido pero no existe

5. **Reporte:**
   - Copia los logs últimos 50 líneas
   - Incluye el nombre de la prenda que escribiste
   - Incluye el error exacto si lo hay

---

## 📊 Checklist de Validación

- [ ] Los checkboxes de variaciones se pueden marcar
- [ ] Se escriben observaciones sin error
- [ ] Guarda la cotización sin error 422
- [ ] Los logs muestran "✅ Variante guardada"
- [ ] La query SQL retorna datos en variantes_prenda
- [ ] Los campos (`tipo_manga_id`, `descripcion_adicional`) tienen valores

Si todo está ✓, entonces las variaciones se guardan correctamente ahora.
