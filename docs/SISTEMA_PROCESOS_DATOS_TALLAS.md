#  Documentación: Sistema de Procesos con Datos Extendidos por Talla

## 🎯 Descripción General

Se ha implementado un sistema mejorado para agregar procesos a prendas (Reflectivo, Bordado, Estampado, DTF, Sublimado) que ahora permite especificar datos detallados **por cada talla** en el editor de tallas específicas.

## 🆕 Nuevas Funcionalidades

### En el Editor de Tallas Específicas, cada talla ahora tiene:

1. **📍 Ubicación(es) por Talla**
   - Input para ingresar ubicaciones específicas
   - Botón para agregar múltiples ubicaciones
   - Tags removibles de ubicaciones agregadas
   - Ej: "Frente", "Espalda", "Manga izquierda", etc.

2. **💬 Observaciones por Talla**
   - Textarea para notas específicas
   - Ej: "Color rojo fuerte", "Bordado fino", "Solo esta talla"

3. **📷 Imagen por Talla**
   - Preview de imagen con drag-drop
   - Cada talla puede tener su propia imagen de referencia
   - Botón para eliminar imagen

## 📁 Archivos Implementados

### Nuevos Archivos JavaScript

1. **`extension-editor-tallas-multiproducto.js`**
   - Reemplaza la función `abrirEditorTallasEspecificas` original
   - Renderiza campos extendidos para cada talla
   - Maneja ubicaciones, imágenes y observaciones por talla
   - Estructura de datos: `window.datosExtendidosTallasProceso`

2. **`extension-guardar-datos-tallas-extendida.js`**
   - Extiende la función `guardarTallasSeleccionadas` original
   - Guarda automáticamente los datos extendidos en `window.procesosSeleccionados`
   - Permite restaurar datos al abrir el editor nuevamente

3. **`editor-tallas-extendido.js`** (opcional, para referencia)
   - Contiene funciones auxiliares para manejo de datos

## 🔄 Flujo de Trabajo

### 1. **Seleccionar Proceso**
```
Usuario marca checkbox de proceso (Reflectivo, Bordado, etc.)
↓
Se abre modal con campos generales (ubicaciones, observaciones, imágenes generales)
```

### 2. **Editar Tallas Específicas**
```
Usuario hace click en "Editar tallas específicas"
↓
Se abre modal de tallas mejorado con tarjetas por talla
↓
Cada tarjeta contiene:
  - Checkbox de selección
  - Input de cantidad
  - Campo de ubicación(es)
  - Field de observaciones
  - Panel de imagen
```

### 3. **Guardar Datos**
```
Usuario hace click en "Guardar Tallas"
↓
Se ejecuta guardarTallasSeleccionadas() extendida
↓
Se guardan simultáneamente:
   - Tallas seleccionadas
   - Cantidades por talla
   - Ubicaciones por talla
   - Observaciones por talla
   - Imágenes por talla
↓
Se cierra el editor y se actualiza el resumen
```

## 📊 Estructura de Datos

### `window.datosExtendidosTallasProceso`
```javascript
{
    dama: {
        "M": {
            ubicaciones: ["Frente", "Espalda"],
            imagen: "data:image/png;base64...",
            observaciones: "Color rojo fuerte"
        },
        "L": {
            ubicaciones: ["Manga derecha"],
            imagen: null,
            observaciones: ""
        }
    },
    caballero: {
        "M": {
            ubicaciones: ["Bolsillo"],
            imagen: "data:image/...",
            observaciones: "Bordado fino"
        }
    },
    sobremedida: {}
}
```

### En `window.procesosSeleccionados[tipoProceso].datos`
```javascript
{
    tipo: "estampado",
    ubicaciones: [...], // Ubicaciones generales
    observaciones: "...", // Observaciones generales
    tallas: {
        dama: { M: 10, L: 5 },
        caballero: { M: 5 }
    },
    datosExtendidos: {
        dama: { M: {...}, L: {...} },
        caballero: { M: {...} },
        sobremedida: {}
    },
    imagenes: [...]
}
```

## 🔧 Funciones Principales

### `extension-editor-tallas-multiproducto.js`

```javascript
// Abre el editor con campos extendidos
window.abrirEditorTallasEspecificas()

// Agrega ubicación a una talla
window.agregarUbicacionATallaExtendido(genero, talla)

// Elimina ubicación de una talla
window.eliminarUbicacionTallaExtendida(genero, talla, index)

// Carga imagen para una talla
window.cargarImagenTallaExtendida(genero, talla, inputElement)

// Elimina imagen de una talla
window.eliminarImagenTallaExtendida(genero, talla)

// Guarda observaciones
window.guardarObservacionesTallaExtendida(genero, talla, texto)

// Obtiene todos los datos de tallas
window.obtenerDatosExtendidosTallasProceso()
```

### `extension-guardar-datos-tallas-extendida.js`

```javascript
// Versión extendida que guarda todo
window.guardarTallasSeleccionadas()

// Obtiene datos de una talla específica
window.obtenerDatosExtendidosTalla(proceso, genero, talla)

// Restaura datos cuando se reabre el editor
window.restaurarDatosExtendidosTallasProceso(proceso)
```

## 🎨 Estilos en las Tarjetas

Cada tarjeta de talla tiene un color distintivo:
- **DAMA**: Rosa (#be185d) para las de color, azul (#1d4ed8) para las estándar
- **CABALLERO**: Azul (#0284c7) para las de color, azul (#3b82f6) para las estándar

Los campos están organizados verticalmente para mejor legibilidad:
1. Header (Checkbox + nombre/cantidad disponible)
2. Campo de cantidad
3. Campo de ubicación(es)
4. Campo de observaciones
5. Panel de imagen

## 📱 Responsive Design

- En pantallas pequeñas: 1 tarjeta por fila (minmax: 280px)
- En pantallas medianas: 2-3 tarjetas por fila
- En pantallas grandes: 3+ tarjetas por fila

## 🔐 Persistencia de Datos

Los datos se guardan automáticamente en:
1. **En memoria**: `window.procesosSeleccionados` (mientras está en el navegador)
2. **En el objeto del proceso**: Se guarden en `datosExtendidos` cuando se hace click en "Guardar Tallas"

## ⚠️ Consideraciones Importantes

1. Las imágenes se guardan como **Data URLs** (base64), pueden ocupar espacio en memoria
2. Cada talla puede tener **múltiples ubicaciones** agregadas manualmente
3. Las observaciones son **opcionales**
4. Las imágenes se pueden **agregar, eliminar y reemplazar** en cualquier momento
5. Los datos se **pierden si no se hace click en "Guardar Tallas"**

## 🧪 Testing

Para probar la funcionalidad:

1. Abre el formulario de crear/editar prenda
2. Marca un checkbox de proceso (Ej: Estampado)
3. Se abrirá el modal del proceso
4. Haz click en "Editar tallas específicas"
5. Verás las tarjetas mejoradas con todos los campos nuevos
6. Prueba agregar ubicaciones, observaciones e imágenes
7. Haz click en "Guardar Tallas"
8. Los datos se guardarán automáticamente

## 📝 Notas para Desarrolladores

- Las funciones originales se guardan con el sufijo "Original" antes de reemplazarlas
- Se usa `event delegation` para manejar clicks en elementos dinámicos
- Los datos se almacenan como objetos JavaScript (no como JSON directamente)
- La sincronización entre la UI y los datos es automática via `data` attributes

## 🚀 Próximas Mejoras Sugeridas

1. Validación de imágenes (tamaño máximo, formato)
2. Preview de datos antes de guardar
3. Historial/undo de cambios
4. Exportación de datos de procesos
5. Plantillas de procesos predefinidos
