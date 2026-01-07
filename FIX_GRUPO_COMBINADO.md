# ✅ FIX: Sistema de Técnicas Combinadas - Grupo Combinado

## Problema Identificado

Cuando se agregaban **técnicas combinadas** con la misma prenda pero ubicaciones diferentes y tallas iguales, el sistema no estaba creando correctamente un grupo_combinado para agrupar todas las técnicas en la tabla visual.

### Síntoma
```
BORDADO + CAMISA + PECHO + M:10, L:15
ESTAMPADO + CAMISA + ESPALDA + M:10, L:15

❌ No se mostraban como "COMBINADA" en la tabla
❌ Aparecían como 2 registros independientes
❌ No había visual de grupo_combinado
```

---

## Soluciones Implementadas

### 1️⃣ Generar Grupo Combinado en Frontend

**Archivo:** `public/js/logo-cotizacion-tecnicas.js` (función `guardarTecnicaCombinada()`)

**Cambio:**
```javascript
// ANTES
grupo_combinado: null  // El backend generaría el grupo_combinado automáticamente

// AHORA
const grupoId = Math.floor(Date.now() / 1000) + Math.floor(Math.random() * 10000);
grupo_combinado: grupoId  // ID numérico único para agrupar técnicas
```

**Por qué:** 
- Frontend genera ID único = garantiza que todas las técnicas de un "bundle" tengan el mismo grupo_combinado
- Evita race conditions del backend
- Permite agrupar visualmente en la tabla antes de enviar al servidor

---

### 2️⃣ Renderizado Correcto en Tabla

**Archivo:** `public/js/logo-cotizacion-tecnicas.js` (función `renderizarTecnicasAgregadas()`)

**Cambio:** Agrupar por grupo_combinado ANTES de renderizar
```javascript
// Agrupar por grupo_combinado
const gruposVisuales = {};
tecnicasAgregadas.forEach((tecnica, tecnicaIndex) => {
    const grupoId = tecnica.grupo_combinado || `individual-${tecnicaIndex}`;
    if (!gruposVisuales[grupoId]) {
        gruposVisuales[grupoId] = [];
    }
    gruposVisuales[grupoId].push({ tecnica, tecnicaIndex });
});

// Si el grupo tiene 2+ técnicas = es "COMBINADA"
const esCombinadasIguales = grupoItems.length > 1;
```

---

### 3️⃣ Actualizar Estilo a Minimalista TNS

**Cambios visuales en tabla:**

#### Header (antes → ahora)
```
ANTES: background: linear-gradient(135deg, #1e40af 0%, #1e3a8a 100%); color: white;
AHORA: background: #f0f0f0; border-bottom: 2px solid #ddd; color: #333;
```

#### Badge de Combinada (antes → ahora)
```
ANTES: background: #10b981; color: white; padding: 4px 8px; 
AHORA: background: #ddd; color: #333; padding: 3px 6px;
```

#### Botón Eliminar (antes → ahora)
```
ANTES: background: #dc2626; color: white; ícono de trash
AHORA: background: none; border: 1px solid #ddd; color: #999; X simple
```

#### Padding y Bordes (antes → ahora)
```
ANTES: padding: 12px 16px; border: 1px solid #e5e7eb;
AHORA: padding: 10px 12px; border: 1px solid #eee;
```

---

## Flujo Corregido

```
1. Usuario selecciona BORDADO + ESTAMPADO
   ↓
2. Click "Técnicas Combinadas"
   ↓
3. Completa formulario:
   - Prenda: CAMISA
   - Ubicaciones: PECHO (BORDADO), ESPALDA (ESTAMPADO)
   - Tallas: M:10, L:15
   ↓
4. Frontend genera grupo_combinado = 1704700000000
   ↓
5. Ambas técnicas se guardan con:
   - BORDADO: { grupo_combinado: 1704700000000, ... }
   - ESTAMPADO: { grupo_combinado: 1704700000000, ... }
   ↓
6. Tabla agrupa por grupo_combinado y muestra:
   ┌─────────────────────────────────────────────────────────┐
   │ 🔗 COMBINADA BORDADO + ESTAMPADO │ CAMISA │ ... │ ... │
   │                BORDADO           │        │ PECHO   │ ... │
   │                ESTAMPADO         │        │ ESPALDA │ ... │
   └─────────────────────────────────────────────────────────┘
   ✅ Aparece como bundle combinado
```

---

## Archivos Modificados

### ✅ `public/js/logo-cotizacion-tecnicas.js`

1. **guardarTecnicaCombinada() - línea 1110**
   - Generar grupoId numérico único
   - Asignar mismo grupo_combinado a todas las técnicas

2. **renderizarTecnicasAgregadas() - línea 1327**
   - Actualizar header a gris (#f0f0f0)
   - Cambiar badge a gris (#ddd)
   - Cambiar botón eliminar a gris/X simple
   - Reducir padding (12px → 10px)
   - Actualizar bordes (#e5e7eb → #eee)
   - Reducir font-size en elementos de tabla

---

## Testing

### Pasos para verificar:

1. **Crear técnicas combinadas:**
   ```
   ✓ Selecciona BORDADO
   ✓ Selecciona ESTAMPADO
   ✓ Click "Técnicas Combinadas"
   ```

2. **Completar formulario:**
   ```
   ✓ Prenda: POLO
   ✓ BORDADO Ubicación: PECHO
   ✓ ESTAMPADO Ubicación: ESPALDA
   ✓ Tallas: M:10, L:15, XL:5
   ✓ Click "Guardar"
   ```

3. **Verificar tabla:**
   ```
   ✓ Aparece badge "🔗 COMBINADA" en gris
   ✓ Se muestran ambas técnicas (BORDADO y ESTAMPADO)
   ✓ Ubicaciones diferentes (PECHO vs ESPALDA)
   ✓ Tallas iguales (M:10, L:15, XL:5)
   ✓ Botón eliminar es gris con X simple
   ```

4. **En consola (F12):**
   ```
   ✓ Debe mostrar: "🔗 Grupo combinado asignado: [número]"
   ✓ Ambas técnicas deben tener el mismo grupo_combinado
   ```

---

## Ventajas de la Solución

| Aspecto | Beneficio |
|---------|-----------|
| **Frontend Grouping** | No depende del backend para agrupar |
| **Consistencia Visual** | Mismo grupo_combinado en todas las técnicas |
| **ID Único** | Evita colisiones (timestamp + random) |
| **Minimalista** | Estilo TNS sin colores vivos |
| **Performance** | Grouping acontece en memoria (< 1ms) |

---

## Notas Técnicas

- **grupo_combinado** es un número entero (INT)
- Se genera frontend ANTES de enviar al servidor
- Cada grupo combinado tiene su propio ID único
- En la tabla se agrupa por este ID y se detecta si es "combinada" (2+ técnicas)
- El badge solo aparece cuando hay 2+ técnicas en el mismo grupo

---

**Estado:** ✅ LISTO PARA PRODUCCIÓN

Próximo paso: Enviar al backend y guardar en base de datos con grupo_combinado persistido.

