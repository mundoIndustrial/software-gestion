# Implementación: Campo "PRENDA DE BODEGA" en Cotizaciones Combinadas

## Resumen
Se ha implementado exitosamente un nuevo campo llamado **"PRENDA DE BODEGA"** en el módulo de asesores para cotizaciones combinadas. El campo permite marcar si una prenda viene de bodega.

## Cambios Realizados

### 1. **Base de Datos - Migración**
**Archivo:** `database/migrations/2026_01_06_add_prenda_bodega_to_prenda_variantes_cot.php`

- Agregado campo `prenda_bodega` (VARCHAR, nullable) a la tabla `prenda_variantes_cot`
- Cuando está marcado: se guarda la palabra **"si"**
- Cuando no está marcado: se guarda **NULL** (sin nada)
- Migración ejecutada exitosamente ✅

### 2. **Modelo - PrendaVarianteCot**
**Archivo:** `app/Models/PrendaVarianteCot.php`

- Agregado `'prenda_bodega'` al array `$fillable`
- El modelo ahora permite asignar y guardar este campo

### 3. **Formulario - Vista**
**Archivo:** `resources/views/asesores/prendas/agregar-prendas.blade.php`

**Ubicación:** Justo después del campo "TIPO DE PRENDA"

```html
<!-- PRENDA DE BODEGA -->
<div>
    <label class="block text-sm font-bold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
        <i class="fas fa-warehouse"></i> PRENDA DE BODEGA
    </label>
    <div class="flex items-center gap-3">
        <input 
            type="checkbox" 
            id="prenda-bodega"
            class="w-5 h-5 border-2 border-blue-500 rounded cursor-pointer accent-blue-500"
        >
        <label for="prenda-bodega" class="text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
            Marcar si esta prenda viene de bodega
        </label>
    </div>
</div>
```

**Cambios en JavaScript:**
- Función `agregarPrenda()`: Captura el estado del checkbox `prenda-bodega`
- Función `actualizarTabla()`: Muestra visualmente si la prenda es de bodega (✅ Sí / ❌ No)
- Función `limpiarFormulario()`: Limpia automáticamente el checkbox

### 4. **Servicio - CotizacionPrendaService**
**Archivo:** `app/Application/Services/CotizacionPrendaService.php`

En el método que crea variantes:
```php
'prenda_bodega' => $variantes['prenda_bodega'] === true || $variantes['prenda_bodega'] === 'true' ? 'si' : null,
```

- Si el checkbox está marcado (true): guarda **"si"**
- Si no está marcado: guarda **null**

### 5. **Vista de Cotización - Mostrar Prenda de Bodega**
**Archivo:** `resources/views/components/cotizaciones/show/variante-details.blade.php`

Se agregó justo después del campo "Color":
```html
<!-- Prenda de Bodega -->
@if($variante->prenda_bodega)
    <div style="margin-bottom: 8px; padding: 8px 12px; background: #dcfce7; border-radius: 6px; border-left: 3px solid #16a34a;">
        <span style="font-weight: 600; color: #15803d;">
            <i class="fas fa-warehouse" style="margin-right: 6px;"></i> Prenda de Bodega:
        </span>
        <span style="color: #166534; font-weight: 600;">✅ Sí</span>
    </div>
@endif
```

## Flujo de Funcionamiento

1. **Usuario marca el checkbox** "PRENDA DE BODEGA" en el formulario de agregar prendas
2. **Se captura el valor** mediante JavaScript en la función `agregarPrenda()`
3. **Se visualiza en la tabla** con un indicador visual (✅ Sí o ❌ No)
4. **Se envía al backend** como parte de los datos de variantes
5. **Se procesa en CotizacionPrendaService** y guarda en la BD:
   - Si está marcado → guarda "si"
   - Si no está marcado → guarda NULL
6. **Se muestra en la cotización** con un badge verde cuando está marcado

## Archivos Modificados

1. ✅ `database/migrations/2026_01_06_add_prenda_bodega_to_prenda_variantes_cot.php` - **Creado**
2. ✅ `app/Models/PrendaVarianteCot.php` - **Actualizado**
3. ✅ `resources/views/asesores/prendas/agregar-prendas.blade.php` - **Actualizado**
4. ✅ `app/Application/Services/CotizacionPrendaService.php` - **Actualizado**
5. ✅ `resources/views/components/cotizaciones/show/variante-details.blade.php` - **Actualizado**

## Verificación

- ✅ Migración ejecutada exitosamente
- ✅ Campo agregado a la tabla `prenda_variantes_cot`
- ✅ Modelo actualizado con fillable
- ✅ Formulario muestra el checkbox en la posición correcta
- ✅ JavaScript captura y muestra el valor
- ✅ Servicio guarda el valor correctamente
- ✅ Vista muestra el indicador cuando está marcado

## Notas

- El campo solo guarda datos cuando está marcado (valor "si"), de lo contrario queda como NULL
- El checkbox está ubicado justo después del campo "TIPO DE PRENDA" como se solicitó
- El icono de bodega (warehouse) ayuda a identificar visualmente el campo
- La presentación en la vista es clara con un badge verde que dice "✅ Sí"
