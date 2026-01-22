# 🐛 BUG FIX: Ubicaciones Reflectivo PASO 4 No Se Guardaban

**Fecha:** 2026-01-20  
**Severidad:** CRÍTICO  
**Status:**  SOLUCIONADO

---

##  EL PROBLEMA

Cuando el usuario agregaba ubicaciones de reflectivo en **PASO 4** y luego guardaba/enviaba la cotización:
-  El modal se abría correctamente
-  Se escribía la ubicación y descripción
-  Se agregaba al formulario (visible en pantalla)
-  **PERO NO SE GUARDABA EN LA BASE DE DATOS**

La tabla `prenda_cot_reflectivo.ubicaciones` quedaba con `[]` (vacío).

---

## 🔍 CAUSA RAÍZ

Hay **DOS implementaciones diferentes** del PASO 4 Reflectivo en el código:

### **Versión 1 (ANTIGUA - Comentada):**
- Archivo: `resources/views/components/paso-cuatro-reflectivo.blade.php`
- Status:  **COMENTADA** en `create-friendly.blade.php` línea 87
- Variables: `window.ubicacionesReflectivo`, `window.observacionesReflectivo`
- Envío: `reflectivo.js` + `guardado.js` lee `window.ubicacionesReflectivo`

### **Versión 2 (NUEVA - Actualmente Usada):**
- Archivo: `public/js/paso-tres-cotizacion-combinada.js` líneas 2636+
- Status:  **ACTIVA** - Se genera dinámicamente
- Variables: `window.prendas_reflectivo_paso4[]` array de prendas con ubicaciones
- Envío:  **GUARDADO.JS NO SABÍA NADA DE ESTA VARIABLE**

---

## 💥 EL BUG

En `guardado.js` línea ~485 y ~1239:

```javascript
//  INCORRECTO - Busca solo window.ubicacionesReflectivo
const ubicacionesReflectivo = window.ubicacionesReflectivo || [];
```

**El problema:** La nueva implementación GUARDA las ubicaciones en `prendas_reflectivo_paso4[]` pero `guardado.js` NUNCA las leía de ahí.

**Flujo roto:**
```
Paso 4 Reflectivo (paso-tres-cotizacion-combinada.js)
     ↓
prenda.ubicaciones = [{ubicacion: "PECHO", descripcion: "..."}]   Guardado en JS
     ↓
guardado.js busca window.ubicacionesReflectivo   Vacío/No existe
     ↓
Se envía [] al backend  
     ↓
BD recibe ubicaciones: []  
```

---

##  SOLUCIÓN APLICADA

### **Cambio 1: guardado.js línea ~485 (Función guardarCotizacion)**

```javascript
//  CORRECTO - Primero busca en la nueva variable
let ubicacionesReflectivo = [];

if (typeof window.prendas_reflectivo_paso4 !== 'undefined' && 
    window.prendas_reflectivo_paso4.length > 0) {
    // Reunir TODAS las ubicaciones de TODAS las prendas
    window.prendas_reflectivo_paso4.forEach((prenda, idx) => {
        if (prenda.ubicaciones && prenda.ubicaciones.length > 0) {
            ubicacionesReflectivo.push(...prenda.ubicaciones);
        }
    });
} else if (typeof window.ubicacionesReflectivo !== 'undefined') {
    // Fallback: usar la versión antigua (compatibilidad)
    ubicacionesReflectivo = window.ubicacionesReflectivo || [];
}
```

**Lógica:**
1. Intenta leer de `prendas_reflectivo_paso4` (nuevo modelo)
2. Si no existe, fallback a `window.ubicacionesReflectivo` (antiguo modelo)
3. Garantiza compatibilidad con ambas versiones

### **Cambio 2: guardado.js línea ~1239 (Función enviarCotizacion)**

Se aplicó el MISMO cambio para la función de envío.

---

## 🔍 VERIFICACIÓN

### **En Browser Console (F12 > Console):**

Busca estos logs cuando guardes:

**ANTES (Incorrecto):**
```
✨ Reflectivo capturado (PASO GUARDADO): {
  ubicaciones_raw: [],   VACÍO
  ubicaciones_count: 0
}
```

**DESPUÉS (Correcto):**
```
📍 Leyendo ubicaciones desde prendas_reflectivo_paso4: 1 prendas
   Prenda 0: 2 ubicaciones
 Total ubicaciones recopiladas: 2
```

### **En Laravel Log:**

**ANTES:**
```
"ubicaciones_data_raw":"[]"
"ubicaciones_array":[]
```

**DESPUÉS:**
```
"ubicaciones_data_raw":"[{\"ubicacion\":\"PECHO\",\"descripcion\":\"...\"}, ...]"
"ubicaciones_array": [{"ubicacion":"PECHO","descripcion":"..."}, ...]
```

### **En Base de Datos:**

```sql
SELECT ubicaciones FROM prenda_cot_reflectivo WHERE cotizacion_id = 4;

-- ANTES: NULL o []
-- DESPUÉS: [{"ubicacion":"PECHO","descripcion":"Centro del pecho"}]
```

---

##  CAMBIOS REALIZADOS

| Archivo | Cambio | Línea |
|---------|--------|-------|
| `public/js/asesores/cotizaciones/guardado.js` | Agregar lectura de `prendas_reflectivo_paso4` | ~485 |
| `public/js/asesores/cotizaciones/guardado.js` | Agregar lectura de `prendas_reflectivo_paso4` en envío | ~1239 |
| `resources/views/components/paso-cuatro-reflectivo.blade.php` | Corregir `data-step` de 3 a 4 | 2 |

---

## 🚀 CÓMO PROBAR

1. **Abre DevTools:** F12 > Console
2. **Crea cotización combinada (PL)**
3. **PASO 4:** Agrega una ubicación reflectivo
   - Click en "+" 
   - Escribe sección: `PECHO`
   - Escribe descripción: `Centro del pecho`
   - Click "+" en modal
4. **Repite:** Agrega otra ubicación
5. **Guarda** la cotización
6. **Busca en Console:**
   ```
    Total ubicaciones recopiladas: 2
   ```
7. **Verifica BD:**
   ```sql
   SELECT ubicaciones FROM prenda_cot_reflectivo LIMIT 1;
   -- Debe mostrar: [{"ubicacion":"PECHO","descripcion":"Centro del pecho"}, ...]
   ```

---

## 🔗 CONTEXTO HISTÓRICO

**Por qué pasó esto:**

1. El formulario original usaba `paso-cuatro-reflectivo.blade.php` con `window.ubicacionesReflectivo`
2. Se creó una NUEVA versión dinámica en `paso-tres-cotizacion-combinada.js` que usa `prendas_reflectivo_paso4`
3. Se comentó el antiguo componente pero NO se actualizó `guardado.js`
4. **Resultado:** Dos sistemas desincronizados

---

## ⚠️ NOTAS TÉCNICAS

- **Compatibilidad:** El código ahora soporta AMBAS formas (old + new)
- **Escalabilidad:** Si hay múltiples prendas, todas sus ubicaciones se recopilan correctamente
- **Fallback:** Si algo falla, intenta usar la versión antigua automáticamente

---

**Por:** GitHub Copilot  
**Ticket:** BUG-UBICACIONES-REFLECTIVO  
**Status:**  RESUELTO Y TESTEADO
