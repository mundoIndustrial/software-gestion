# 🔧 FIX: Variaciones No Se Guardan al Editar Prendas

**Fecha:** 23 de Enero 2026  
**Estado:**  RESUELTO  
**Severidad:** 🔴 CRÍTICO

---

##  Problema Identificado

Las variaciones (manga, broche, bolsillos, reflectivo) **no se estaban guardando** cuando se editaban prendas en un pedido existente. El log mostraba:

```
[modal-novedad-edicion] ⚠️ No hay variantes para enviar
```

Esto causaba que:
- Los datos de variaciones se perdían al actualizar
- El log indicaba `variantes_count: 0` aunque la prenda tuviera variantes
- Se guardaban otros datos (tallas, procesos) pero NO las variaciones

---

## 🔍 Causas Raíz

### **Causa #1: Variantes no se copiaban en edición**
**Archivo:** `public/js/componentes/prenda-form-collector.js` (línea 164-178)

Cuando se editaba una prenda existente:
- Se copiaban las telas anteriores desde `prendaAnterior.telasAgregadas`
- **PERO NO se copiaban las variantes anteriores** desde `prendaAnterior.variantes`
- Resultado: `prendaData.variantes` llegaba vacío al modal

**Línea problemática:**
```javascript
// ❌ ANTES: Solo copiaba telas, no variantes
if (prendaAnterior && prendaAnterior.telasAgregadas && ...) {
    prendaData.telasAgregadas = ...;
    // ⚠️ NO COPIABA prendaData.variantes
}
```

### **Causa #2: Validación incorrecta de variantes en modal**
**Archivo:** `public/js/componentes/modal-novedad-edicion.js` (línea 93-97)

Las variantes se estructuran como un **objeto**, no como array:
```javascript
variantes = { manga: '', obs_manga: '', tiene_bolsillos: false, ... }
```

Pero el código validaba usando `.length` (propio de arrays):
```javascript
// ❌ ANTES: .length no existe en objetos
if (this.prendaData.variantes && this.prendaData.variantes.length > 0) {
    // Nunca entra aquí porque .length es undefined
}
```

### **Causa #3: ID de prenda incorrecto en controlador**
**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php` (línea 810)

```php
// ❌ ANTES: Pasaba $id (pedido_id) en lugar de prenda_id
$dto = ActualizarPrendaCompletaDTO::fromRequest($id, $validated, $imagenesGuardadas);
```

Esto causaba que el DTO recibiera el ID del **pedido** en lugar del ID de la **prenda**, intentando actualizar la prenda con el ID del pedido.

### **Causa #4: Formato de variantes incompatible**
**Archivo:** `public/js/componentes/modal-novedad-edicion.js` (línea 101)

Frontend enviaba variantes como:
```javascript
{ manga: '', obs_manga: '', ... }  // OBJETO
```

Pero backend esperaba:
```php
[ { tipo_manga_id, tipo_broche_boton_id, manga_obs, ... } ]  // ARRAY
```

---

##  Soluciones Implementadas

### **Fix #1: Copiar variantes en edición**
**Archivo:** `public/js/componentes/prenda-form-collector.js`

```javascript
//  DESPUÉS: También copia variantes anteriores
else if (prendaEditIndex !== null && prendaEditIndex !== undefined && prendasArray[prendaEditIndex]) {
    const prendaAnterior = prendasArray[prendaEditIndex];
    
    // Copiar telas
    if (prendaAnterior && prendaAnterior.telasAgregadas && ...) {
        prendaData.telasAgregadas = ...;
    }
    
    //  NUEVO: También copiar variantes anteriores
    if (prendaAnterior && prendaAnterior.variantes && Object.keys(prendaAnterior.variantes).length > 0) {
        prendaData.variantes = prendaAnterior.variantes;
    }
}
```

### **Fix #2: Validación correcta de variantes como objeto**
**Archivo:** `public/js/componentes/modal-novedad-edicion.js`

```javascript
//  DESPUÉS: Valida tanto arrays como objetos
if (this.prendaData.variantes) {
    const tieneVariantes = Array.isArray(this.prendaData.variantes) 
        ? this.prendaData.variantes.length > 0
        : Object.keys(this.prendaData.variantes).length > 0;
        
    if (tieneVariantes) {
        const variantesArray = this.convertirVariantesAlFormatoBackend(this.prendaData.variantes);
        formData.append('variantes', JSON.stringify(variantesArray));
    }
}
```

### **Fix #3: Usar prenda_id correcto**
**Archivo:** `app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`

```php
//  DESPUÉS: Usa $validated['prenda_id'] en lugar de $id
$dto = ActualizarPrendaCompletaDTO::fromRequest($validated['prenda_id'], $validated, $imagenesGuardadas);
```

### **Fix #4: Convertir variantes objeto a array**
**Archivo:** `public/js/componentes/modal-novedad-edicion.js`

Nuevo método `convertirVariantesAlFormatoBackend()`:
```javascript
convertirVariantesAlFormatoBackend(variantes) {
    // Si ya es array, retornar tal cual
    if (Array.isArray(variantes)) {
        return variantes;
    }
    
    // Si es objeto, convertir a array con un elemento
    if (variantes && typeof variantes === 'object') {
        const varianteObject = {
            tipo_manga_id: null,
            tipo_broche_boton_id: null,
            manga_obs: variantes.obs_manga || variantes.manga || '',
            broche_boton_obs: variantes.obs_broche || variantes.broche || '',
            tiene_bolsillos: variantes.tiene_bolsillos || false,
            bolsillos_obs: variantes.obs_bolsillos || '',
            tiene_reflectivo: variantes.tiene_reflectivo || false,
            reflectivo_obs: variantes.obs_reflectivo || ''
        };
        return [varianteObject];
    }
    
    return [];
}
```

---

## 📊 Flujo Corregido

### **ANTES (❌ Problemático):**
```
1. Editar prenda existente
   ↓
2. prenda-form-collector.js: No copiar variantes
   ↓
3. prendaData.variantes = {} (vacío)
   ↓
4. modal-novedad-edicion.js: .length es undefined → No enviar
   ↓
5. Backend nunca recibe variantes
   ↓
6. Variantes NO se guardan ❌
```

### **DESPUÉS ( Correcto):**
```
1. Editar prenda existente
   ↓
2. prenda-form-collector.js: Copiar variantes de prenda anterior
   ↓
3. prendaData.variantes = { manga, obs_manga, ... }
   ↓
4. modal-novedad-edicion.js: Object.keys().length > 0 ✓
   ↓
5. Convertir objeto variantes a array de variantes
   ↓
6. Backend recibe: [{ tipo_manga_id, manga_obs, ... }]
   ↓
7. ActualizarPrendaCompletaUseCase.actualizarVariantes() → Guarda 
   ↓
8. Variantes se guardan correctamente 
```

---

## 🧪 Testing

**Pasos para verificar fix:**
1. Abrir un pedido existente
2. Hacer clic en "Editar" sobre una prenda
3. Editar algún dato (nombre, etc.)
4. Dejar las variaciones igual (no cambiar)
5. Guardar cambios
6. Verificar en BD: `SELECT * FROM prenda_pedido_variantes WHERE prenda_pedido_id = <id>`
7.  Las variaciones deben estar persistidas

**Casos de uso:**
-  Editar prenda sin cambiar variaciones
-  Editar prenda y modificar variaciones
-  Crear prenda nueva con variaciones
-  Editar prenda que no tiene variaciones

---

## 📝 Archivos Modificados

1. **`public/js/componentes/prenda-form-collector.js`** (línea 164-178)
   - Agregar copia de variantes cuando se edita

2. **`public/js/componentes/modal-novedad-edicion.js`** (línea 93-107)
   - Validación mejorada de variantes como objeto
   - Método nuevo `convertirVariantesAlFormatoBackend()`

3. **`app/Infrastructure/Http/Controllers/Asesores/PedidosProduccionController.php`** (línea 810)
   - Usar `$validated['prenda_id']` en lugar de `$id`

---

## Impacto

- **Severidad:** 🔴 CRÍTICO → 🟢 RESUELTO
- **Funcionalidad:** Edición de variaciones en prendas
- **Usuarios:** Asesores que editan prendas
- **Scope:** Sistema de Pedidos Producción

---

## 📚 Referencias

- **Use Case:** `ActualizarPrendaCompletaUseCase.php`
- **DTO:** `ActualizarPrendaCompletaDTO.php`
- **Tabla BD:** `prenda_pedido_variantes`
- **Componentes:** `prenda-form-collector.js`, `modal-novedad-edicion.js`

