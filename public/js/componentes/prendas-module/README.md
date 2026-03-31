# Prendas Module - Sistema Modular v2.0

##  Descripción

Sistema modular desacoplado para el manejo de prendas, imágenes y funcionalidades de drag & drop. Organizado en una carpeta específica para mejor claridad y mantenibilidad.

##  Estructura del Módulo

```
public/js/componentes/prendas-module/
├── index.js                          # Punto de entrada principal
├── prendas-wrappers-v2.js           # Loader principal del sistema
├── modal-wrappers.js                #  Gestión de modales
├── image-management.js              # 🖼️ Manejo de imágenes
├── drag-drop-handlers.js            # 🎯 Funcionalidades drag & drop
├── ui-helpers.js                    #  Utilidades y helpers
└── README.md                         # 📚 Documentación
```

##  Componentes del Módulo

### 1. **Index** (`index.js`)
- Punto de entrada principal del módulo
- Define namespace `PrendasModule`
- Gestión centralizada de componentes

### 2. **Main Loader** (`prendas-wrappers-v2.js`)
- Carga secuencial de componentes
- Sistema de eventos y logging
- Compatibilidad con sistema legacy

### 3. **Modal Wrappers** (`modal-wrappers.js`)
- Gestión de modales de prendas
- Funciones proxy que delegan a `GestionItemsUI`
- Compatibilidad hacia atrás

**Funciones:**
- `abrirModalPrendaNueva()`
- `cerrarModalPrendaNueva()`
- `agregarPrendaNueva()`
- `cargarItemEnModal()`
- `abrirSelectorPrendas()`

### 4. **Image Management** (`image-management.js`)
- Manejo de imágenes de prendas y telas
- Integración con servicios de almacenamiento
- Preview y galería de imágenes

**Funciones:**
- `manejarImagenesPrenda()`
- `actualizarPreviewPrenda()`
- `manejarImagenTela()`
- `actualizarPreviewTela()`

### 5. **Drag & Drop Handlers** (`drag-drop-handlers.js`)
- Funcionalidades completas de drag & drop
- Soporte para imágenes de prendas y telas
- Feedback visual y validaciones

**Funciones:**
- `setupDragAndDrop()`
- `setupDragAndDropConImagen()`
- `setupDragDropTela()`
- `setupDragDropTelaPreview()`
- `inicializarDragDropPrenda()`
- `inicializarDragDropTela()`

### 6. **UI Helpers** (`ui-helpers.js`)
- Utilidades de interfaz de usuario
- Modales de error y límites
- Galería de imágenes

**Funciones:**
- `limpiarFormulario()`
- `mostrarModalLimiteImagenes()`
- `mostrarModalError()`
- `mostrarGaleriaImagenesPrenda()`

##  Instalación y Uso

### Carga Automática (Recomendado)

```html
<!-- Carga el módulo completo -->
<script src="/js/componentes/prendas-module/index.js"></script>
```

### Carga a través de Compatibilidad

```html
<!-- El archivo existente mantiene compatibilidad -->
<script src="/js/componentes/prendas-wrappers.js"></script>
```

### Carga Directa del Loader

```html
<!-- Carga directa del sistema principal -->
<script src="/js/componentes/prendas-module/prendas-wrappers-v2.js"></script>
```

##  Eventos del Módulo

### Eventos Disponibles

```javascript
// Evento principal del módulo
window.addEventListener('prendasModuleLoaded', (e) => {
    console.log('🎉 Módulo completamente cargado');
    console.log('Componentes:', e.detail.components);
});

// Evento legacy para compatibilidad
window.addEventListener('prendasWrappersLoaded', () => {
    console.log(' Sistema legacy cargado');
});
```

### Estado del Módulo

```javascript
// Verificar estado del módulo
console.log(window.PrendasModule);
// {
//   name: 'Prendas Module',
//   version: '2.0.0',
//   loaded: true,
//   components: {
//     'ui-helpers': true,
//     'image-management': true,
//     'drag-drop-handlers': true,
//     'modal-wrappers': true
//   }
// }
```

## 🎯 Características

###  Ventajas de la Estructura Modular

- **Organización**: Archivos agrupados por funcionalidad
- **Claridad**: Fácil identificar a qué pertenece cada archivo
- **Mantenibilidad**: Cada componente es independiente
- **Escalabilidad**: Fácil agregar nuevos componentes
- **Testing**: Testing unitario por componente
- **Namespace**: Todo bajo `PrendasModule`

###  Funcionalidades Completas

- **Drag & Drop Completo**: Para imágenes de prendas y telas
- **Gestión de Imágenes**: Preview, galería, eliminación
- **Modales**: Gestión centralizada de modales
- **Validaciones**: Validación de tipos de archivos y límites
- **Feedback Visual**: Efectos visuales en todas las interacciones

## 🐛 Depuración y Logging

### Logs del Sistema

El módulo incluye logs detallados con emojis:

```
 Prendas Module v2.0.0 - Iniciando carga de componentes...
 Cargando componente: ui-helpers
 Componente cargado: ui-helpers
 Cargando componente: image-management
 Componente cargado: image-management
🎉 Prendas Module completamente cargado
```

### Navegación del Código

- **📁 Carpeta clara**: Todo en `prendas-module/`
- **🏷️ Nombres descriptivos**: Cada archivo indica su propósito
- ** Documentación**: README en cada nivel
- ** Búsqueda fácil**: Encuentra rápidamente lo que necesitas

##  Migración desde Sistema Antiguo

### Cambios Principales

1. **Estructura de Carpetas**: Archivos organizados en `prendas-module/`
2. **Namespace Central**: Todo bajo `window.PrendasModule`
3. **Index Principal**: Punto de entrada único
4. **Mejor Logging**: Logs estructurados por componente

### Compatibilidad Mantenida

- **100% Compatible**: Código existente sigue funcionando
- **Sin Cambios**: No requiere modificaciones al código actual
- **Gradual**: Puede migrarse componente por componente

## 🔮 Roadmap Futuro

### v2.1 (Planeado)
- [ ] Sistema de plugins dentro del módulo
- [ ] Configuración por componente
- [ ] Testing automatizado por módulo
- [ ] Documentación interactiva

### v2.2 (Futuro)
- [ ] TypeScript definitions por módulo
- [ ] Sistema de temas por componente
- [ ] Internacionalización por módulo
- [ ] Performance profiling por componente

## 📝 Notas Importantes

### 🎯 Propósito del Módulo
- **Claridad**: Saber exactamente a qué pertenece cada archivo
- **Organización**: Estructura lógica y mantenible
- **Escalabilidad**: Fácil agregar nuevos componentes
- **Debugging**: Logs específicos por componente

###  Mantenimiento
- **Independiente**: Cada componente puede ser modificado por separado
- **Versionado**: Cada componente puede tener su propia versión
- **Testing**: Testing unitario por componente
- **Documentación**: README específico por componente

###  Rendimiento
- **Carga Eficiente**: Solo carga componentes necesarios
- **Lazy Loading**: Posibilidad de carga bajo demanda
- **Cache**: Mejor cacheo por componente
- **Optimización**: Optimización individual por componente

---

**Versión**: 2.0.0  
**Módulo**: Prendas Module  
**Estructura**: Modular por carpetas  
**Última actualización**: 2026-02-10
