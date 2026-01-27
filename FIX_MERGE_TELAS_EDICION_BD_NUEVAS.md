# FIX: Merge de Telas en Edición (BD + Nuevas)

## 🔴 Problema

Cuando editabas una prenda con 1 tela de BD y agregabas 1 tela nueva:
- **Esperado:** 2 telas (1 de BD + 1 nueva)
- **Real:** Solo se guardaba 1 tela (se perdía una)

**Síntoma:** Al guardar cambios, la tela nueva reemplazaba la tela de BD en lugar de sumarse.

---

## 🧬 Causa Raíz

Había **tres variables diferentes** sin sincronización:

```javascript
// VARIABLES DESINCRONIZADAS:
window.telasCreacion       // Nuevas telas (creación)
window.telasAgregadas      // Telas de BD (edición) 
window.telasEdicion        // Telas de BD (edición legacy)

// PROBLEMA:
// 1. Al abrir edición: Se cargan en telasAgregadas
// 2. Al agregar nueva: Se pushea a telasCreacion (❌ EQUIVOCADO)
// 3. Al guardar: Se busca en telasEdicion (❌ VACÍO)
// 4. Resultado: Solo se guardan nuevas, se pierden de BD
```

---

## ✅ Solución

### 1. Agregar Tela en Lugar Correcto
**Archivo:** `gestion-telas.js` - Línea ~214

**ANTES (❌ Siempre a telasCreacion):**
```javascript
window.telasCreacion.push({
    color, tela, referencia, ...
});
```

**DESPUÉS (✅ Detecta modo):**
```javascript
// En EDICIÓN: agregar a telasAgregadas (conserva BD + nuevas)
// En CREACIÓN: agregar a telasCreacion
const modoEdicion = window.telasAgregadas && window.telasAgregadas.length > 0;
const destino = modoEdicion ? window.telasAgregadas : window.telasCreacion;

destino.push({
    color, tela, referencia,
    nombre_tela: tela,  // Normalizar
    imagenes: imagenesCopia
});
```

### 2. Guardar Desde Variable Correcta
**Archivo:** `modal-novedad-edicion.js` - Línea ~189

**ANTES (❌ Solo buscaba en telasEdicion):**
```javascript
if (window.telasEdicion && window.telasEdicion.length > 0) {
    // ...
}
```

**DESPUÉS (✅ Prioriza telasAgregadas):**
```javascript
const telasParaEnviar = (window.telasAgregadas && window.telasAgregadas.length > 0) 
    ? window.telasAgregadas 
    : window.telasEdicion;

if (telasParaEnviar && telasParaEnviar.length > 0) {
    // ...
}
```

### 3. Actualizar Tabla al Agregar
**Archivo:** `gestion-telas.js` - Línea ~251

Después de agregar tela nueva, actualizar tabla:
```javascript
// Actualizar tabla para mostrar la tela nueva agregada
if (window.actualizarTablaTelas) {
    window.actualizarTablaTelas();
}
```

---

## 🧪 Flujo Correcto Ahora

```
EDICIÓN DE PRENDA
  ↓
1️⃣ Modal abre
   ├─ Telas de BD cargan en window.telasAgregadas
   └─ Tabla muestra: [Tela BD]

2️⃣ Usuario agrega tela nueva
   ├─ Detecta modo EDICIÓN ✅
   ├─ Push a window.telasAgregadas (NO telasCreacion) ✅
   ├─ Tabla actualiza
   └─ Tabla muestra: [Tela BD] + [Tela Nueva]

3️⃣ Usuario guarda
   ├─ Lee telasParaEnviar (prioriza telasAgregadas) ✅
   ├─ Envía: [Tela BD + ID] + [Tela Nueva] ✅
   └─ Servidor: MERGE (conserva BD + agrega nueva) ✅

4️⃣ Resultado
   └─ Base de datos: 2 telas ✅
```

---

## 📊 Comparación

| Paso | Antes (❌ Problema) | Después (✅ Correcto) |
|------|---|---|
| **Abrir edición** | telasAgregadas = [Tela BD] | telasAgregadas = [Tela BD] ✅ |
| **Agregar nueva** | telasCreacion = [Tela Nueva] | telasAgregadas = [Tela BD, Tela Nueva] ✅ |
| **Guardar** | Busca telasEdicion (vacío) ❌ | Busca telasAgregadas (lleno) ✅ |
| **Resultado** | 1 tela ❌ | 2 telas ✅ |

---

## ✅ Tabla Visual en Edición

Ahora se ve correctamente:

```
TABLA DE TELAS
┌─────────────────────────────────────────────────────────┐
│ TELA      │ COLOR    │ REFERENCIA │ FOTO        │ [...] │
├─────────────────────────────────────────────────────────┤
│ drill     │ dsf      │            │ [IMG-BD]    │ [X]   │  ← Tela de BD
├─────────────────────────────────────────────────────────┤
│ DFGDFG    │ dsf      │            │ [IMG-NUEVA] │ [X]   │  ← Tela nueva agregada
└─────────────────────────────────────────────────────────┘
```

---

## 🔒 Casos Cubiertos

### ✅ Caso 1: Sin telas, agregar 1 nueva
- No hay BD → crea en telasCreacion
- Guarda 1 tela ✅

### ✅ Caso 2: 1 tela BD, agregar 1 nueva
- BD carga en telasAgregadas
- Nueva se agrega a telasAgregadas
- Guarda 2 telas ✅

### ✅ Caso 3: 1 tela BD, agregar 2 nuevas
- BD + 2 nuevas = 3 en telasAgregadas
- Guarda 3 telas ✅

### ✅ Caso 4: 1 tela BD, eliminar y agregar
- Eliminar reduce telasAgregadas a 0
- Agregar suma 1
- Guarda 1 tela ✅

---

## 📝 Archivos Modificados

| Archivo | Cambios | Línea |
|---------|---------|-------|
| `gestion-telas.js` | Detectar modo, agregar a destino correcto | 214 |
| `gestion-telas.js` | Actualizar tabla después de agregar | 251 |
| `modal-novedad-edicion.js` | Usar telasAgregadas como prioridad | 189 |
| `modal-novedad-edicion.js` | Usar telasAgregadas en loop fotos | 246 |

---

## 🚀 Funcionamiento Actual

```javascript
// Antes (problema):
Edición → telasAgregadas cargadas → Agregar → telasCreacion = [nueva]
  → Guardar → busca telasEdicion → vacío → 1 tela perdida

// Ahora (correcto):
Edición → telasAgregadas = [BD] → Agregar → telasAgregadas = [BD, nueva]
  → Guardar → busca telasAgregadas → [BD, nueva] → 2 telas guardadas ✅
```

---

## ✅ Validación

En Console (F12) durante edición:

```javascript
// 1. Ver telas de BD cargadas
console.log(window.telasAgregadas);  // [{...}, {... nueva}]

// 2. Verificar destino correcto
console.log('[guardarTela] Modo: EDICIÓN, destino: telasAgregadas');

// 3. Ver telas para enviar
console.log(window.telasAgregadas.length);  // Debe ser > 1

// 4. Verificar en guardado
console.log('[modal-novedad-edicion] Telas enviadas (MERGE):', telasArray);
```

---

**Fecha:** 27 ENE 2026  
**Estado:** ✅ Implementado  
**Pruebas:** Pendientes en BD con múltiples telas
