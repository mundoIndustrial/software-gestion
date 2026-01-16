# 🔧 FIX: Variaciones de Prendas - Problema de Valores por Defecto

**Fecha:** 16 de Enero de 2026  
**Problema Principal:** Las variaciones de prendas (manga, broche/botón, bolsillos, reflectivo) se sobrescribían con valores por defecto en lugar de mantener los valores seleccionados por el usuario.

---

## 📋 RESUMEN DEL PROBLEMA

### Síntomas
1. El usuario selecciona valores en el frontend (ej: `manga = "ret"`, `tipo_broche = "boton"`)
2. Al enviar el pedido, el backend recibe valores por defecto:
   - `"No aplica"` para manga y broche
   - `false` para bolsillos y reflectivo
3. La información del usuario se pierda completamente

### Causa Raíz
En la función `recolectarDatosPedido()` (línea ~1145-1154), se intentaba leer las variaciones desde:
```javascript
// ❌ INCORRECTO - estas propiedades NO existen
prenda.tipo_manga
prenda.obs_manga
prenda.tipo_broche
prenda.obs_broche
```

Pero las variaciones estaban guardadas en:
```javascript
// ✅ CORRECTO - estructura real
prenda.variantes = {
    tipo_manga: "ret",
    obs_manga: "reter",
    tipo_broche: "boton",
    obs_broche: "retret",
    tiene_bolsillos: true,
    obs_bolsillos: "tert",
    tiene_reflectivo: false,
    obs_reflectivo: ""
}
```

---

## ✅ SOLUCIONES IMPLEMENTADAS

### 1. FRONTEND: Captura de Variaciones (Líneas ~708-832)

**Cambio:** Agregar validación exhaustiva al capturar valores del modal

```javascript
// 🔍 VALIDACIÓN EXHAUSTIVA: MANGA
const plicaManga = document.getElementById('aplica-manga');
if (plicaManga?.checked) {
    const mangaInput = document.getElementById('manga-input');
    const tipoMangaRaw = mangaInput?.value?.trim();
    
    console.log('   - manga-input VALUE:', tipoMangaRaw);
    
    variacionesConfiguradas.tipo_manga = tipoMangaRaw || 'No aplica';
    
    console.log('   ✅ MANGA CAPTURADA:', {
        tipo: variacionesConfiguradas.tipo_manga,
        obs: variacionesConfiguradas.obs_manga
    });
}
```

**Beneficios:**
- ✅ Logs detallados de cada campo capturado
- ✅ Validación para MANGA, BOLSILLOS, BROCHE, REFLECTIVO
- ✅ Debugging fácil desde DevTools

### 2. FRONTEND: Guardar en Gestor JSON (Línea ~870)

**Cambio:** Usar `prendaNueva.variantes` en lugar de propiedades individuales

```javascript
// 🔍 VERIFICAR VARIACIONES ANTES DE GUARDAR
console.log('🔍 [JSON GESTOR] Variantes extraídas:', prendaNueva.variantes);

window.gestorDatosPedidoJSON.agregarPrenda({
    // ...
    variaciones: {
        tipo_manga: prendaNueva.variantes?.tipo_manga ?? 'No aplica',
        obs_manga: prendaNueva.variantes?.obs_manga ?? '',
        tipo_broche: prendaNueva.variantes?.tipo_broche ?? 'No aplica',
        obs_broche: prendaNueva.variantes?.obs_broche ?? '',
        tiene_bolsillos: prendaNueva.variantes?.tiene_bolsillos ?? false,
        obs_bolsillos: prendaNueva.variantes?.obs_bolsillos ?? '',
        tiene_reflectivo: prendaNueva.variantes?.tiene_reflectivo ?? false,
        obs_reflectivo: prendaNueva.variantes?.obs_reflectivo ?? ''
    }
});
```

**Cambio Crítico:** Usar operador `??` (nullish coalescing) en lugar de `||` para valores falsy

```javascript
// ❌ INCORRECTO - || sobrescribe false/0/''
tiene_bolsillos: prenda.variantes?.tiene_bolsillos || false  // siempre false si undefined

// ✅ CORRECTO - ?? solo sobrescribe null/undefined
tiene_bolsillos: prenda.variantes?.tiene_bolsillos ?? false  // preserva false si existe
```

### 3. FRONTEND: Recuperar en recolectarDatosPedido() (Línea ~1195-1250)

**Cambio Principal:** Extraer desde `prenda.variantes` correctamente

```javascript
// ✅ CONSTRUIR VARIACIONES DESDE LA FUENTE CORRECTA (prenda.variantes)
console.log(`🔍 [VARIACIONES] Procesando prenda ${prendaIndex}:`, {
    tieneVariantes: !!prenda.variantes,
    varianteKeys: Object.keys(prenda.variantes || {}),
    variantes: prenda.variantes
});

// 🔹 EXTRAER VALORES DIRECTOS DE prenda.variantes
const tipoMangaRaw = prenda.variantes?.tipo_manga ?? 'No aplica';
const obsMangaRaw = prenda.variantes?.obs_manga ?? '';
const tieneBolsillosRaw = prenda.variantes?.tiene_bolsillos ?? false;
const obsBolsillosRaw = prenda.variantes?.obs_bolsillos ?? '';
const tipoBrocheRaw = prenda.variantes?.tipo_broche ?? 'No aplica';
const obsBrocheRaw = prenda.variantes?.obs_broche ?? '';
const tieneReflectivoRaw = prenda.variantes?.tiene_reflectivo ?? false;
const obsReflectivoRaw = prenda.variantes?.obs_reflectivo ?? '';

// 🔹 VALIDAR: No permitir sobrescritura con valores por defecto
const tipoManga = tipoMangaRaw === 'No aplica' ? 'No aplica' : (tipoMangaRaw || 'No aplica');
const tieneBolsillos = tieneBolsillosRaw === true; // ✅ Validar que es exactamente true
```

**Logs Agregados:**
```javascript
console.log(`✅ [VARIACIONES DEBUG] Valores extraídos:`, {
    tipo_manga: tipoMangaRaw,
    obs_manga: obsMangaRaw,
    tipo_broche: tipoBrocheRaw,
    // ...
});

console.log(`📤 [VARIACIONES JSON] Objeto final para backend:`, variaciones);
```

### 4. FRONTEND: Validación Final Antes de Envío (Línea ~1310-1350)

**Cambio:** Logs exhaustivos para confirmar valores correctos

```javascript
// 🔍 LOG CRÍTICO: Confirmar que las variaciones NO son valores por defecto
itemsFormato.forEach((item, idx) => {
    if (item.variaciones) {
        const manga = item.variaciones.manga;
        const bolsillos = item.variaciones.bolsillos;
        // ...
        
        // Verificar que NO son todos valores por defecto
        const esDefaultManga = manga?.tipo === 'No aplica' && manga?.observacion === '';
        const esDefaultBolsillos = bolsillos?.tiene === false && bolsillos?.observacion === '';
        
        console.log(`  Ítem ${idx} (${item.prenda}):`);
        console.log(`    - Manga: tipo="${manga?.tipo}" (esDefault=${esDefaultManga})`);
        console.log(`    - Bolsillos: tiene=${bolsillos?.tiene} obs="${bolsillos?.observacion}"`);
        
        if (esDefaultManga && esDefaultBolsillos && ...) {
            console.warn(`  ⚠️  ADVERTENCIA: Ítem ${idx} tiene TODAS las variaciones por defecto`);
        }
    }
});
```

### 5. BACKEND: Actualizar Referencias a tipo_broche_id (PedidoPrendaService.php)

**Cambio:** Reemplazar `tipo_broche_id` con `tipo_broche_boton_id` en 3 ubicaciones

```php
// Línea 156: Búsqueda/creación de broche
if (!empty($prendaData['broche']) && empty($prendaData['tipo_broche_boton_id'])) {
    $broche = $this->colorGeneroService->obtenerOCrearBroche($prendaData['broche']);
    if ($broche) {
        $prendaData['tipo_broche_boton_id'] = $broche->id; // ✅ CAMBIO
    }
}

// Línea 120: Log de entrada
'tipo_broche_boton_id' => $prendaData['tipo_broche_boton_id'] ?? null,

// Línea 189: Log de verificación
'tipo_broche_boton_id' => $prendaData['tipo_broche_boton_id'] ?? null,

// Línea 272: Log de guardado
'tipo_broche_boton_id_guardado' => $prenda->tipo_broche_boton_id,
```

---

## 🔍 CÓMO DEBUGGEAR EN EL NAVEGADOR

### Paso 1: Abrir DevTools
```
F12 (Windows/Linux) o Cmd+Option+I (Mac)
```

### Paso 2: Ir a Console
Busca estos logs en orden:

#### A. CAPTURA DE VARIACIONES (Al crear prenda)
```
🔍 [VARIACIONES CAPTURA] ===== MANGA =====
   - aplica-manga checkbox encontrado: true
   - manga-input VALUE: "ret"
   ✅ MANGA CAPTURADA: {tipo: "ret", obs: "reter"}
```

**Qué buscar:**
- ✅ Si dice `"encontrado: true"` = checkbox está marcado
- ✅ Si dice `VALUE: "ret"` = el input tiene el valor correcto
- ❌ Si dice `VALUE: ""` o `VALUE: undefined` = problema en el input

#### B. GUARDADO EN GESTOR JSON
```
🔍 [JSON GESTOR] Variantes extraídas:
{
    tipo_manga: "ret",
    obs_manga: "reter",
    tipo_broche: "boton",
    ...
}
```

**Qué buscar:**
- ✅ `tipo_manga: "ret"` (NO `"No aplica"`)
- ✅ `tipo_broche: "boton"` (NO `"No aplica"`)
- ✅ `tiene_bolsillos: true` (NO `false`)

#### C. RECUPERACIÓN EN recolectarDatosPedido()
```
🔍 [VARIACIONES] Procesando prenda 0:
{
    tieneVariantes: true,
    varianteKeys: ["tipo_manga", "obs_manga", ...],
    variantes: {...}
}

✅ [VARIACIONES DEBUG] Valores extraídos:
{
    tipo_manga: "ret",
    obs_manga: "reter",
    ...
}

📤 [VARIACIONES JSON] Objeto final para backend:
{
    manga: {tipo: "ret", observacion: "reter"},
    bolsillos: {tiene: true, observacion: "tert"},
    broche: {tipo: "boton", observacion: "retret"},
    reflectivo: {tiene: false, observacion: ""}
}
```

**Qué buscar:**
- ✅ `variantes: {...}` (objeto con datos reales)
- ✅ `manga: {tipo: "ret", ...}` (NO `"No aplica"`)
- ✅ `bolsillos: {tiene: true, ...}` (NO `false`)

#### D. VALIDACIÓN FINAL (Antes de envío)
```
🔍 [VARIACIONES - ANTES DE ENVIAR] VALIDACIÓN EXHAUSTIVA:
  Ítem 0 (Nombre Prenda):
    - Manga: tipo="ret" (esDefault=false)
    - Bolsillos: tiene=true obs="tert" (esDefault=false)
    - Broche: tipo="boton" (esDefault=false)
    - Reflectivo: tiene=false obs="" (esDefault=true)

✅ [VARIACIONES] Validación exitosa: contienen valores del usuario
```

**Qué buscar:**
- ✅ `esDefault=false` (significa que tiene valores reales)
- ✅ `Validación exitosa` (confirmación final)
- ❌ Si ve `Validación exitosa` pero todos son `esDefault=true` = advertencia normal (sin variaciones)

### Paso 3: Revisar Red (Network)
1. En DevTools → Tab "Network"
2. Crear pedido
3. Buscar POST a `/api/pedidos` o similar
4. Click en el request → Tab "Payload"
5. Buscar la sección `items[0].variaciones`:

```json
{
  "items": [
    {
      "variaciones": {
        "manga": {"tipo": "ret", "observacion": "reter"},
        "bolsillos": {"tiene": true, "observacion": "tert"},
        "broche": {"tipo": "boton", "observacion": "retret"},
        "reflectivo": {"tiene": false, "observacion": ""}
      }
    }
  ]
}
```

**Qué verificar:**
- ✅ `"tipo": "ret"` (NO `"No aplica"`)
- ✅ `"tiene": true` (NO `false`)
- ✅ `"observacion": "tert"` (NO vacío si el usuario escribió algo)

---

## 📊 FLUJO COMPLETO DE DATOS

```
┌─────────────────────────────────────────┐
│  USUARIO SELECCIONA EN MODAL             │
│  - manga: "ret" + obs: "reter"          │
│  - bolsillos: ✓ + obs: "tert"           │
│  - broche: "boton" + obs: "retret"      │
│  - reflectivo: ☐ (no seleccionado)      │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  CAPTURA (líneas ~750-830)              │
│  → variacionesConfiguradas = {          │
│      tipo_manga: "ret",                │
│      obs_manga: "reter",               │
│      tipo_broche: "boton",             │
│      obs_broche: "retret",             │
│      tiene_bolsillos: true,            │
│      obs_bolsillos: "tert",            │
│      tiene_reflectivo: false,          │
│      obs_reflectivo: ""                │
│    }                                   │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  GUARDAR EN GESTOR (líneas ~870-950)    │
│  → prendaNueva.variantes = {...}       │
│  → Enviar a gestorDatosPedidoJSON      │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  RECUPERAR EN recolectarDatosPedido()   │
│  (líneas ~1195-1250)                    │
│  → Leer desde prenda.variantes          │
│  → Usar operador ??                    │
│  → Construir objeto variaciones        │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  VALIDACIÓN FINAL (líneas ~1310-1360)   │
│  → Verificar que NO son valores default│
│  → Confirmar en console                 │
│  → Enviar al backend                    │
└─────────────────────────────────────────┘
                    ↓
┌─────────────────────────────────────────┐
│  BACKEND (PedidoPrendaService.php)      │
│  → Recibe variaciones JSON              │
│  → Extrae valores                       │
│  → Guarda con tipo_broche_boton_id     │
└─────────────────────────────────────────┘
```

---

## 🧪 CASOS DE PRUEBA

### Caso 1: Manga Completa
```
Entrada: {
    "aplica-manga": ✓ (checked)
    "manga-input": "ret"
    "manga-obs": "reter"
}

Esperado:
    tipo_manga: "ret"
    obs_manga: "reter"

Log esperado en console:
    ✓ [MANGA CAPTURADA] {tipo: "ret", obs: "reter"}
```

### Caso 2: Bolsillos con Observación
```
Entrada: {
    "aplica-bolsillos": ✓ (checked)
    "bolsillos-obs": "tert"
}

Esperado:
    tiene_bolsillos: true
    obs_bolsillos: "tert"

Log esperado:
    ✓ [BOLSILLOS CAPTURADOS] {tiene: true, obs: "tert"}
```

### Caso 3: Broche/Botón sin Observación
```
Entrada: {
    "aplica-broche": ✓ (checked)
    "broche-input": "boton"
    "broche-obs": ""
}

Esperado:
    tipo_broche: "boton"
    obs_broche: ""

Log esperado:
    ✓ [BROCHE CAPTURADO] {tipo: "boton", obs: ""}
```

### Caso 4: Reflectivo No Seleccionado
```
Entrada: {
    "aplica-reflectivo": ☐ (unchecked)
}

Esperado:
    tiene_reflectivo: false
    obs_reflectivo: ""

Log esperado:
    ⚠️ Reflectivo NO seleccionado (checkbox desmarcado)
```

---

## 📝 ARCHIVOS MODIFICADOS

1. **c:\Users\Usuario\Documents\mundoindustrial\public\js\modulos\crear-pedido\procesos\gestion-items-pedido.js**
   - Líneas ~750-832: Validación exhaustiva de captura
   - Líneas ~870-950: Guardar en gestor JSON
   - Líneas ~1195-1250: Recuperar y validar variaciones
   - Líneas ~1310-1360: Validación final antes de envío

2. **c:\Users\Usuario\Documents\mundoindustrial\app\Application\Services\PedidoPrendaService.php**
   - Línea 120: Log entrada
   - Línea 156: Búsqueda/creación broche → `tipo_broche_boton_id`
   - Línea 189: Log verificación
   - Línea 272: Log guardado

---

## ✨ RESULTADO ESPERADO

Después de estos cambios:

✅ Las variaciones seleccionadas por el usuario se preservan  
✅ No hay valores por defecto sobrescribiendo datos reales  
✅ Los logs permiten debugging rápido en DevTools  
✅ El backend recibe variaciones correctas con `tipo_broche_boton_id`  
✅ Las observaciones de variaciones se guardan correctamente  

---

## 📞 SOPORTE

Si los logs muestran `⚠️` pero esperas `✅`:

1. Verifica que los checkboxes están marcados en el modal
2. Verifica que los inputs tienen valores (no vacíos)
3. Revisa la red (Network tab) para ver qué se envía al backend
4. Busca errores en Console (en rojo)
5. Abre esta guía en la línea "Paso 2: Ir a Console"

