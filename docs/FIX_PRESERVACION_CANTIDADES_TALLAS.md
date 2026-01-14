# ✅ FIX: Preservación de Cantidades de Tallas

## 🐛 Problema Reportado

Cuando el usuario:
1. Agregaba cantidades a tallas de DAMA (ej: M=5, S=3)
2. Abría el modal para CABALLERO
3. Seleccionaba tallas de CABALLERO
4. Confirmaba

**Las cantidades de DAMA desaparecían** 🗑️

## 🔍 Causa Raíz

Cuando se regeneraban las tarjetas en `actualizarTarjetasGeneros()`, los elementos DOM se reconstruían completamente, perdiendo los valores que el usuario había ingresado en los inputs.

## ✅ Solución Implementada

### 1. Objeto Global para Almacenar Cantidades
```javascript
window.cantidadesTallas = {};
// Estructura: { "dama-S": 5, "dama-M": 10, "caballero-L": 3, ... }
```

### 2. Nueva Función: `guardarCantidadTalla()`
```javascript
window.guardarCantidadTalla = function(input) {
    const genero = input.dataset.genero;
    const talla = input.dataset.talla;
    const cantidad = parseInt(input.value) || 0;
    const key = `${genero}-${talla}`;
    
    window.cantidadesTallas[key] = cantidad;
    // Log en consola para debugging
}
```

### 3. Actualización de Inputs
El evento `onchange` en cada input ahora:
- Guarda la cantidad en `window.cantidadesTallas`
- Actualiza el total

```html
<input type="number" 
       data-genero="${genero}" 
       data-talla="${talla}" 
       min="0" 
       value="${cantidad}" 
       onchange="guardarCantidadTalla(this); actualizarTotalPrendas();"
/>
```

### 4. Restauración de Cantidades
Cuando se regenera una tarjeta en `crearTarjetaGenero()`:
```javascript
const key = `${genero}-${talla}`;
const cantidad = window.cantidadesTallas[key] || 0;  // Restaurar o usar 0
```

### 5. Limpieza Apropiada
Las cantidades se limpian SOLO en dos casos:
- **Cuando eliminas un género**: Se eliminan sus cantidades
- **Cuando confirmas la prenda**: Se limpian TODAS las cantidades

## 📊 Flujo de Datos

```
USUARIO INGRESA CANTIDAD
         ↓
guardarCantidadTalla() guarda en window.cantidadesTallas
         ↓
Usuario abre otro género
         ↓
Modal se regenera pero restaura cantidad desde window.cantidadesTallas
         ↓
LA CANTIDAD PERSISTE ✅
```

## 🎯 Logs Disponibles

En la consola verás:
```
💾 [GUARDAR CANTIDAD] dama-S: 5
📊 [GUARDAR CANTIDAD] Cantidades actuales: {"dama-S":5,"dama-M":10}
```

Cuando regenera:
```
📊 [TOTAL PRENDAS] Cantidades en UI: 15 | Estado completo: {"dama-S":5,"dama-M":10}
```

Cuando eliminas:
```
🗑️ [ELIMINAR GÉNERO] Eliminando género: dama
🧹 [ELIMINAR GÉNERO] Limpiando cantidades de: dama
📊 [ELIMINAR GÉNERO] Cantidades después: {}
```

## 🔄 Casos de Prueba

### ✅ Prueba 1: Persistencia Básica
1. Abre modal
2. Selecciona DAMA con M, L
3. Ingresa M=10, L=5
4. Abre CABALLERO
5. Selecciona XL
6. Ingresa XL=8
7. Abre DAMA nuevamente
8. **Verificar**: M=10 y L=5 siguen allí ✅

### ✅ Prueba 2: Eliminación y Re-agregación
1. Agrega DAMA con cantidades
2. Elimina DAMA
3. Vuelve a agregar DAMA con NUEVAS tallas
4. **Verificar**: Las cantidades anteriores NO reaparecen (limpieza correcta) ✅

### ✅ Prueba 3: Confirmación
1. Agrega DAMA y CABALLERO con cantidades
2. Confirma prenda (Agregar Prenda)
3. Abre modal nuevamente
4. **Verificar**: Las cantidades están limpias (0) ✅

## 📁 Archivos Modificados

- [crear-desde-cotizacion-editable.blade.php](../../resources/views/asesores/pedidos/crear-desde-cotizacion-editable.blade.php)
  - Línea ~2847: Inicialización de `window.cantidadesTallas`
  - Línea ~3088: Nueva función `guardarCantidadTalla()`
  - Línea ~3098: Actualización de `crearTarjetaGenero()`
  - Línea ~3121: Actualización de `eliminarGenero()`
  - Línea ~2750: Limpieza en `agregarPrendaNueva()`

## 💡 Ventajas

- ✅ Cantidades persistentes entre aperturas de modal
- ✅ Sin recarga necesaria
- ✅ Sin pérdida de datos
- ✅ Limpieza apropiada cuando se elimina o confirma
- ✅ Logs completos para debugging
