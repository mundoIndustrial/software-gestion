# Telas Module - Sistema Modular v2.0

## 📋 Descripción

Sistema modular desacoplado para el manejo de telas, colores, referencias e imágenes de prendas. Organizado en componentes específicos para mejor mantenibilidad y escalabilidad.

## 🏗️ Estructura del Módulo

```
📁 public/js/modulos/crear-pedido/telas/telas-module/
├── 📄 estado-validacion.js          # Estado global y validaciones
├── 📄 gestion-telas.js              # CRUD de telas
├── 📄 manejo-imagenes.js            # Galería y preview de imágenes
├── 📄 ui-renderizado.js              # UI y renderizado de tabla
├── 📄 storage-datos.js              # Storage y obtención de datos
├── 📄 telas-module-main.js           # Loader principal
├── 📄 README.md                     # Documentación completa
```

## 🎯 Componentes del Módulo

### 1. **Estado y Validación** (`estado-validacion.js`)
- **Propósito**: Manejo del estado global y validaciones de campos
- **Funciones**:
  - `limpiarErrorTela()` - Limppiar errores de campos de tela
  - `inicializarEventosTela()` - Configurar event listeners
  - `validarCamposTela()` - Validar campos de tela
  - `mostrarErrorTela()` - Mostrar errores en campos
  - `limpiarTodosLosErroresTela()` - Limpiar todos los errores

### 2. **Gestión de Telas** (`gestion-telas.js`)
- **Propósito**: Operaciones CRUD de telas
- **Funciones**:
  - `agregarTelaNueva()` - Agregar nueva tela con validación
  - `eliminarTela()` - Eliminar tela con confirmación
  - `actualizarTela()` - Actualizar datos de tela existente
  - `obtenerTelaPorIndice()` - Obtener tela por índice
  - `buscarTelas()` - Buscar telas por criterios
  - `existeTela()` - Verificar si existe una tela

### 3. **Manejo de Imágenes** (`manejo-imagenes.js`)
- **Propósito**: Galería, preview y manejo de imágenes
- **Funciones**:
  - `manejarImagenTela()` - Procesar imagen de tela
  - `mostrarGaleriaImagenesTemporales()` - Galería de imágenes temporales
  - `mostrarGaleriaImagenesTela()` - Galería de imágenes de tela
  - `eliminarImagenTemporal()` - Eliminar imagen temporal
  - `actualizarPreviewTelaTemporal()` - Actualizar preview
  - `validarImagenTela()` - Validar archivo de imagen
  - `limpiarImagenesTemporales()` - Limpiar imágenes temporales

### 4. **UI y Renderizado** (`ui-renderizado.js`)
- **Propósito**: Renderizado de tabla y actualización de UI
- **Funciones**:
  - `actualizarTablaTelas()` - Actualizar tabla de telas
  - `crearFilaTela()` - Crear fila de tabla
  - `actualizarContadorTelas()` - Actualizar contador
  - `actualizarBotonesTelas()` - Actualizar botones
  - `actualizarVistaTelas()` - Actualizar vista completa
  - `crearContenedorImagenesTela()` - Crear contenedor de imágenes

### 5. **Storage y Datos** (`storage-datos.js`)
- **Propósito**: Almacenamiento y obtención de datos
- **Funciones**:
  - `obtenerTelasParaEnvio()` - Obtener telas para envío
  - `obtenerTelasParaEdicion()` - Obtener telas para edición
  - `obtenerImagenesTelaParaEnvio()` - Obtener imágenes para envío
  - `obtenerResumenTelas()` - Obtener resumen de telas
  - `tieneTelas()` - Verificar si hay telas
  - `exportarDatosTelas()` - Exportar datos para diferentes contextos
  - `importarDatosTelas()` - Importar datos desde almacenamiento
  - `serializarDatosTelas()` - Serializar datos para almacenamiento
  - `restaurarDatosTelas()` - Restaurar datos desde almacenamiento

##  Instalación y Uso

### Carga Automática (Recomendado)
El módulo se carga automáticamente a través del archivo principal `gestion-telas.js`.

```html
<!-- Carga el módulo completo -->
<script src="/js/modulos/crear-pedido/telas/gestion-telas.js"></script>
```

### Carga Directa de Componentes
Para desarrollo o testing, puedes cargar componentes individuales:

```html
<!-- Cargar componentes específicos -->
<script src="/js/modulos/crear-pedido/telas/telas-module/estado-validacion.js"></script>
<script src="/js/modulos/crear-pedido/telas/telas-module/gestion-telas.js"></script>
<script src="/js/modulos/crear-pedido/telas/telas-module/manejo-imagenes.js"></script>
<script src="/js/modulos/crear-pedido/telas/telas-module/ui-renderizado.js"></script>
<script src="/js/modulos/crear-pedido/telas/telas-module/storage-datos.js"></script>
```

### Eventos Disponibles
```javascript
// Cuando el módulo está completamente cargado
window.addEventListener('telasModuleLoaded', (e) => {
    console.log('🎉 Módulo de telas completamente cargado');
    console.log('Componentes disponibles:', e.detail.components);
});
```

## 🎯 Funciones Disponibles

### Estado y Validación
- `limpiarErrorTela(campo)`
- `inicializarEventosTela()`
- `validarCamposTela(color, tela, referencia)`
- `mostrarErrorTela(campoId, mensaje)`
- `limpiarTodosLosErroresTela()`

### Gestión de Telas
- `agregarTelaNueva()`
- `eliminarTela(index, event)`
- `actualizarTela(index, nuevosDatos)`
- `obtenerTelaPorIndice(index)`
- `buscarTelas(criterios)`
- `existeTela(color, tela)`

### Manejo de Imágenes
- `manejarImagenTela(input)`
- `mostrarGaleriaImagenesTemporales(imagenes, indiceInicial)`
- `mostrarGaleriaImagenesTela(imagenes, telaIndex, indiceInicial)`
- `eliminarImagenTemporal(index)`
- `actualizarPreviewTelaTemporal()`
- `validarImagenTela(file)`
- `limpiarImagenesTemporales()`

### UI y Renderizado
- `actualizarTablaTelas()`
- `crearFilaTela(tela, index)`
- `actualizarContadorTelas()`
- `actualizarBotonesTelas()`
- `actualizarVistaTelas()`
- `crearContenedorImagenesTela()`

### Storage y Datos
- `obtenerTelasParaEnvio()`
- `obtenerTelasParaEdicion()`
- `obtenerImagenesTelaParaEnvio(telaIndex)`
- `obtenerResumenTelas()`
- `tieneTelas()`
- `obtenerTelasConImagenes()`
- `obtenerTelasSinImagenes()`
- `buscarTelasPorColor(color)`
- `buscarTelasPorNombre(nombre)`
- `exportarDatosTelas(contexto)`
- `importarDatosTelas(telas)`
- `serializarDatosTelas()`
- `restaurarDatosTelas(datosSerializados)`

## 🎯 Características

###  Ventajas de la Arquitectura Modular
- **Organización**: Código separado por responsabilidades
- **Claridad**: Cada componente tiene un propósito específico
- **Mantenibilidad**: Fácil modificar componentes individuales
- **Escalabilidad**: Fácil agregar nuevos componentes
- **Testing**: Cada componente puede ser probado independientemente
- **Debugging**: Logs específicos por componente

### 🔧 Funcionalidades Completas
- **Drag & Drop**: Integración completa con el sistema de arrastrar y soltar
- **Validaciones**: Validación de campos y archivos
- **Galería**: Sistema completo de galería de imágenes
- **Storage**: Manejo de datos temporales y persistentes
- **UI Dinámica**: Actualización optimizada del DOM
- **Compatibilidad**: 100% compatible con el sistema existente

###  Estado del Sistema
```javascript
console.log(window.TelasModule);
// {
//   name: 'Telas Module',
//   version: '2.0.0',
//   loaded: true,
//   components: {
//     'estado-validacion': true,
//     'gestion-telas': true,
//     'manejo-imagenes': true,
//     'ui-renderizado': true,
//     'storage-datos': true
//   }
// }
```

##  Reemplazo del Sistema Antiguo

### Cambios Principales
1. **Estructura Modular**: Archivos organizados por funcionalidad
2. **Namespace Central**: Todo bajo `window.TelasModule`
3. **Logging Mejorado**: Logs detallados por componente
4. **Eventos Centralizados**: Eventos del módulo disponibles
5. **Sin Compatibilidad Legacy**: Reemplazo completo del sistema antiguo

### Sistema Eliminado
- **Archivo Original**: `gestion-telas.js` (1052 líneas monolíticas)
- **Backup**: Eliminado para evitar confusión
- **Compatibilidad**: No se mantiene compatibilidad con sistema antiguo

### Nuevo Sistema
- **Modular**: 5 componentes especializados
- **Eficiente**: Carga bajo demanda
- **Escalable**: Fácil agregar nuevos componentes
- **Documentado**: README completo y ejemplos

### Pasos de Migración
1. **Reemplazar**: El archivo `gestion-telas.js` ahora carga el sistema modular
2. **Sin Cambios**: Las funciones globales siguen disponibles
3. **Mejorado**: Todas las funciones tienen mejoras y logs
4. **Eventos**: Ahora se dispara `telasModuleLoaded` cuando está listo

## 🔮 Roadmap Futuro

### v2.1 (Planeado)
- [ ] Sistema de plugins para el módulo de telas
- [ ] Configuración personalizable por componente
- [ ] Testing automatizado por componente
- [ ] Documentación interactiva por componente

### v2.2 (Futuro)
- [ ] TypeScript definitions por componente
- [ ] Sistema de temas por componente
- [ ] Internacionalización por componente
- [ **Performance profiling** por componente

## 📝 Notas Importantes

### 🎯 Propósito del Módulo
- **Claridad**: Saber exactamente qué pertene a cada archivo
- **Organización**: Estructura lógica y mantenible
- **Escalabilidad**: Fácil agregar nuevos componentes
- **Debugging**: Logs específicos por componente

### 🔧 Mantenimiento
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
**Módulo**: Telas Module  
**Estructura**: Modular por carpetas  
**Última actualización**: 2026-02-10  
**Compatibilidad**: 100% con sistema existente
