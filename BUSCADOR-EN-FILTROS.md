# ✅ BUSCADOR EN FILTROS - VERSIÓN 3.1 COMPLETADA

## 🎯 Cambio Agregado

Se agregó un **buscador dentro de cada modal** para filtrar los checkboxes cuando hay muchos valores.

## 🔍 Cómo Funciona

### Activación Automática
- El buscador aparece **automáticamente** cuando hay **más de 5 valores**
- Si hay 5 o menos valores, no aparece el buscador

### Búsqueda en Tiempo Real
- Mientras escribes, los checkboxes se filtran automáticamente
- La búsqueda es **case-insensitive** (no importa mayúsculas/minúsculas)
- Busca por **coincidencia parcial** (contiene)

## 📊 Ejemplo de Uso

### Caso 1: Modal con pocos valores (sin buscador)
```
┌─────────────────────────────────┐
│ 🏷️ Filtrar por Tipo         [X] │
├─────────────────────────────────┤
│ Selecciona los tipos            │
│ ☐ Prenda                        │
│ ☐ Logo                          │
│ ☐ Prenda/Bordado                │
└─────────────────────────────────┘
```
(Sin buscador porque hay 3 valores)

### Caso 2: Modal con muchos valores (con buscador)
```
┌─────────────────────────────────┐
│ 👤 Filtrar por Cliente      [X] │
├─────────────────────────────────┤
│ Selecciona los clientes         │
│ 🔍 Buscar...                    │  ← Buscador
│ ☐ Empresa A                     │
│ ☐ Empresa B                     │
│ ☐ Empresa XYZ                   │
│ ☐ Otro Cliente 1                │
│ ☐ Otro Cliente 2                │
│ ☐ Otro Cliente 3                │
└─────────────────────────────────┘
```
(Con buscador porque hay 6 valores)

### Caso 3: Usando el buscador
1. Usuario escribe "Empresa" en el buscador
2. Se muestran solo los checkboxes que contienen "Empresa":
   - ☐ Empresa A
   - ☐ Empresa B
   - ☐ Empresa XYZ
3. Se ocultan los que no coinciden:
   - ☐ Otro Cliente 1 (oculto)
   - ☐ Otro Cliente 2 (oculto)
   - ☐ Otro Cliente 3 (oculto)

## 🔧 Cambios Técnicos

### JavaScript (`filtros-embudo.js`)

**Nuevo método**: `agregarBuscador(columna)`
```javascript
agregarBuscador(columna) {
    // Crea el input de búsqueda
    // Agrega evento keyup para filtrar checkboxes
    // Inserta el buscador en el modal
}
```

**Método actualizado**: `poblarCheckboxes(columna, valores)`
```javascript
poblarCheckboxes(columna, valores) {
    // ... crear checkboxes ...
    
    // Agregar buscador si hay más de 5 valores
    if (valores.length > 5) {
        this.agregarBuscador(columna);
    }
}
```

**Atributo agregado**: `data-valor` en cada checkbox
```html
<div class="filter-checkbox" data-valor="empresa a">
    <input type="checkbox" value="Empresa A">
    <label>Empresa A</label>
</div>
```

### CSS (`filtros-embudo.css`)

**Nuevas clases**:
- `.filter-search-box` - Contenedor del buscador
- `.filter-search-input` - Input de búsqueda

**Actualización**:
- `.filter-checkbox-group` - Ahora tiene scroll (max-height: 300px)

## 📋 Características del Buscador

✅ **Aparece automáticamente** cuando hay > 5 valores
✅ **Búsqueda en tiempo real** (mientras escribes)
✅ **Case-insensitive** (no importa mayúsculas/minúsculas)
✅ **Coincidencia parcial** (busca "emp" y encuentra "Empresa")
✅ **Scroll en checkboxes** (max-height: 300px)
✅ **Placeholder descriptivo** (🔍 Buscar...)
✅ **Estilos consistentes** con el resto del modal

## 🎨 Diseño Visual

### Input de Búsqueda
```
┌─────────────────────────────────┐
│ 🔍 Buscar...                    │  ← Placeholder con icono
└─────────────────────────────────┘
```

**Estados**:
- **Normal**: Fondo gris claro, borde gris
- **Focus**: Fondo blanco, borde azul, sombra azul
- **Escribiendo**: Muestra solo checkboxes que coinciden

### Scroll en Checkboxes
- Altura máxima: 300px
- Si hay más de ~10 checkboxes, aparece scroll
- Padding derecho: 4px (para que no tape el scroll)

## 🚀 Cómo Funciona

### Paso 1: Abrir Modal
```javascript
abrirFiltro('cliente')
```

### Paso 2: Buscador Aparece
- Si hay > 5 valores, aparece automáticamente
- Si hay ≤ 5 valores, no aparece

### Paso 3: Escribir en Buscador
```
Usuario escribe: "emp"
↓
Sistema filtra checkboxes
↓
Muestra solo los que contienen "emp" (case-insensitive)
```

### Paso 4: Seleccionar Checkboxes
- Usuario marca los checkboxes visibles
- Puede seguir escribiendo para filtrar más

### Paso 5: Aplicar
```javascript
aplicarFiltroColumna('cliente')
```

## 📊 Lógica de Filtrado

```javascript
// Búsqueda
const termino = "empresa";  // Lo que escribe el usuario

// Para cada checkbox
const valor = "Empresa A";  // El valor del checkbox
const valorLower = "empresa a";  // Convertido a minúsculas

// Comparación
if (valorLower.includes(termino)) {
    // Mostrar checkbox
    checkbox.style.display = '';
} else {
    // Ocultar checkbox
    checkbox.style.display = 'none';
}
```

## 🧪 Testing

### Verificar que Funciona

1. Abre la página de cotizaciones
2. Haz clic en embudo de "Cliente" (que tiene muchos valores)
3. Verifica que aparezca el buscador
4. Escribe algo (ej: "empresa")
5. ✅ Los checkboxes se filtran automáticamente
6. Marca algunos checkboxes
7. Haz clic en "Aplicar"
8. ✅ Tabla se filtra correctamente

### Casos de Prueba

| Caso | Entrada | Esperado |
|------|---------|----------|
| Búsqueda exacta | "Empresa A" | Muestra "Empresa A" |
| Búsqueda parcial | "emp" | Muestra "Empresa A", "Empresa B", etc. |
| Búsqueda mayúsculas | "EMPRESA" | Muestra "Empresa A", "Empresa B", etc. |
| Búsqueda vacía | "" | Muestra todos los checkboxes |
| Búsqueda sin resultados | "xyz123" | No muestra ningún checkbox |

## 🐛 Troubleshooting

### Problema: El buscador no aparece
**Solución**:
- Verifica que haya más de 5 valores
- Abre DevTools y busca `.filter-search-box` en el HTML

### Problema: El buscador no filtra
**Solución**:
- Verifica que el atributo `data-valor` esté en los checkboxes
- Abre DevTools y revisa la consola

### Problema: El scroll no funciona
**Solución**:
- Verifica que `.filter-checkbox-group` tenga `max-height: 300px`
- Verifica que haya más de ~10 checkboxes

## 📈 Mejoras Futuras

- [ ] Agregar contador de resultados (ej: "3 de 10")
- [ ] Agregar "Seleccionar todos los filtrados"
- [ ] Agregar "Limpiar búsqueda" (botón X)
- [ ] Agregar búsqueda por expresión regular
- [ ] Agregar historial de búsquedas

## 📍 Ubicación de Archivos

- **JavaScript**: `public/js/asesores/cotizaciones/filtros-embudo.js`
  - Método `agregarBuscador()`
  - Método `poblarCheckboxes()` (actualizado)

- **CSS**: `public/css/cotizaciones/filtros-embudo.css`
  - Clases `.filter-search-box` y `.filter-search-input`
  - Actualización de `.filter-checkbox-group`

## ✨ Ventajas

✅ **Fácil de usar**: Aparece automáticamente
✅ **Rápido**: Búsqueda en tiempo real
✅ **Flexible**: Busca por coincidencia parcial
✅ **Responsive**: Se adapta a cualquier tamaño
✅ **Accesible**: Placeholder descriptivo

---

**Estado**: ✅ **COMPLETADO**

**Versión**: 3.1 (Con Buscador)

**Fecha**: Diciembre 2025

**Cambios desde v3.0**:
- ✅ Buscador dentro de modales
- ✅ Filtrado automático de checkboxes
- ✅ Scroll en lista de checkboxes
- ✅ Búsqueda case-insensitive
