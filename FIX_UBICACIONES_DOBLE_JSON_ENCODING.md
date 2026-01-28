# 🔧 FIX: Ubicaciones con Doble JSON Encoding

**Problema Reportado:**
```
UBICACIONES
📍 ["[\"[\\\"dsfds\\\"]\""📍 "rtrtretreter"]📍 "sadasdas"]
```

Las ubicaciones se guardaban con caracteres escapados duplicados, en lugar de valores simples.

---

## 🔍 Root Cause

El problema ocurría en el **flujo de edición de procesos** en prendas:

1. **Backend recupera ubicaciones JSON-encoded:**
   ```php
   $proceso->ubicaciones // = '["pecho","espalda"]'
   ```

2. **Frontend lo decodifica para renderizar:**
   ```javascript
   JSON.parse(ubicacionesJSON) // ['pecho', 'espalda']
   ```

3. **Usuario edita ubicaciones en el modal**

4. **Al guardar, el array se envía en el PATCH request:**
   - Frontend: `JSON.stringify({ubicaciones: ['pecho']})` ✓
   - Backend recibe: `['pecho']` ✓

5. **⚠️ PERO si el array contenía strings JSON-encodados:**
   - Se convertían a: `["\"[\\...\\\""]` (double-encoded)
   - Al hacer `json_encode()` de nuevo: Triple-encoded

---

## 🛠️ Soluciones Implementadas

### 1. Frontend: proceso-editor.js

**Agregado método `_normalizarUbicaciones()`** en la clase `ProcesosEditor`:

```javascript
/**
 * Normalizar ubicaciones para evitar doble JSON encoding
 * Convierte elementos JSON-encodados de vuelta a valores simples
 */
_normalizarUbicaciones(ubicaciones) {
    return ubicaciones.map(ub => {
        // Si es string que parece JSON, parsearlo
        if (typeof ub === 'string' && (ub.startsWith('[') || ub.startsWith('{'))) {
            try {
                const parsed = JSON.parse(ub);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    return parsed[0];
                } else if (typeof parsed === 'object' && parsed.ubicacion) {
                    return parsed.ubicacion;
                }
            } catch (e) {}
        }
        
        // Si es objeto con 'ubicacion', extraer valor
        if (typeof ub === 'object' && ub !== null && ub.ubicacion) {
            return ub.ubicacion;
        }
        
        return ub;
    }).filter(u => u && u.length > 0);
}
```

**Llamado desde `obtenerCambios()`:**
```javascript
if (this.cambios.ubicaciones !== null) {
    cambiosFinales.ubicaciones = this._normalizarUbicaciones(this.cambios.ubicaciones);
}
```

### 2. Backend: PrendaPedidoEditController.php

**Agregadas tres funciones privadas para normalizar:**

```php
/**
 * normalizarUbicaciones() - Coordina la limpieza
 * extraerValorUbicacion() - Detecta tipo y extrae valor
 * parseJsonUbicacion() - Parsea JSON strings
 */
```

**Ejecutado en `actualizarProcesoEspecifico()`:**
```php
if (isset($validated['ubicaciones'])) {
    // Normalizar ubicaciones para evitar JSON doble-encodeado
    $ubicacionesNormalizadas = $this->normalizarUbicaciones($validated['ubicaciones']);
    
    $ubicacionesLimpias = array_filter($ubicacionesNormalizadas);
    $proceso->ubicaciones = json_encode($ubicacionesLimpias);
}
```

---

## ✅ Resultado Esperado

**Antes (Incorrecto):**
```json
{
    "ubicaciones": "[\"[\\\"dsfds\\\"]\"\", \"rtrtretreter\"]"
}
```

**Después (Correcto):**
```json
{
    "ubicaciones": "[\"pecho\", \"espalda\"]"
}
```

---

## 🧪 Ejemplo de Flujo Corregido

### Escenario: Editar ubicaciones de un proceso reflectivo

```javascript
// 1. Usuario edita: elimina "espalda", mantiene "pecho"
window.ubicacionesProcesoSeleccionadas = ['pecho'];

// 2. Llama a guardar cambios
procesosEditor.registrarCambioUbicaciones(['pecho']);

// 3. Al enviar PATCH
const cambios = procesosEditor.obtenerCambios();
// → cambios.ubicaciones = ['pecho'] ← NORMALIZADO

fetch('/api/prendas-pedido/3472/procesos/113', {
    method: 'PATCH',
    body: JSON.stringify(cambios)
});

// 4. Backend recibe array limpio
// → $validated['ubicaciones'] = ['pecho']

// 5. Normaliza (por si acaso)
$ubicacionesNormalizadas = $this->normalizarUbicaciones(['pecho']);
// → ['pecho'] ← Sin cambios (ya está limpio)

// 6. Codifica una sola vez
$proceso->ubicaciones = json_encode(['pecho']);
// → '["pecho"]' ✓ CORRECTO
```

---

## 📝 Archivos Modificados

1. **[proceso-editor.js](../public/js/modulos/crear-pedido/procesos/services/proceso-editor.js)**
   - Línea ~189: Agregado método `_normalizarUbicaciones()`
   - Línea ~195: Llamado en `obtenerCambios()`

2. **[PrendaPedidoEditController.php](../app/Infrastructure/Http/Controllers/API/PrendaPedidoEditController.php)**
   - Línea ~497: Llama `normalizarUbicaciones()`
   - Línea ~703: Método `normalizarUbicaciones()`
   - Línea ~720: Método `extraerValorUbicacion()`
   - Línea ~741: Método `parseJsonUbicacion()`

---

## 🔐 Defensa en Profundidad

- **Frontend:** Limpia antes de enviar
- **Backend:** Limpia al recibir (defensa extra)
- **Ambos:** Usan la misma lógica de normalización

Esto asegura que incluso si hay datos corruptos, se limpian en múltiples capas.

---

## 🚀 Testing

Para verificar que funciona:

1. Edita un proceso existente
2. Modifica sus ubicaciones
3. Guardia cambios
4. Revisa los logs: `[PROCESOS-ACTUALIZAR] Ubicaciones actualizadas`
5. Verifica en BD que `proceso.ubicaciones` es JSON válido simple:
   ```sql
   SELECT ubicaciones FROM pedidos_procesos_prenda_detalles WHERE id = 113;
   -- Resultado: ["pecho","espalda"]  ✓
   ```

