# ANÁLISIS DE CAMBIOS: RAMA REFACTORIZACION vs MAIN

**Fecha**: 2026-01-23  
**Objetivo**: Entender qué cambios se implementaron en la rama `refactorizacion` que afectan a la funcionalidad actual

---

## CAMBIOS PRINCIPALES DETECTADOS

### 1. 🔴 CAMBIO CRÍTICO EN `prenda-form-collector.js`

**REMOVIDO EN REFACTORIZACION**:
```javascript
// Líneas 145-149: ELIMINADAS en refactorizacion
if (prendaAnterior && prendaAnterior.variantes && Object.keys(prendaAnterior.variantes).length > 0) {
    prendaData.variantes = prendaAnterior.variantes;
}

// Líneas 189-199: ELIMINADAS en refactorizacion  
const brocheValor = broqueInput?.value?.toLowerCase() || '';
if (brocheValor === 'broche') {
    variantes.tipo_broche_boton_id = 1;
} else if (brocheValor === 'boton') {
    variantes.tipo_broche_boton_id = 2;
} else {
    variantes.tipo_broche_boton_id = null;
}
```

**QUÉ SIGNIFICA**: 
- En `refactorizacion` NO se copia las variantes anteriores en modo edición
- En `refactorizacion` NO se mapea "broche" → ID 1, "boton" → ID 2
- Esto explica por qué en la rama ACTUAL se fijó esto pero en REFACTORIZACION NO lo tienen

---

### 2. 🔴 CAMBIO CRÍTICO EN `modal-novedad-edicion.js`

**REMOVIDO EN REFACTORIZACION**:
```javascript
// Líneas 90-104: ELIMINADAS en refactorizacion
// Código que manejaba tanto objeto como array de variantes
if (this.prendaData.variantes) {
    const tieneVariantes = Array.isArray(this.prendaData.variantes)
        ? this.prendaData.variantes.length > 0
        : Object.keys(this.prendaData.variantes).length > 0;

    if (tieneVariantes) {
        const variantesArray = this.convertirVariantesAlFormatoBackend(this.prendaData.variantes);
        formData.append('variantes', JSON.stringify(variantesArray));
    }
}

// Líneas 253-289: TODO EL MÉTODO convertirVariantesAlFormatoBackend() ELIMINADO
convertirVariantesAlFormatoBackend(variantes) { ... }
```

**CAMBIO EN REFACTORIZACION**:
```javascript
// Asume que variantes es directamente un ARRAY
if (this.prendaData.variantes && this.prendaData.variantes.length > 0) {
    formData.append('variantes', JSON.stringify(this.prendaData.variantes));
}
```

**QUÉ SIGNIFICA**:
- En `refactorizacion` esperan que `variantes` sea un ARRAY desde el inicio
- NO contemplan que sea un OBJETO (como lo devuelve `prenda-form-collector.js`)
- El método que convertía objeto → array fue ELIMINADO completamente

---

### 3. 🔴 CAMBIO CRÍTICO EN `PedidosProduccionController.php`

**CAMBIO EN REFACTORIZACION** (línea 808):
```php
// ANTES (main - INCORRECTO):
$dto = ActualizarPrendaCompletaDTO::fromRequest($validated['prenda_id'], $validated, $imagenesGuardadas);

// DESPUÉS (refactorizacion - CORRECTO):
$dto = ActualizarPrendaCompletaDTO::fromRequest($id, $validated, $imagenesGuardadas);
```

**QUÉ SIGNIFICA**:
- En `refactorizacion` REVERTIMOS el fix que nosotros hicimos
- Vuelven a usar `$id` (que es `pedido_id`) en lugar de `$validated['prenda_id']`
- **ESTO ES UN RETROCESO** - nuestro fix en main es correcto

---

## ANÁLISIS: ¿QUÉ ESTÁ PASANDO EN REFACTORIZACION?

Mirando los cambios globales (76 archivos, 3222 inserciones, 2651 eliminaciones), la rama `refactorizacion` parece estar:

1. **SIMPLIFICANDO** el código eliminando:
   - Métodos de conversión de formato
   - Lógica de manejo de variantes como objeto
   - Mapeo manual de IDs

2. **ASUMIENDO** un nuevo formato de datos:
   - Las variantes DEBEN ser array desde el inicio
   - No hay conversión objeto → array
   - Formato más rígido

3. **AÑADIENDO** muchas migraciones de BD:
   - 30+ migraciones nuevas
   - Reestructuración de tablas (prenda_variantes, prenda_fotos, etc.)
   - Nuevas tablas: tipos_manga, tipos_broche_boton

---

## POSIBLES EXPLICACIONES

### Teoría 1: Frontend Refactorizado
La rama `refactorizacion` está esperando que el frontend cambie para devolver variantes como ARRAY desde el inicio, NO como objeto.

**Impacto**: Nuestros fixes en `main` NO serían compatibles con `refactorizacion`

### Teoría 2: Backend Refactorizado  
Hay cambios en el backend que manejan variantes de forma diferente.

**Verificar**: Ver qué cambió en `ActualizarPrendaCompletaDTO` y `ActualizarPrendaCompletaUseCase`

---

## RECOMENDACIÓN

Necesito ver:
1. ¿Qué cambió exactamente en `ActualizarPrendaCompletaDTO.php`?
2. ¿Qué cambió en `ActualizarPrendaCompletaUseCase.php`?
3. ¿Las migraciones en `refactorizacion` afectan la estructura que usamos ahora?

**Antes de mergear** `refactorizacion` a `main`, debemos:
1. Verificar si los cambios son compatibles
2. Re-aplicar nuestros fixes (tipo_broche_boton_id, variantes en edición, etc.)
3. Asegurar que TALLAS, FOTOS, y TELAS se guardan correctamente

---

## ESTADO DE MAIN vs REFACTORIZACION

| Aspecto | MAIN (Actual) | REFACTORIZACION |
|---------|---------|-----------------|
| Variantes | Objeto + Array | Solo Array |
| Conversión | Con método convertirVariantesAlFormatoBackend() | Sin método |
| tipo_broche_boton_id | Se mapea (broche→1, boton→2) | No se mapea |
| Variantes en edición | Se copian prendaAnterior.variantes | NO se copian |
| Controller prenda_id | Usa $validated['prenda_id'] | Usa $id ❌ |
| Migraciones | Actuales | Nuevas (30+) |

