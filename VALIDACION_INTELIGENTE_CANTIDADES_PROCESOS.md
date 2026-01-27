# 🔒 VALIDACIÓN INTELIGENTE DE CANTIDADES - Tallas Procesos

**Fecha:** 27 Enero 2026  
**Estado:** ✅ IMPLEMENTADO

---

## 📋 Funcionalidad

Cuando editas las tallas de un proceso, el sistema ahora:

1. **Calcula automáticamente** cuánto está asignado en OTROS procesos
2. **Muestra desglose** de asignaciones previas
3. **Valida en tiempo real** que no se exceda el total disponible
4. **Muestra modal informativo** si intentas superar el límite

---

## 🎯 Ejemplo de Uso

### Escenario:
```
Prenda: Camiseta
├─ Talla S: 20 unidades
└─ Talla M: 20 unidades

Procesos ya creados:
├─ Reflectivo: S = 5, M = 8
└─ Bordado: S = 3, M = 0

Intentas crear: Estampado con S = 15
```

### Validación:
```
Total disponible en S: 20 unidades
Ya asignadas:
  ├─ Reflectivo: 5
  └─ Bordado: 3
  ├─ Subtotal: 8
Disponible para Estampado: 12 máximo

❌ No puedes asignar 15 (necesitas reducir 3)
```

---

## 🔍 Cómo Funciona

### 1. Modal de Edición de Tallas

```
EDITAR TALLAS - ESTAMPADO
═════════════════════════════════════════════════

☑ S [  ] 12 disponibles
   ⚠️ Ya asignadas:
   Reflectivo: 5
   Bordado: 3

☑ M [  ] 20 disponibles
   (Sin asignaciones previas)

☑ L [ ] 0 disponibles
   (No disponible en la prenda)
```

### 2. Validación al Escribir

Si intentas ingresar 15 en S:

```
⚠️  MODAL - LÍMITE EXCEDIDO
════════════════════════════════════════════════

Talla: S (DAMA)
La prenda tiene 20 unidades de esta talla

📊 Desglose de asignaciones:
   ├─ Reflectivo: 5
   ├─ Bordado: 3
   └─ Subtotal asignado: 8

Disponible para este proceso: 12

❌ No puedes asignar 15
   Necesitas reducir en 3 unidades

[Entendido]
```

---

## 💻 Código Implementado

### Nueva Función: `calcularCantidadAsignadaOtrosProcesos()`

```javascript
function calcularCantidadAsignadaOtrosProcesos(talla, generoKey, procesoActualExcluir) {
    let totalAsignado = 0;
    const procesosDetalle = [];
    
    // Recorre TODOS los procesos existentes
    Object.entries(window.procesosSeleccionados).forEach(([tipoProceso, datosProc]) => {
        // Excluye el proceso actual para no contar su propia asignación
        if (tipoProceso === procesoActualExcluir) return;
        
        // Suma las cantidades de otros procesos
        if (datosProc?.datos?.tallas) {
            const cantidad = datosProc.datos.tallas[generoKey]?.[talla] || 0;
            if (cantidad > 0) {
                totalAsignado += cantidad;
                procesosDetalle.push({
                    nombre: tipoProceso,
                    cantidad: cantidad
                });
            }
        }
    });
    
    return { totalAsignado, procesosDetalle };
}
```

### Función Mejorada: `actualizarCantidadTallaProceso()`

```javascript
window.actualizarCantidadTallaProceso = function(input) {
    const cantidad = parseInt(input.value) || 0;
    const tallasPrenda = obtenerTallasDeLaPrenda();
    const cantidadDisponibleEnPrenda = tallasPrenda[genero.toLowerCase()]?.[talla] || 0;
    
    // ✅ Calcular cuánto está disponible DESPUÉS de otros procesos
    const { totalAsignado, procesosDetalle } = 
        calcularCantidadAsignadaOtrosProcesos(talla, genero, procesoActual);
    const cantidadDisponibleParaEsteProceso = 
        cantidadDisponibleEnPrenda - totalAsignado;
    
    // ✅ Validar contra disponible restante (NO contra total de prenda)
    if (cantidad > cantidadDisponibleParaEsteProceso) {
        mostrarModalAdvertenciaLimiteExcedido(
            talla,
            genero,
            cantidadDisponibleEnPrenda,
            cantidadDisponibleParaEsteProceso,
            cantidad,
            procesosDetalle
        );
        
        // Revertir al máximo permitido
        input.value = cantidadDisponibleParaEsteProceso;
        return;
    }
    
    // ✅ Guardar en estructura independiente del proceso
    window.tallasCantidadesProceso[genero][talla] = cantidad;
};
```

---

## 📊 Cálculo de Disponibilidad

```
FÓRMULA:
════════

Disponible para este proceso = Total en prenda - (Suma de otros procesos)

EJEMPLO:
════════
Prenda S = 20 unidades

Procesos existentes:
├─ Reflectivo: S = 5
├─ Bordado: S = 3
├─ DTF: S = 2
└─ Subtotal otros = 10

Disponible para nuevo proceso = 20 - 10 = 10 máximo
```

---

## ✅ Restricciones Garantizadas

| Restricción | Implementación | Nivel |
|------------|--------------|--------|
| No superar total de prenda | Cálculo: Prenda - Otros procesos | 🔴 CRÍTICO |
| Mostrar desglose de asignaciones | Modal informativo | 🟡 IMPORTANTE |
| Permitir 0 si ya está todo asignado | Campo deshabilitado (max=0) | 🟡 IMPORTANTE |
| Excluir proceso actual de cálculo | Parámetro `procesoActualExcluir` | 🟢 NORMAL |
| Mantener integridad de prenda | Nunca tocar `tallasRelacionales` | 🔴 CRÍTICO |

---

## 🎨 Visual de la UI

### Modal Informativo (cuando se intenta exceder):

```
╔════════════════════════════════════════╗
║ ⚠️  LÍMITE EXCEDIDO                     ║
║ No hay suficientes unidades            ║
╠════════════════════════════════════════╣
║                                        ║
║ Talla: S (DAMA)                        ║
║ La prenda tiene 20 unidades            ║
║                                        ║
║ 📊 Desglose de asignaciones:           ║
║ ┌─ Reflectivo ............ 5            ║
║ ├─ Bordado .............. 3             ║
║ └─ Subtotal asignado .... 8             ║
║                                        ║
║ Disponible para este proceso: 12       ║
║                                        ║
║ ❌ No puedes asignar 15                ║
║    Necesitas reducir en 3 unidades     ║
║                                        ║
║ ┌──────────────────────────────────┐   ║
║ │          Entendido               │   ║
║ └──────────────────────────────────┘   ║
╚════════════════════════════════════════╝
```

### Campo de Edición (modo normal):

```
DAMA
═════════════════════════════════════════════════

☑ S [5] ⚠️ Ya asignadas:
         Reflectivo: 5
         Bordado: 3
         
☑ M [  ] Disponible: 20
         
☐ L [ ] (No en prenda)
```

---

## 🔄 Flujo Completo

```
1. Usuario abre "Editar tallas"
   ↓
2. Sistema calcula disponibilidad para CADA talla
   ├─ Total en prenda
   ├─ Menos: suma de otros procesos
   └─ Igual: disponible para este proceso
   ↓
3. Muestra campos con máximo preestablecido
   ↓
4. Usuario intenta ingresar cantidad
   ↓
5. Validación en tiempo real:
   ├─ ¿Cantidad > Disponible?
   │  ├─ SÍ → Mostrar modal + Revertir a máximo
   │  └─ NO → Guardar en tallasCantidadesProceso
   ↓
6. Usuario guarda proceso
   ├─ Tallas se copian a procesosSeleccionados[tipo].datos.tallas
   ├─ Prenda permanece intacta
   └─ Modal se cierra
   ↓
7. Si edita de nuevo:
   ├─ Se cargan datos del proceso
   ├─ Se recalcula disponibilidad con NUEVO estado
   └─ Vuelve a paso 3
```

---

## 🧪 Casos de Prueba

### Test 1: Asignar dentro del límite ✅
```
Prenda S = 20
Reflectivo S = 5
Bordado intenta: S = 10

Resultado: Acepta (5 + 10 = 15 < 20)
```

### Test 2: Asignar exactamente lo restante ✅
```
Prenda S = 20
Reflectivo S = 5
Bordado intenta: S = 15

Resultado: Acepta (5 + 15 = 20 = 20)
```

### Test 3: Intentar superar ❌ → Modal
```
Prenda S = 20
Reflectivo S = 5
Bordado intenta: S = 20

Resultado:
├─ Muestra modal
├─ Indica: Disponible = 15
├─ Revertir a 15
└─ Usuario ve advertencia
```

### Test 4: Editar proceso existente ✅
```
Prenda S = 20
Reflectivo S = 5
Abro a editar Reflectivo

Resultado:
├─ Carga S = 5
├─ Calcula disponible = 20 - 0 (otros) = 20
├─ Permite cambiar a cualquier valor < 20
└─ Sin afectar prenda
```

### Test 5: Reducir cantidad ✅
```
Prenda S = 20
Reflectivo S = 5
Bordado S = 10
Estampado abre modal: max = 5

Resultado:
├─ Usuario reduce Reflectivo a 2
├─ Guarda cambios
├─ Estampado ahora puede tener hasta 8
└─ Sistema recalcula dinámicamente
```

---

## 🔐 Garantías de Seguridad

| Garantía | Cómo se implementa |
|----------|------------------|
| **No sobrescribir prenda** | Nunca modificar `tallasRelacionales` desde procesos |
| **Cálculo dinámico** | Se recalcula cada vez que se abre modal |
| **Transparencia** | Modal muestra desglose completo de asignaciones |
| **Reversibilidad** | Si intenta exceder, revertir automáticamente |
| **Consistencia** | Mismo límite en UI y validación backend (futura) |

---

## 📝 Notas Importantes

1. **Modal SOLO se muestra cuando intenta exceder**
   - No aparece si está dentro del límite
   - Auto-cierra cuando hace clic o fuera del modal

2. **Cálculo EXCLUYE el proceso actual**
   - Permite editar proceso sin contar su asignación actual
   - Recalcula considerando nuevos procesos agregados

3. **Disponible actualiza dinámicamente**
   - Si agrega nuevo proceso, el disponible de otros se reduce
   - Si edita cantidad de un proceso, otros ven diferente disponible

4. **Información visual**
   - Campo `max` HTML previene input > máximo (UX)
   - Modal JS valida logicalmente (seguridad)
   - Estilos rojo indican advertencia

---

**Fin del documento**
